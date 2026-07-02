<?php

namespace App\Http\Controllers\Backend\AI;

use App\Exports\RoleGoalsExport;
use App\Http\Controllers\Controller;
use App\Mail\EmailManager;
use App\Models\AiChat;
use App\Models\AiChatCategory;
use App\Models\AiChatMessage;
use App\Models\AiChatPrompt;
use App\Models\AiChatPromptGroup;
use App\Models\ChatCategory;
use App\Models\ChatRoleCategory;
use App\Models\DriftEvent;
use App\Models\ExpectedState;
use App\Models\Intervention;
use App\Models\ObservedState;
use App\Models\SearchUserChat;
use App\Models\SearchUserChatData;
use App\Models\SubcategoryMenu;
use App\Models\SubscriptionPackage;
use App\Models\UserChatAnswer;
use App\Services\AI\AiProviderService;
use App\Services\AI\DocumentContextService;
use App\Services\Integration\IntegrationService;
use App\Services\WriteBotService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class AiChatController extends Controller
{
    public function __construct(
        protected AiProviderService $ai,
        protected DocumentContextService $docs,
    ) {
        if (getSetting('enable_ai_chat') == '0') {
            flash(localize('AI chat is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }

    // ---------------------------------------------------------------------------
    // GoalSync prompt builder
    // ---------------------------------------------------------------------------

    private function goalSyncJsonPrompt(string $question, string $documentNamesList): string
    {
        return <<<EOT
You are an executive strategy assistant trained in the GoalSync 7-step framework.

User Goal: "$question"
{$documentNamesList}

Return a SINGLE valid JSON object (no markdown, no code fences, no commentary) with EXACTLY this shape:

{
  "acknowledgement": "1-2 warm sentences acknowledging the goal",
  "documentInsights": ["3-5 insights; at least one MUST reference a specific document by name"],
  "goalAssessment": "2-3 sentences assessing alignment of the goal with the company",
  "scoring": [
    {"label": "Alignment with Company Goals", "value": "High"},
    {"label": "Feasibility", "value": "Medium"},
    {"label": "Impact on Operations", "value": "High"},
    {"label": "Risk Level", "value": "Medium"}
  ],
  "strategyMap": [
    {"id": "s1", "name": "Descriptive Strategy Name", "rationale": "one sentence", "teams": "IT, Operations", "tradeoffs": "what is gained vs lost", "risk": "Low|Medium|High"}
  ],
  "selectedStrategyId": "s1"
}

Rules:
- "strategyMap": 3 decision paths (pathways), each with a UNIQUE id (s1, s2, s3) and a descriptive name (never "Path A/1").
- Do NOT generate assumptions, scenarios, simulations, role goals or outcomes here. Those are produced on demand AFTER the user picks a pathway: first the pathway's assumptions are derived, then the simulations are generated from the pathway + its assumptions.
- "acknowledgement" must be a non-empty 1-2 sentence string.
- "selectedStrategyId" = "s1".
- Output VALID JSON only: double-quoted keys/strings, no trailing commas, no comments, no markdown.
EOT;
    }

    // ---------------------------------------------------------------------------
    // Chat CRUD (existing AiChat expert system)
    // ---------------------------------------------------------------------------

    public function index(Request $request, WriteBotService $writeBotService)
    {
        $user = user();

        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            if ($package->allow_ai_chat == 0) {
                abort(403);
            }
        } elseif (! auth()->user()->can('ai_chat')) {
            abort(403);
        }

        $conditions = [['type', 'chat']];

        if (! isCustomer()) {
            $chatExpertIds = $writeBotService->getAiChatCategories(null, null, $conditions);
            $chatExperts = $writeBotService->getAiChatCategories(true, 1, $conditions);
        } else {
            $chatExpertIds = $writeBotService->getAiChatCategories(null, 1, $conditions);
            $chatExperts = $writeBotService->getAiChatCategories(true, 1, $conditions);
        }

        $chatListQuery = AiChat::orderBy('updated_at', 'DESC')
            ->with('messages', 'category')
            ->where('user_id', $user->id)
            ->whereIn('ai_chat_category_id', $chatExpertIds);

        $searchKey = null;

        if (! empty($request->search)) {
            $chatListQuery->where('title', 'like', '%'.$request->search.'%');
            $searchKey = $request->search;
        }

        $chatList = ! empty($request->expert)
            ? $chatListQuery->where('ai_chat_category_id', $request->expert)->get()
            : $chatListQuery->where('ai_chat_category_id', 1)->get();

        $promptGroups = AiChatPromptGroup::oldest()->get();
        $prompts = AiChatPrompt::latest()->get();
        $conversation = $chatListQuery->first();
        $documents = $this->docs->forUser($user);
        $documentCount = $documents->count();
        $documentContext = $documents->pluck('parsed_text')->filter()->implode("\n\n--- Document Separator ---\n\n");

        return view('backend.pages.aiChat.index', compact(
            'chatExperts', 'chatList', 'conversation', 'searchKey',
            'promptGroups', 'prompts', 'documentCount', 'documentContext'
        ));
    }

    public function store(Request $request)
    {
        $user = user();

        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            if ($package->allow_ai_chat == 0) {
                return response()->json([
                    'status' => 400,
                    'success' => false,
                    'message' => localize('AI Chat is not available in this package, please upgrade you plan'),
                ]);
            }
        }

        $expert = AiChatCategory::find($request->ai_chat_category_id);

        if (empty($expert)) {
            return response()->json([
                'status' => 400,
                'ai_chat_category_id' => $request->ai_chat_category_id,
                'success' => false,
                'message' => localize('Expert not found'),
            ]);
        }

        $conversation = new AiChat;
        $conversation->user_id = $user->id;
        $conversation->ai_chat_category_id = $request->ai_chat_category_id;
        $conversation->title = $expert->name.localize(' Chat');
        $conversation->save();

        $result = $expert->role === 'default'
            ? localize("Hello! I am $expert->name, and I'm here to answer your all questions.")
            : localize("Hello! I am $expert->name, and I'm $expert->role. $expert->assists_with.");

        $message = new AiChatMessage;
        $message->ai_chat_id = $conversation->id;
        $message->user_id = $user->id;
        $message->response = $result;
        $message->result = $result;
        $message->save();

        $chatList = AiChat::latest()->where('ai_chat_category_id', $expert->id)->where('user_id', $user->id)->get();
        $promptGroups = AiChatPromptGroup::oldest()->get();
        $prompts = AiChatPrompt::latest()->get();
        $documentCount = $this->docs->forUser($user)->count();

        return response()->json([
            'status' => 200,
            'chatList' => view('backend.pages.aiChat.inc.chat-list', compact('chatList'))->render(),
            'messagesContainer' => view('backend.pages.aiChat.inc.messages-container', compact('conversation', 'promptGroups', 'prompts', 'documentCount'))->render(),
        ]);
    }

    public function update(Request $request)
    {
        $conversation = AiChat::findOrFail((int) $request->chatId);
        $conversation->title = $request->value;
        $conversation->save();
    }

    public function delete($id)
    {
        $conversation = AiChat::findOrFail((int) $id);
        AiChatMessage::where('ai_chat_id', $conversation->id)->delete();
        $conversation->delete();

        flash(localize('Chat has been deleted successfully'))->success();

        return back();
    }

    public function newMessage(Request $request)
    {
        $chat = AiChat::findOrFail((int) $request->chat_id);
        $user = auth()->user();

        if (isCustomer() && availableDataCheck('words') <= 10) {
            return response()->json([
                'status' => 400,
                'ai_chat_category_id' => $request->category_id,
                'success' => false,
                'message' => localize('Your word balance is low, please upgrade you plan'),
            ]);
        }

        $message = new AiChatMessage;
        $message->ai_chat_id = $chat->id;
        $message->user_id = $user->id;
        $message->prompt = $request->prompt;
        $message->result = $request->prompt;
        $message->save();

        $message->aiChat->touch();

        $request->session()->put('chat_id', $chat->id);
        $request->session()->put('message_id', $message->id);
        $request->session()->put('category_id', $request->category_id);
        $request->session()->put('real_time_data', $request->real_time_data == 1 ? 1 : null);

        return response()->json([
            'status' => 200,
            'ai_chat_category_id' => $request->category_id,
            'success' => false,
            'message' => '',
        ]);
    }

    public function process()
    {
        $request = request();
        $request->merge(['stream' => true, 'content_type' => 'ai_chat']);

        return (new IntegrationService)->contentGenerator(aiChatEngine(), $request);
    }

    public function updateUserWords($tokens, $user)
    {
        if ($user->user_type === 'customer') {
            updateDataBalance('words', $tokens, $user);
        }
    }

    public function updateBalanceStopGeneration(Request $request)
    {
        $randomNumber = session()->get('random_number');
        $user = user();

        if ($randomNumber && isCustomer()) {
            $aiChatMessage = AiChatMessage::where('random_number', $randomNumber)
                ->where('user_id', $user->id)
                ->first();

            if ($aiChatMessage) {
                $this->updateUserWords($aiChatMessage->words, $user);
                session()->forget('random_number');

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false]);
    }

    public function getMessages(Request $request)
    {
        $conversation = AiChat::find((int) $request->chatId);

        if (is_null($conversation)) {
            return response()->json(['status' => 400]);
        }

        $user = auth()->user();
        $promptGroups = AiChatPromptGroup::oldest()->get();
        $prompts = AiChatPrompt::latest()->get();
        $documentCount = $this->docs->forUser($user)->count();

        return response()->json([
            'status' => 200,
            'messagesContainer' => view('backend.pages.aiChat.inc.messages-container', compact('conversation', 'promptGroups', 'prompts', 'documentCount'))->render(),
        ]);
    }

    public function getConversations(Request $request)
    {
        $conversationsQuery = AiChat::where('ai_chat_category_id', (int) $request->ai_chat_category_id)
            ->where('user_id', auth()->id())
            ->latest('updated_at');

        $chatList = $conversationsQuery->get();
        $conversation = $conversationsQuery->first();
        $promptGroups = AiChatPromptGroup::oldest()->get();
        $prompts = AiChatPrompt::latest()->get();
        $ai_chat_category_id = $request->ai_chat_category_id;

        return response()->json([
            'status' => 200,
            'ai_chat_category_id' => $ai_chat_category_id,
            'chatRight' => view('backend.pages.aiChat.inc.chat-right', compact('conversation', 'chatList', 'promptGroups', 'prompts'))->render(),
        ]);
    }

    public function sendInEmail(Request $request)
    {
        if ($request->email == null) {
            flash(localize('Please type an email'))->error();

            return back();
        }

        $conversation = AiChat::findOrFail((int) $request->conversation_id);

        try {
            Mail::to($request->email)->queue(new EmailManager([
                'view' => 'emails.chat',
                'from' => config('custom.mail_from_address'),
                'subject' => $conversation->title,
                'conversation' => $conversation,
                'messages' => $conversation->messages,
            ]));

            flash(localize('Chat successfully sent to email'))->success();
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
        }

        return back();
    }

    public function downloadChatHistory(Request $request)
    {
        try {
            $type = $request->type;
            $conversation = AiChat::findOrFail((int) $request->chatId);
            $messages = $conversation->messages;
            $name = $conversation->category ? $conversation->category->name : 'ai_chat';

            if (! $messages) {
                flash(localize('No Message Fund'));

                return redirect()->back();
            }

            $data = ['messages' => $messages, 'conversation' => $conversation, 'type' => $type];

            if (in_array($type, ['html', 'word'])) {
                $ext = $type === 'html' ? '.html' : '.doc';
                $filePath = public_path('/').str_replace(' ', '_', $name).$ext;

                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                file_put_contents($filePath, view('backend.pages.aiChat.download.AI_ChatBot', $data)->render());

                return response()->download($filePath);
            }

            if ($type === 'pdf') {
                return view('backend.pages.aiChat.download.AI_ChatBot', $data);
            }

            if ($type === 'copyChat') {
                return view('backend.pages.aiChat.download.copyChat', $data);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    // ---------------------------------------------------------------------------
    // GoalSync chat
    // ---------------------------------------------------------------------------

    public function newchat(Request $request)
    {
        $user = auth()->user();
        $chatrolecategories = ChatRoleCategory::active()->get();
        $chatcategories = ChatCategory::active()->forRole($user->chat_role_categories)->get();

        return view('backend.pages.aiChat.newchat', compact('user', 'chatrolecategories', 'chatcategories'));
    }

    public function userchathistory(Request $request)
    {
        $user = auth()->user();
        $userhistorydata = UserChatAnswer::where('user_id', $user->id)->get();

        return view('backend.pages.aiChat.user-chat-history', compact('user', 'userhistorydata'));
    }

    public function newusers_new_chat(Request $request)
    {
        $user = auth()->user();
        $newChat = SearchUserChat::create(['user_id' => $user->id, 'status1' => 0]);

        flash(localize('New Chat.'));

        return redirect('dashboard/users-new-chat/'.$newChat->id);
    }

    public function users_new_chat(Request $request, $id)
    {
        $user = auth()->user();
        $searchKey = null;
        $promptGroups = AiChatPromptGroup::oldest()->get();
        $prompts = AiChatPrompt::latest();

        if ($request->search != null) {
            $prompts = $prompts->where('title', 'like', '%'.$request->search.'%')
                ->orWhere('prompt', 'like', '%'.$request->search.'%');
            $searchKey = $request->search;
        }

        $prompts = $prompts->get();

        $searchuserchatdata = SearchUserChatData::where('search_user_chat_id', $id)
            ->where('user_id', $user->id)
            ->get();

        $today = Carbon::now()->startOfDay();
        $yesterday = Carbon::yesterday()->startOfDay();
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();

        $searchuserchatdatanew = SearchUserChatData::where('user_id', $user->id)
            ->get()
            ->groupBy(function ($item) use ($today, $yesterday, $sevenDaysAgo, $thirtyDaysAgo) {
                $created = Carbon::parse($item->created_at);

                if ($created->greaterThanOrEqualTo($today)) {
                    return 'Today';
                } elseif ($created->greaterThanOrEqualTo($yesterday) && $created->lessThan($today)) {
                    return 'Yesterday';
                } elseif ($created->greaterThanOrEqualTo($sevenDaysAgo)) {
                    return 'Previous 7 Days';
                } elseif ($created->greaterThanOrEqualTo($thirtyDaysAgo)) {
                    return 'Previous 30 Days';
                } else {
                    return 'Older';
                }
            });

        $documentCount = $this->docs->forUser($user)->count();

        $chatRecord = SearchUserChat::where('id', $id)->where('user_id', $user->id)->first();

        $chatTotalTokens = (int) ($chatRecord->total_tokens ?? 0);
        $selectedStrategyFromDB = $chatRecord->selected_strategy ?? null;
        $leadershipBriefFromDB = ! empty($chatRecord->leadership_brief) ? $chatRecord->leadership_brief : null;

        return view('backend.pages.aiChat.users-new-chat', compact(
            'user', 'promptGroups', 'prompts', 'searchKey', 'searchuserchatdata', 'id',
            'searchuserchatdatanew', 'documentCount', 'selectedStrategyFromDB',
            'leadershipBriefFromDB', 'chatTotalTokens'
        ));
    }

    public function generate_strategy_variant(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $question = $request->input('original_question');
        $strategyId = trim((string) $request->input('strategy_id'));
        $strategyName = trim((string) $request->input('strategy_name'));
        $strategyRationale = trim((string) $request->input('strategy_rationale'));
        $assumptions = $this->normalizeAssumptions($request->input('assumptions'));

        if (! $chatId || ! $question || $strategyName === '') {
            return response()->json(['error' => 'Chat ID, original question and strategy name are required.'], 400);
        }

        $documents = $this->docs->forUser($user);
        $systemMessage = $this->docs->buildSystemMessage($user, 'You are an executive strategy assistant trained in the GoalSync framework. Return ONLY valid JSON. No markdown, no code fences, no commentary.');

        $rationaleLine = $strategyRationale !== '' ? "Strategy rationale: \"{$strategyRationale}\"\n" : '';

        // Simulations are derived from TWO inputs: the chosen pathway and the
        // assumptions already derived for it. Feed the assumptions in so the
        // scenarios stay consistent with them.
        $assumptionsBlock = '';
        if (! empty($assumptions)) {
            $assumptionsList = implode("\n", array_map(fn ($a) => "- {$a}", $assumptions));
            $assumptionsBlock = "Assumptions derived for this pathway (the simulations MUST be consistent with these):\n{$assumptionsList}\n\n";
        }

        $prompt = <<<EOT
User Goal: "{$question}"
Selected strategy: "{$strategyName}"
{$rationaleLine}
{$assumptionsBlock}Generate the GoalSync variant for THIS strategy as a SINGLE valid JSON object with EXACTLY this shape:

{
  "scenarios": [
    {"id": "sc1", "label": "Best Case", "text": "one sentence"},
    {"id": "sc2", "label": "Expected", "text": "one sentence"},
    {"id": "sc3", "label": "Risk", "text": "one sentence"}
  ],
  "selectedScenarioId": "sc1",
  "scenarioVariants": {
    "sc1": {
      "rolesGoals": [{"role": "Role title from documents", "goal": "1-2 sentences", "action": "EXACTLY one sentence"}],
      "complementaryGoals": ["goal one", "goal two"],
      "finalOutcome": "two sentences for this strategy + scenario"
    },
    "sc2": {
      "rolesGoals": [{"role": "Role title from documents", "goal": "1-2 sentences", "action": "EXACTLY one sentence"}],
      "complementaryGoals": ["goal one", "goal two"],
      "finalOutcome": "two sentences for this strategy + scenario"
    },
    "sc3": {
      "rolesGoals": [{"role": "Role title from documents", "goal": "1-2 sentences", "action": "EXACTLY one sentence"}],
      "complementaryGoals": ["goal one", "goal two"],
      "finalOutcome": "two sentences for this strategy + scenario"
    }
  }
}

Rules:
- "scenarioVariants" MUST contain ALL THREE keys sc1, sc2 AND sc3.
- Each "rolesGoals": 5 to 7 DISTINCT roles using ONLY exact role titles from the documents. "action" is EXACTLY one sentence.
- The scenarios (simulations) MUST be consistent with the assumptions listed above when any are provided.
- Output VALID JSON only: double-quoted keys/strings, no trailing commas, no comments, no markdown.
EOT;

        try {
            $aiResponse = $this->ai->generate($systemMessage, $prompt, 3000, 0.7, true);

            if (! $aiResponse->successful()) {
                return response()->json(['error' => 'Failed to generate strategy variant.', 'details' => $aiResponse->body()], 500);
            }

            $text = $this->ai->extractText($aiResponse);
            $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);
            $variant = $this->ai->parseJson($text);

            if ($variant === null) {
                Log::warning('Strategy variant JSON parse failed', ['user_id' => $user->id, 'chat_id' => $chatId, 'preview' => substr((string) $text, 0, 300)]);

                return response()->json(['error' => 'Could not parse strategy variant.'], 502);
            }

            // Keep the assumptions on the variant so they persist and re-render
            // alongside the pathway and simulations on reload.
            $variant['assumptions'] = $assumptions;

            // Persist the pathway's assumptions + simulations into the stored
            // contract JSON so the downstream Expected/Observed State workflow
            // can read them later.
            if ($strategyId !== '') {
                $this->persistContractMutation($chatId, $user->id, function (array &$data) use ($strategyId, $variant, $assumptions) {
                    $data['strategyVariants'] = $data['strategyVariants'] ?? [];
                    $data['strategyVariants'][$strategyId] = $variant;
                    $data['pathwayAssumptions'] = $data['pathwayAssumptions'] ?? [];
                    $data['pathwayAssumptions'][$strategyId] = $assumptions;
                    $data['selectedStrategyId'] = $strategyId;
                });
            }

            return response()->json(['success' => true, 'variant' => $variant, 'chat_total_tokens' => $chatTotalTokens]);
        } catch (\Throwable $e) {
            return $this->ai->handleException($e, 'Generate Strategy Variant', $user->id, $chatId);
        }
    }

    public function generate_pathway_assumptions(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $question = $request->input('original_question');
        $strategyId = trim((string) $request->input('strategy_id'));
        $strategyName = trim((string) $request->input('strategy_name'));
        $strategyRationale = trim((string) $request->input('strategy_rationale'));

        if (! $chatId || ! $question || $strategyName === '') {
            return response()->json(['error' => 'Chat ID, original question and strategy name are required.'], 400);
        }

        $systemMessage = $this->docs->buildSystemMessage($user, 'You are an executive strategy assistant trained in the GoalSync framework. Return ONLY valid JSON. No markdown, no code fences, no commentary.');

        $rationaleLine = $strategyRationale !== '' ? "Strategy rationale: \"{$strategyRationale}\"\n" : '';

        $prompt = <<<EOT
User Goal: "{$question}"
Selected strategic pathway: "{$strategyName}"
{$rationaleLine}
The user has just chosen this strategic pathway. BEFORE any simulations are generated, derive the key ASSUMPTIONS this pathway depends on. These assumptions will be stored and later used to calculate Expected and Observed State, and the simulations will be generated from the pathway PLUS these assumptions.

Return a SINGLE valid JSON object (no markdown, no code fences, no commentary) with EXACTLY this shape:

{
  "assumptions": [
    "A concise, testable assumption this pathway relies on",
    "Another concise, testable assumption"
  ]
}

Rules:
- Provide between 3 and 5 assumptions.
- Each assumption MUST be specific to THIS pathway and the user's company context (grounded in the provided documents where possible).
- Each assumption is ONE concise sentence, phrased as a condition that can later be measured as true/false (e.g. budget, capacity, adoption, timeline, dependency).
- Do NOT include scenarios, role goals, or outcomes — only assumptions.
- Output VALID JSON only: double-quoted keys/strings, no trailing commas, no comments, no markdown.
EOT;

        try {
            $aiResponse = $this->ai->generate($systemMessage, $prompt, 1200, 0.7, true);

            if (! $aiResponse->successful()) {
                return response()->json(['error' => 'Failed to derive pathway assumptions.', 'details' => $aiResponse->body()], 500);
            }

            $text = $this->ai->extractText($aiResponse);
            $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);
            $parsed = $this->ai->parseJson($text);
            $assumptions = $this->normalizeAssumptions($parsed['assumptions'] ?? null);

            if (empty($assumptions)) {
                Log::warning('Pathway assumptions JSON parse failed', ['user_id' => $user->id, 'chat_id' => $chatId, 'preview' => substr((string) $text, 0, 300)]);

                return response()->json(['error' => 'Could not derive pathway assumptions.'], 502);
            }

            // Persist the assumptions onto the stored contract alongside the pathway.
            if ($strategyId !== '') {
                $this->persistContractMutation($chatId, $user->id, function (array &$data) use ($strategyId, $assumptions) {
                    $data['pathwayAssumptions'] = $data['pathwayAssumptions'] ?? [];
                    $data['pathwayAssumptions'][$strategyId] = $assumptions;
                    $data['selectedStrategyId'] = $strategyId;
                });
            }

            return response()->json(['success' => true, 'assumptions' => $assumptions, 'chat_total_tokens' => $chatTotalTokens]);
        } catch (\Throwable $e) {
            return $this->ai->handleException($e, 'Generate Pathway Assumptions', $user->id, $chatId);
        }
    }

    public function users_new_chat_ask(Request $request)
    {
        $user = auth()->user();
        $question = $request->input('question');
        $chatId = $request->input('chat_id');
        $additionalContext = $request->input('additional_context');

        if (! $question || ! $chatId) {
            return response()->json(['error' => 'Question and Chat ID are required.'], 400);
        }

        $previousContext = UserChatAnswer::where('user_id', $user->id)->latest()->first();
        $chat = SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->first();

        if (! $chat) {
            $chat = SearchUserChat::create(['user_id' => $user->id, 'status1' => 0]);
            $chatId = $chat->id;
        }

        $isFirstMessage = $chat->isFirstMessage();
        $documents = $this->docs->forUser($user);
        $documentNamesList = $this->docs->buildNamesList($documents);

        $systemMessage = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.';

        if ($isFirstMessage) {
            $systemMessage .= $this->docs->buildContext($documents);
        } elseif (! empty($documentNamesList)) {
            $systemMessage .= "\n\n--- COMPANY DOCUMENTS (names only) ---\n"
                ."Full document text was provided earlier in this session. The available company documents are:\n"
                .$documentNamesList
                ."--- END COMPANY DOCUMENTS ---\n";
        }

        $systemMessage .= $this->resolveAdditionalContext($request, $chat, $additionalContext);

        $responseFormat = 'markdown';
        $provider = $this->ai->providerLabel();

        if ($isFirstMessage) {
            $prompt = $this->goalSyncJsonPrompt($question, $documentNamesList);
            $responseFormat = 'json';

            try {
                Log::info("{$provider} Request - First Chat", ['user_id' => $user->id, 'chat_id' => $chatId, 'documents_count' => $documents->count()]);

                $aiResponse = $this->ai->generate($systemMessage, $prompt, 4096, 0.7, true);

                Log::info("{$provider} Response - First Chat", ['user_id' => $user->id, 'chat_id' => $chatId, 'status' => $aiResponse->status()]);

                UserChatAnswer::where('user_id', $user->id)->update(['status1' => 1]);
                SearchUserChat::where('id', $chatId)->update(['status1' => 1]);
            } catch (\Throwable $e) {
                return $this->ai->handleException($e, 'First Chat', $user->id, $chatId);
            }
        } else {
            $followUpPrompt = <<<EOT
User message: "$question"

Respond using the GoalSync method, but keep it SHORT and scannable:
- No preamble or filler.
- Use brief emoji section headers only where useful.
- Bullet points, one line each, max ~12 words per bullet.
- No long paragraphs. No restating the question.
- Be specific to the user's company context.
- Keep the whole reply under ~120 words.
EOT;

            try {
                Log::info("{$provider} Request - Follow-up Chat", ['user_id' => $user->id, 'chat_id' => $chatId]);

                $aiResponse = $this->ai->generate($systemMessage, $followUpPrompt, 1200);

                Log::info("{$provider} Response - Follow-up Chat", ['user_id' => $user->id, 'chat_id' => $chatId, 'status' => $aiResponse->status()]);

                UserChatAnswer::where('user_id', $user->id)->update(['status2' => 1]);
                SearchUserChat::where('id', $chatId)->update(['status2' => 1]);
            } catch (\Throwable $e) {
                return $this->ai->handleException($e, 'Follow-up Chat', $user->id, $chatId);
            }
        }

        if (! $aiResponse->successful()) {
            $statusCode = $aiResponse->status();
            $clientError = $statusCode === 429
                ? "{$provider} API quota exceeded. Check your billing or reduce request rate."
                : "{$provider} API request failed.";

            Log::error("{$provider} Unsuccessful Response", ['user_id' => $user->id, 'chat_id' => $chatId, 'status' => $statusCode]);

            return response()->json(['error' => $clientError, 'details' => $aiResponse->json() ?? $aiResponse->body()],
                $statusCode >= 400 && $statusCode < 500 ? $statusCode : 500);
        }

        $responseContent = $this->ai->extractText($aiResponse);
        $usage = $this->ai->extractUsage($aiResponse);
        $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);

        if ($responseContent === '') {
            Log::error("{$provider} returned empty text", ['user_id' => $user->id, 'chat_id' => $chatId]);

            return response()->json(['error' => "{$provider} returned an empty response. Please try again."], 502);
        }

        if ($responseFormat === 'json' && $this->ai->parseJson($responseContent) === null) {
            Log::warning('GoalSync JSON parse failed - falling back to markdown', ['user_id' => $user->id, 'chat_id' => $chatId]);
            $responseFormat = 'markdown';
        }

        $commonData = [
            'user_id' => $user->id,
            'answers' => $previousContext->answers ?? null,
            'chat_role_categories' => $previousContext->chat_role_categories ?? null,
            'categories' => $previousContext->categories ?? null,
            'subcategories' => $previousContext->subcategories ?? null,
            'questionmenuid' => $previousContext->questionmenuid ?? null,
            'search' => $question,
            'response' => $responseContent,
        ];

        SearchUserChat::where('id', $chatId)->update($commonData);
        SearchUserChatData::create(array_merge($commonData, ['search_user_chat_id' => $chatId]));

        return response()->json([
            'question' => $question,
            'answer' => $responseContent,
            'format' => $responseFormat,
            'usage' => $usage,
            'chat_total_tokens' => $chatTotalTokens,
            'chat_id' => $chatId,
            'previousContext' => $previousContext ? (object) ['status1' => $previousContext->status1, 'status2' => $previousContext->status2] : null,
            'chectdata' => $chat ? (object) ['status1' => $chat->status1,         'status2' => $chat->status2] : null,
        ]);
    }

    public function users_new_chat_update_strategy(Request $request)
    {
        $user = auth()->user();
        $selectedStrategy = $request->input('selected_strategy');
        $chatId = $request->input('chat_id');
        $originalQuestion = $request->input('original_question');
        $sectionsBefore = $request->input('sections_before');
        $strategyMap = $request->input('strategy_map');
        $isUserSelection = $request->input('is_user_selection', false);

        if (! $selectedStrategy || ! $chatId || ! $originalQuestion) {
            return response()->json(['error' => 'Selected strategy, Chat ID, and original question are required.'], 400);
        }

        $systemMessage = $this->docs->buildSystemMessage($user);
        $systemMessage .= SearchUserChat::find($chatId)?->additionalContextBlock() ?? '';

        $prompt = <<<EOT
Strategy: "$selectedStrategy"
Goal: "$originalQuestion"

Generate these 4 sections concisely:

    🔮 Scenario Simulations
    Provide between 3 and 4 scenarios only. Each scenario must be on its own line starting with "-".
    Bold the scenario name (e.g., **Best Case:**) followed by 1-2 sentences.
    Ensure at least one best-case, one worst-case, and the remaining scenarios are realistic alternatives.
    Never return fewer than 3 or more than 4 scenarios.

    👥 Rephrased Goals by Role
    - Study the uploaded org/company documents in context.
    - Choose the sections/roles that are most relevant to the user's goal.
    - Output 5 to 10 roles only, numbered in order.
    - For each role: role name on one line, then "Goal:" line (1–2 sentences), then "Actions:" line with EXACTLY ONE sentence on the same line (no bullets, no dashes, no line breaks, no multiple sentences).
    - Only use role titles that actually appear in the company documents.

📌 Complementary Goals
2 goals, 1 sentence each.

✅ Final Outcome Summary
2 sentences on impact.

EOT;

        $provider = $this->ai->providerLabel();

        try {
            Log::info("{$provider} Request - Update Strategy", ['user_id' => $user->id, 'chat_id' => $chatId, 'selected_strategy' => $selectedStrategy]);

            $aiResponse = $this->ai->generate($systemMessage, $prompt, 3000);

            Log::info("{$provider} Response - Update Strategy", ['user_id' => $user->id, 'chat_id' => $chatId, 'status' => $aiResponse->status()]);
        } catch (\Throwable $e) {
            return $this->ai->handleException($e, 'Update Strategy', $user->id, $chatId);
        }

        if (! $aiResponse->successful()) {
            Log::error("{$provider} Unsuccessful Response - Update Strategy", ['user_id' => $user->id, 'chat_id' => $chatId]);

            return response()->json(['error' => "{$provider} API request failed.", 'details' => $aiResponse->body()], 500);
        }

        $updatedSections = $this->ai->extractText($aiResponse);
        $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);

        if ($isUserSelection) {
            $chat = SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->first();

            if ($chat) {
                $fullResponse = $sectionsBefore."\n\n".$strategyMap."\n\n".$updatedSections;

                $chat->update(['response' => $fullResponse, 'selected_strategy' => $selectedStrategy]);

                $latest = SearchUserChatData::where('search_user_chat_id', $chatId)
                    ->where('user_id', $user->id)
                    ->latest()
                    ->first();

                if ($latest) {
                    $latest->update(['response' => $fullResponse]);
                }
            }
        }

        return response()->json([
            'updated_sections' => $updatedSections,
            'selected_strategy' => $selectedStrategy,
            'chat_total_tokens' => $chatTotalTokens,
        ]);
    }

    public function users_new_chat_update_scenario(Request $request)
    {
        $user = auth()->user();
        $selectedScenario = $request->input('selected_scenario');
        $selectedStrategy = $request->input('selected_strategy');
        $chatId = $request->input('chat_id');
        $originalQuestion = $request->input('original_question');
        $sectionsBefore = $request->input('sections_before');
        $isUserSelection = $request->boolean('is_user_selection', false);

        if (! $selectedScenario || ! $chatId || ! $originalQuestion) {
            return response()->json(['error' => 'Selected scenario, Chat ID, and original question are required.'], 400);
        }

        $systemMessage = $this->docs->buildSystemMessage($user);

        $prompt = <<<EOT
Strategy Context: "{$selectedStrategy}"
Focused Scenario: "{$selectedScenario}"
Goal: "{$originalQuestion}"

Earlier sections:
{$sectionsBefore}

Regenerate these sections tailored to the selected scenario (and strategy if provided):

👥 Rephrased Goals by Role
- Study the uploaded org/company documents in context.
- Select the sections/roles most relevant to this scenario (and strategy, if provided).
- Output 5 to 10 roles only, numbered in order (1., 2., 3., ...).
- For each role: role name on one line, then "Goal:" line (1–2 sentences), then "Actions:" line with EXACTLY ONE sentence on the same line.
- Only use role titles that exist in the documents.
- Translate the goal into role-specific directions referencing the scenario and role responsibilities.
- Avoid OKR phrasing - use leadership-alignment language.
- Reference at least one dependency per role.

📌 Complementary Goals
2 goals, 1 sentence each.

✅ Final Outcome Summary
2 sentences describing the scenario's impact.

Keep the same emojis and section headers exactly as shown above.
EOT;

        $provider = $this->ai->providerLabel();

        try {
            Log::info("{$provider} Request - Update Scenario", ['user_id' => $user->id, 'chat_id' => $chatId, 'scenario' => $selectedScenario]);

            $aiResponse = $this->ai->generate($systemMessage, $prompt, 2500);

            Log::info("{$provider} Response - Update Scenario", ['user_id' => $user->id, 'chat_id' => $chatId, 'status' => $aiResponse->status()]);
        } catch (\Throwable $e) {
            return $this->ai->handleException($e, 'Update Scenario', $user->id, $chatId);
        }

        if (! $aiResponse->successful()) {
            $statusCode = $aiResponse->status();
            $clientError = $statusCode === 429
                ? "{$provider} API quota exceeded. Check your billing or reduce request rate."
                : "{$provider} API request failed.";

            Log::error("{$provider} Unsuccessful Response - Update Scenario", ['user_id' => $user->id, 'chat_id' => $chatId]);

            return response()->json(['error' => $clientError, 'details' => $aiResponse->json() ?? $aiResponse->body()],
                $statusCode >= 400 && $statusCode < 500 ? $statusCode : 500);
        }

        $updatedSections = $this->ai->extractText($aiResponse);
        $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);

        if ($isUserSelection) {
            SearchUserChat::where('id', $chatId)->update(['selected_scenario' => $selectedScenario]);
        }

        return response()->json([
            'updated_sections' => $updatedSections,
            'selected_scenario' => $selectedScenario,
            'chat_total_tokens' => $chatTotalTokens,
        ]);
    }

    public function users_new_chat_add_context(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $userId = $request->input('user_id');
        $additionalDetails = $request->input('additional_details', '');

        if (! $chatId || ! $userId) {
            return response()->json(['error' => 'Chat ID and User ID are required.'], 400);
        }

        $chat = SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->first();

        if (! $chat) {
            return response()->json(['error' => 'Chat session not found or access denied.'], 404);
        }

        try {
            $chat->appendAdditionalContext($additionalDetails);
        } catch (\Exception $e) {
            Log::error('Error saving additional context', ['error' => $e->getMessage(), 'chat_id' => $chatId]);

            return response()->json(['error' => 'Failed to save context: '.$e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Additional context saved successfully.',
            'context' => ! empty($additionalDetails) ? "Additional Details: {$additionalDetails}" : '',
        ]);
    }

    public function userschat_search_delete(Request $request, $id)
    {
        $deleted = SearchUserChat::where('id', $id)->where('user_id', auth()->id())->delete();

        if ($deleted) {
            SearchUserChatData::where('search_user_chat_id', $id)->delete();
        }

        flash(localize('Chat deleted successfully!'));

        return back();
    }

    public function user_view_chathistory(Request $request, $id)
    {
        $user = auth()->user();
        $userhistoryview = UserChatAnswer::where('id', $id)->where('user_id', $user->id)->first();

        return view('backend.pages.aiChat.user-view-chat-history', compact('user', 'userhistoryview'));
    }

    public function chatsearch_question(Request $request)
    {
        $user = auth()->user();
        $requestall = $request->all();

        $query = SubcategoryMenu::forRole($request->chat_role_categories)->forCategory($request->categories);

        $questionmenu = ! empty($request->subcategories)
            ? $query->where('subcategories', $request->subcategories)->first()
            : $query->first();

        if (! $questionmenu) {
            flash(localize('No Question Found'));

            return back();
        }

        $questionmenulist = $questionmenu->activeQuestions()->get();
        $useranswerdata = UserChatAnswer::where('user_id', $user->id)
            ->where('chat_role_categories', $request->chat_role_categories)
            ->where('categories', $request->categories)
            ->where('subcategories', $request->subcategories)
            ->first();

        return view('backend.pages.aiChat.newchat-question', compact('useranswerdata', 'user', 'questionmenu', 'questionmenulist', 'requestall'));
    }

    public function chat_question_store(Request $request)
    {
        $userId = $request->input('id');
        $questionIds = $request->input('question');
        $answers = $request->input('answers');
        $chatRole = $request->input('chat_role_categories');
        $category = $request->input('categories');
        $subcategory = $request->input('subcategories');
        $questionMenuId = $request->input('questionmenuid');

        $finalAnswers = [];

        foreach ($questionIds as $questionId) {
            if (isset($answers[$questionId])) {
                $finalAnswers[] = ['question_id' => $questionId, 'answer' => $answers[$questionId]];
            }
        }

        $encodedAnswers = json_encode($finalAnswers);
        $chatData = [
            'user_id' => $userId,
            'answers' => $encodedAnswers,
            'chat_role_categories' => $chatRole,
            'categories' => $category,
            'subcategories' => $subcategory,
            'questionmenuid' => $questionMenuId,
        ];

        $existing = UserChatAnswer::where('user_id', $userId)
            ->where('chat_role_categories', $chatRole)
            ->where('categories', $category)
            ->where('subcategories', $subcategory)
            ->first();

        if ($existing) {
            $existing->update(['status1' => 0, 'answers' => $encodedAnswers, 'questionmenuid' => $questionMenuId]);
        } else {
            UserChatAnswer::create(array_merge($chatData, ['status1' => 0]));
        }

        $newChat = SearchUserChat::create($chatData);

        flash(localize('Answers processed successfully.'));

        return redirect('dashboard/users-new-chat/'.$newChat->id);
    }

    // ---------------------------------------------------------------------------
    // Leadership Alignment Brief & Action Table
    // ---------------------------------------------------------------------------

    public function generate_leadership_alignment_brief(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $selectedStrategy = trim((string) $request->input('selected_strategy'));
        $selectedScenario = trim((string) $request->input('selected_scenario'));
        $originalQuestion = $request->input('original_question');
        $fullResponse = $request->input('full_response');

        if (! $chatId || ! $originalQuestion) {
            return response()->json(['error' => 'Chat ID and original question are required.'], 400);
        }

        if (! SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Chat not found or access denied.'], 403);
        }

        // Fall back to names from the stored contract if JS state is missing (e.g. history reload).
        if ($selectedStrategy === '' || $selectedScenario === '') {
            [$stratFromContract, $scenFromContract] = $this->resolveSelectionFromContract($chatId);

            if ($selectedStrategy === '' && $stratFromContract) {
                $selectedStrategy = $stratFromContract;
            }
            if ($selectedScenario === '' && $scenFromContract) {
                $selectedScenario = $scenFromContract;
            }
        }

        $systemMessage = $this->docs->buildSystemMessage($user, 'You are an executive strategy assistant. Provide executive-ready, consulting-style summaries.');

        $prompt = <<<EOT
Provide an executive-ready alignment summary in a clean, structured consulting format.

Context:
- Decision Chosen: "{$selectedStrategy}"
- Scenario Selected: "{$selectedScenario}"
- Goal: "{$originalQuestion}"

Full Analysis Context:
{$fullResponse}

Generate a Contextualized Alignment Brief with the following structure:

📋 CONTEXTUALIZED ALIGNMENT BRIEF

**Decision Chosen:** [Name of the selected decision path]
**Scenario Selected:** [Name of the selected scenario]

**Top 3 Risks:**
1. [Risk 1 with brief description]
2. [Risk 2 with brief description]
3. [Risk 3 with brief description]

**Top 3 Dependencies:**
1. [Dependency 1 - specific teams/roles/resources]
2. [Dependency 2 - specific teams/roles/resources]
3. [Dependency 3 - specific teams/roles/resources]

**Teams Impacted:** [List specific teams/roles that will be affected]
**Alignment Score:** [Low/Med/High] - [Brief rationale]
**Recommended Next Step for Leadership:** [1-2 sentences with specific, actionable recommendation]

Format: concise, executive-ready, consulting-style. For "Decision Chosen" and "Scenario Selected", infer from context if blank — NEVER output "Not provided".
EOT;

        try {
            $aiResponse = $this->ai->generate($systemMessage, $prompt, 2000);

            if (! $aiResponse->successful()) {
                return response()->json(['error' => 'Failed to generate alignment brief.', 'details' => $aiResponse->body()], 500);
            }

            $brief = $this->ai->extractText($aiResponse);
            $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);

            try {
                SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->update(['leadership_brief' => $brief]);
            } catch (\Exception $e) {
                Log::error('Failed to save leadership brief', ['chat_id' => $chatId, 'error' => $e->getMessage()]);

                return response()->json(['success' => true, 'brief' => $brief, 'chat_total_tokens' => $chatTotalTokens, 'warning' => 'Brief generated but could not be saved: '.$e->getMessage()]);
            }

            return response()->json(['success' => true, 'brief' => $brief, 'chat_total_tokens' => $chatTotalTokens]);
        } catch (\Exception $e) {
            Log::error('Leadership Alignment Brief Generation Failed', ['user_id' => $user->id, 'chat_id' => $chatId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while generating the alignment brief.', 'details' => $e->getMessage()], 500);
        }
    }

    public function generate_recommended_action_table(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $selectedStrategy = $request->input('selected_strategy');
        $selectedScenario = $request->input('selected_scenario');
        $originalQuestion = $request->input('original_question');
        $fullResponse = $request->input('full_response');
        $roleGoalsText = $request->input('role_goals_text');
        $checkOnly = filter_var($request->input('check_only', false), FILTER_VALIDATE_BOOLEAN);

        if (! $chatId) {
            return response()->json(['error' => 'Chat ID is required.'], 400);
        }

        if (! SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Chat not found or access denied.'], 403);
        }

        // First, check if expected states already exist for this chat ID
        $existingStates = ExpectedState::where('search_user_chat_id', $chatId)->get();
        if ($existingStates->isNotEmpty()) {
            $rows = $existingStates->map(function ($state) {
                return [
                    'id' => $state->id,
                    'role' => $state->role,
                    'action' => $state->recommended_action,
                    'decision' => $state->decision,
                    'success_metric' => $state->success_metric,
                    'target_value' => $state->target_value,
                    'target_date' => $state->target_date ? Carbon::parse($state->target_date)->toDateString() : null,
                    'resources_committed' => $state->resources_committed,
                    'depends_on_id' => $state->depends_on_id,
                ];
            })->all();

            return response()->json(['success' => true, 'rows' => $rows, 'from_cache' => true]);
        }

        if ($checkOnly) {
            return response()->json(['success' => false, 'rows' => []]);
        }

        if (! $originalQuestion) {
            return response()->json(['error' => 'Original question is required for generation.'], 400);
        }

        $systemMessage = $this->docs->buildSystemMessage($user, 'You are an executive strategy assistant. Return ONLY valid JSON. No markdown, no code fences, no commentary.');

        $prompt = <<<EOT
Based on the strategy work below, produce a Recommended Action Table.

Context:
- Strategy / Decision: "{$selectedStrategy}"
- Scenario: "{$selectedScenario}"
- Goal: "{$originalQuestion}"

Roles and goals already defined:
{$roleGoalsText}

Full analysis context:
{$fullResponse}

Output a JSON object with EXACTLY this shape:
{"rows":[{"role":"<role title>","action":"<one concrete recommended action sentence>"}]}

Rules:
- 5 to 8 rows. Use only roles that appear in the role goals / documents above.
- "action" = exactly ONE specific, decision-ready sentence (no bullets, no line breaks, no numbering).
- Tailor each action to the chosen scenario and strategy.
- Return ONLY the JSON object, nothing else.
EOT;

        try {
            $aiResponse = $this->ai->generate($systemMessage, $prompt, 1500, 0.7, true);

            if (! $aiResponse->successful()) {
                return response()->json(['error' => 'Failed to generate recommended action table.', 'details' => $aiResponse->body()], 500);
            }

            $text = $this->ai->extractText($aiResponse);
            $chatTotalTokens = $this->ai->recordChatTokens($chatId, $aiResponse);
            $rows = $this->parseRecommendedActionRows($text);

            if (empty($rows)) {
                return response()->json(['error' => 'No rows could be parsed from the AI response.', 'raw' => $text], 500);
            }

            // Persist the newly generated rows to the expected_states database table so they are saved
            foreach ($rows as &$row) {
                $state = ExpectedState::updateOrCreate(
                    [
                        'search_user_chat_id' => $chatId,
                        'role' => $row['role'],
                    ],
                    [
                        'recommended_action' => $row['action'],
                    ]
                );
                $row['id'] = $state->id;
                $row['decision'] = $state->decision;
                $row['success_metric'] = $state->success_metric;
                $row['target_value'] = $state->target_value;
                $row['target_date'] = $state->target_date ? Carbon::parse($state->target_date)->toDateString() : null;
                $row['resources_committed'] = $state->resources_committed;
                $row['depends_on_id'] = $state->depends_on_id;
            }

            return response()->json(['success' => true, 'rows' => $rows, 'chat_total_tokens' => $chatTotalTokens]);
        } catch (\Exception $e) {
            Log::error('Recommended Action Table Generation Failed', ['user_id' => $user->id, 'chat_id' => $chatId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while generating the recommended action table.', 'details' => $e->getMessage()], 500);
        }
    }

    public function save_expected_state(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $role = $request->input('role');
        $recommendedAction = $request->input('recommended_action');
        $decision = $request->input('decision');
        $successMetric = $request->input('success_metric');
        $targetValue = $request->input('target_value');
        $targetDate = $request->input('target_date');
        $resourcesCommitted = $request->input('resources_committed', false);
        $dependsOnId = $request->input('depends_on_id');

        if (! $chatId || ! $role || ! $recommendedAction || ! $decision) {
            return response()->json(['error' => 'Chat ID, role, action, and decision are required.'], 400);
        }

        // Verify ownership
        if (! SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Chat not found or access denied.'], 403);
        }

        try {
            $expectedState = ExpectedState::updateOrCreate(
                [
                    'search_user_chat_id' => $chatId,
                    'role' => $role,
                ],
                [
                    'recommended_action' => $recommendedAction,
                    'decision' => $decision,
                    'success_metric' => $successMetric,
                    'target_value' => $targetValue,
                    'target_date' => $targetDate ? Carbon::parse($targetDate)->toDateString() : null,
                    'resources_committed' => filter_var($resourcesCommitted, FILTER_VALIDATE_BOOLEAN),
                    'depends_on_id' => $dependsOnId ?: null,
                ]
            );

            return response()->json(['success' => true, 'expected_state' => $expectedState]);
        } catch (\Exception $e) {
            Log::error('Failed to save expected state', ['user_id' => $user->id, 'chat_id' => $chatId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while saving expected state.', 'details' => $e->getMessage()], 500);
        }
    }

    public function get_progress_data(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');

        if (! $chatId) {
            return response()->json(['error' => 'Chat ID is required.'], 400);
        }

        // Verify ownership
        if (! SearchUserChat::where('id', $chatId)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Chat not found or access denied.'], 403);
        }

        try {
            // Eager load the latest observation, latest intervention, and dependency info
            $states = ExpectedState::with(['latestObservation', 'latestIntervention', 'dependsOn.latestObservation'])
                ->where('search_user_chat_id', $chatId)
                ->where('decision', 'act_on_it') // Only retrieve active commitments
                ->get();

            $today = Carbon::now()->toDateString();
            $threshold = (float) config('oi.drift_threshold', 0.8);
            $alerts = [];

            foreach ($states as $state) {
                $obs = $state->latestObservation;
                $status = $obs ? $obs->status : 'Scheduled';

                // Quantitative achievement rate: actual vs target (e.g. 4 of
                // 10 partnerships = 0.4). Values are free text, so we compare
                // the first number found in each.
                $rate = null;
                $target = $this->extractNumeric($state->target_value);
                $actual = $obs ? $this->extractNumeric($obs->actual_value) : null;
                if ($target !== null && $target > 0 && $actual !== null) {
                    $rate = $actual / $target;
                }

                $isOverdue = $state->target_date && Carbon::parse($state->target_date)->toDateString() < $today;

                // ponytail: midpoint between commitment and deadline is the
                // point where "no progress yet" stops being normal.
                $pastMidpoint = false;
                if ($state->target_date && $state->created_at) {
                    $start = Carbon::parse($state->created_at);
                    $end = Carbon::parse($state->target_date)->endOfDay();
                    if ($end->greaterThan($start)) {
                        $pastMidpoint = Carbon::now()->getTimestamp() > ($start->getTimestamp() + $end->getTimestamp()) / 2;
                    }
                }

                $driftStatus = 'None';

                if ($status === 'Complete') {
                    if ($rate !== null && $rate < $threshold) {
                        $driftStatus = 'Capacity Drift'; // Delivered below committed target
                    }
                } elseif ($isOverdue) {
                    $driftStatus = 'Timeline Drift'; // Overdue
                } elseif ($state->depends_on_id && $state->dependsOn && $this->dependencyIsBlocked($state->dependsOn, $today)) {
                    $dep = $state->dependsOn;
                    $driftStatus = 'Dependency Blocked';
                    $alerts[] = "🔔 Alert: <strong>{$state->role}</strong> is blocked because <strong>{$dep->role}</strong> has not completed their task '<em>{$dep->recommended_action}</em>'.";
                } elseif (! $state->resources_committed) {
                    $driftStatus = 'Capacity Drift'; // Committed to work without budget/personnel
                } elseif ($pastMidpoint && ! $obs) {
                    $driftStatus = 'Priority Drift'; // No progress reported at all
                } elseif ($pastMidpoint && $rate !== null && $rate < $threshold) {
                    $driftStatus = 'Timeline Drift'; // Behind pace vs target
                }

                $state->drift_status = $driftStatus;
                $state->achievement_rate = $rate !== null ? round($rate, 2) : null;
                $state->drift_magnitude = $rate !== null ? round(max(0, 1 - $rate), 2) : null;
            }

            $this->recordDriftEvents($states);

            return response()->json([
                'success' => true,
                'states' => $states,
                'leadership_alerts' => $alerts,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch progress data', ['user_id' => $user->id, 'chat_id' => $chatId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while fetching progress data.', 'details' => $e->getMessage()], 500);
        }
    }

    private function extractNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', (string) $value), $m)) {
            return (float) $m[0];
        }

        return null;
    }

    private function dependencyIsBlocked(ExpectedState $dep, string $today): bool
    {
        $depObs = $dep->latestObservation;
        $depStatus = $depObs ? $depObs->status : 'Scheduled';
        $depIsOverdue = $dep->target_date && Carbon::parse($dep->target_date)->toDateString() < $today;

        return $depStatus === 'Blocked' || ($depStatus !== 'Complete' && $depIsOverdue);
    }

    /**
     * Persist drift transitions so there is an audit history of when each
     * commitment entered or recovered from drift. One row per state change.
     *
     * @param  Collection<int, ExpectedState>  $states
     */
    private function recordDriftEvents($states): void
    {
        if ($states->isEmpty()) {
            return;
        }

        // Ascending order + keyBy leaves the latest event per state.
        $lastEvents = DriftEvent::whereIn('expected_state_id', $states->pluck('id'))
            ->orderBy('id')
            ->get()
            ->keyBy('expected_state_id');

        foreach ($states as $state) {
            $prev = $lastEvents->get($state->id);
            $prevType = $prev ? $prev->drift_type : 'None';

            if ($state->drift_status === $prevType) {
                continue;
            }
            if ($state->drift_status === 'None' && ! $prev) {
                continue; // Nothing to record until first drift occurs
            }

            $magnitude = $state->drift_magnitude;
            $severity = null;
            if ($state->drift_status !== 'None') {
                if ($magnitude === null) {
                    $severity = 'Medium';
                } elseif ($magnitude >= 0.5) {
                    $severity = 'High';
                } elseif ($magnitude >= 0.2) {
                    $severity = 'Medium';
                } else {
                    $severity = 'Low';
                }
            }

            DriftEvent::create([
                'expected_state_id' => $state->id,
                'drift_type' => $state->drift_status,
                'magnitude' => $magnitude,
                'severity' => $severity,
                'detected_at' => Carbon::now(),
            ]);
        }
    }

    public function save_observed_state(Request $request)
    {
        $user = auth()->user();
        $expectedStateId = $request->input('expected_state_id');
        $actualValue = $request->input('actual_value');
        $status = $request->input('status'); // e.g. Scheduled, In Progress, Complete, Blocked
        $observationDate = $request->input('observation_date');
        $statusNotes = $request->input('status_notes');

        if (! $expectedStateId || ! $status || ! $observationDate) {
            return response()->json(['error' => 'Expected State ID, status, and observation date are required.'], 400);
        }

        try {
            // Verify ownership via a direct, type-safe query on the owning chat (mirrors
            // save_expected_state / get_progress_data). Relying on the Eloquent relationship
            // plus a strict `!==` comparison is fragile on MySQL, where numeric columns can
            // be returned as strings and fail a strict comparison against an integer user id.
            $expectedState = ExpectedState::find($expectedStateId);

            $ownsChat = $expectedState instanceof ExpectedState
                && SearchUserChat::where('id', $expectedState->search_user_chat_id)
                    ->where('user_id', $user->id)
                    ->exists();

            if (! $ownsChat) {
                return response()->json(['error' => 'Expected state not found or access denied.'], 403);
            }

            $observedState = ObservedState::create([
                'expected_state_id' => $expectedStateId,
                'actual_value' => $actualValue,
                'status' => $status,
                'observation_date' => Carbon::parse($observationDate)->toDateString(),
                'source' => 'Manual',
                'status_notes' => $statusNotes,
            ]);

            return response()->json(['success' => true, 'observed_state' => $observedState]);
        } catch (\Exception $e) {
            Log::error('Failed to save observed state', ['user_id' => $user->id, 'expected_state_id' => $expectedStateId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while saving progress.', 'details' => $e->getMessage()], 500);
        }
    }

    public function generate_intervention(Request $request)
    {
        $user = auth()->user();
        $expectedStateId = $request->input('expected_state_id');

        if (! $expectedStateId) {
            return response()->json(['error' => 'Expected State ID is required.'], 400);
        }

        try {
            // Verify ownership via a direct, type-safe query on the owning chat (see
            // save_observed_state for why the relationship-based strict check is avoided).
            $expectedState = ExpectedState::with(['latestObservation', 'dependsOn'])
                ->find($expectedStateId);

            $chat = $expectedState instanceof ExpectedState
                ? SearchUserChat::where('id', $expectedState->search_user_chat_id)
                    ->where('user_id', $user->id)
                    ->first()
                : null;

            if (! $chat) {
                return response()->json(['error' => 'Expected state not found or access denied.'], 403);
            }
            $obs = $expectedState->latestObservation;
            $status = $obs ? $obs->status : 'Scheduled';

            // Collect context for the AI prompt
            $goal = $chat->search ?: 'Increase Product Adoption';
            $role = $expectedState->role;
            $action = $expectedState->recommended_action;
            $metric = $expectedState->success_metric;
            $targetDate = $expectedState->target_date ? Carbon::parse($expectedState->target_date)->toDateString() : 'None';

            $statusNotes = $obs && $obs->status_notes ? $obs->status_notes : 'None provided';
            $actualValue = $obs && $obs->actual_value ? $obs->actual_value : 'None logged';

            // Determine specific blocker type
            $driftType = (string) $request->input('drift_type', '');
            $blockerDetail = 'Overdue/Delayed';
            if ($status === 'Blocked') {
                $blockerDetail = "Explicitly blocked with notes: {$statusNotes}";
            } elseif ($expectedState->depends_on_id && $expectedState->dependsOn) {
                $dep = $expectedState->dependsOn;
                $blockerDetail = "Blocked on the preceding role '{$dep->role}' completing their task '{$dep->recommended_action}'";
            } elseif ($driftType === 'Capacity Drift') {
                $blockerDetail = 'Capacity Drift: the team committed to this action without the budget/personnel to execute it, or delivered below the committed target.';
            } elseif ($driftType === 'Priority Drift') {
                $blockerDetail = 'Priority Drift: no progress has been logged on this commitment despite significant elapsed time, suggesting competing priorities.';
            }

            $systemMessage = 'You are an executive strategy intervention consultant. Given the context of a stalled organizational objective, recommend exactly one concrete, high-impact corrective action (2-3 sentences max) to resolve the bottleneck and get the team back on track.';

            $prompt = "STRATEGIC CONTEXT:\n"
                ."- Overall Business Goal: \"{$goal}\"\n"
                ."- Accountable Department/Role: {$role}\n"
                ."- Strategic Commitment / Objective Action: \"{$action}\"\n"
                ."- Target Success KPI: \"{$metric}\"\n"
                ."- Planned Target Date: {$targetDate}\n\n"
                ."CURRENT EXECUTION STATUS:\n"
                ."- Current Status of Task: {$status}\n"
                ."- Current Progress / Actual Logged Value: \"{$actualValue}\"\n"
                ."- Roadblock/Blocker Details: \"{$blockerDetail}\"\n"
                ."- Notes from the field: \"{$statusNotes}\"\n\n"
                ."TASK:\n"
                ."Recommend exactly one highly practical, tactical, and contextually specific intervention (2-3 sentences max) that the leadership can activate to unblock {$role} and accelerate delivery. Speak directly, confidently, and professionally.";

            // Call the application's configured AI Engine (Gemini / Vertex AI or OpenAI)
            $aiResponse = $this->ai->generate($systemMessage, $prompt, 1000);

            if (! $aiResponse->successful()) {
                return response()->json(['error' => 'AI Engine request failed.', 'details' => $aiResponse->body()], 500);
            }

            $recommendationText = $this->ai->extractText($aiResponse);
            $this->ai->recordChatTokens($chat->id, $aiResponse);

            // Store the intervention
            $intervention = Intervention::create([
                'expected_state_id' => $expectedStateId,
                'ai_recommendation' => trim($recommendationText),
                'status' => 'proposed',
            ]);

            return response()->json(['success' => true, 'intervention' => $intervention]);
        } catch (\Exception $e) {
            Log::error('Failed to generate intervention', ['user_id' => $user->id, 'expected_state_id' => $expectedStateId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while generating recommendation.', 'details' => $e->getMessage()], 500);
        }
    }

    public function activate_intervention(Request $request)
    {
        $user = auth()->user();
        $interventionId = $request->input('intervention_id');

        if (! $interventionId) {
            return response()->json(['error' => 'Intervention ID is required.'], 400);
        }

        try {
            // Verify ownership via a direct, type-safe query on the owning chat (see
            // save_observed_state for why the relationship-based strict check is avoided).
            $intervention = Intervention::with('expectedState')->find($interventionId);

            $ownsChat = $intervention instanceof Intervention
                && $intervention->expectedState instanceof ExpectedState
                && SearchUserChat::where('id', $intervention->expectedState->search_user_chat_id)
                    ->where('user_id', $user->id)
                    ->exists();

            if (! $ownsChat) {
                return response()->json(['error' => 'Intervention not found or access denied.'], 403);
            }

            $intervention->update([
                'status' => 'active',
                'activated_at' => Carbon::now(),
            ]);

            return response()->json(['success' => true, 'intervention' => $intervention]);
        } catch (\Exception $e) {
            Log::error('Failed to activate intervention', ['user_id' => $user->id, 'intervention_id' => $interventionId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'An error occurred while activating intervention.', 'details' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------------------
    // Export
    // ---------------------------------------------------------------------------

    public function export_role_goals(Request $request)
    {
        $user = auth()->user();
        $roleGoalsText = $request->input('role_goals_text');
        $goal = $request->input('goal', '');
        $scenario = $request->input('scenario', '');
        $strategy = $request->input('strategy', '');

        if (! $roleGoalsText) {
            return response()->json(['error' => 'Role goals text is required.'], 400);
        }

        $roleGoals = $this->parseRoleGoalsFromText($roleGoalsText);

        if (empty($roleGoals)) {
            return response()->json(['error' => 'No role goals found to export.'], 400);
        }

        try {
            return Excel::download(new RoleGoalsExport($roleGoals, $goal, $scenario, $strategy), 'role_goals_'.date('Y-m-d_His').'.xlsx');
        } catch (\Exception $e) {
            Log::error('Role Goals Export Failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to export role goals.', 'details' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------------------

    private function resolveAdditionalContext(Request $request, SearchUserChat $chat, $additionalContext): string
    {
        if ($additionalContext && is_array($additionalContext) && ! empty($additionalContext['additional_details'])) {
            try {
                $chat->appendAdditionalContext($additionalContext['additional_details']);
            } catch (\Exception $e) {
                Log::error('Error saving context to database', ['error' => $e->getMessage()]);
            }

            return "\n\n--- ADDITIONAL USER CONTEXT (FROM THIS REQUEST) ---\n"
                .'Additional Details: '.$additionalContext['additional_details']
                ."\n--- END ADDITIONAL USER CONTEXT ---\n";
        }

        return $chat->additionalContextBlock();
    }

    /**
     * Coerce an assumptions payload (array or JSON string) into a clean list of
     * non-empty, trimmed assumption strings.
     */
    private function normalizeAssumptions($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($a) {
            return is_string($a) ? trim($a) : (is_scalar($a) ? trim((string) $a) : '');
        }, $value), fn ($a) => $a !== ''));
    }

    /**
     * Load the stored GoalSync JSON contract for a chat, apply a mutation to the
     * decoded array, and save it back to the row that holds the contract (and to
     * SearchUserChat.response when it still holds the contract). Used to persist
     * per-pathway assumptions and simulations into the existing response JSON.
     */
    private function persistContractMutation($chatId, $userId, callable $mutate): void
    {
        $row = SearchUserChatData::where('search_user_chat_id', $chatId)
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get()
            ->first(fn ($r) => str_starts_with(ltrim((string) $r->response), '{'));

        if (! $row) {
            return;
        }

        $data = json_decode((string) $row->response, true);

        if (! is_array($data)) {
            return;
        }

        $mutate($data);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $row->response = $json;
        $row->save();

        $chat = SearchUserChat::find($chatId);
        if ($chat && str_starts_with(ltrim((string) $chat->response), '{')) {
            $chat->response = $json;
            $chat->save();
        }
    }

    private function resolveSelectionFromContract($chatId): array
    {
        $candidates = SearchUserChatData::where('search_user_chat_id', $chatId)
            ->orderBy('id')
            ->pluck('response')
            ->all();

        $main = SearchUserChat::where('id', $chatId)->value('response');

        if ($main) {
            $candidates[] = $main;
        }

        foreach ($candidates as $resp) {
            $data = $this->ai->parseJson((string) $resp);

            if (! is_array($data) || empty($data['strategyMap'])) {
                continue;
            }

            $selStratId = $data['selectedStrategyId'] ?? ($data['strategyMap'][0]['id'] ?? null);
            $strategyName = null;

            foreach ($data['strategyMap'] as $s) {
                if (is_array($s) && ($s['id'] ?? null) === $selStratId) {
                    $strategyName = $s['name'] ?? null;
                    break;
                }
            }

            $strategyName = $strategyName ?? ($data['strategyMap'][0]['name'] ?? null);
            $scenarioLabel = null;
            $variant = $data['strategyVariants'][$selStratId] ?? null;

            if (is_array($variant)) {
                $selScenId = $variant['selectedScenarioId'] ?? ($variant['scenarios'][0]['id'] ?? null);

                foreach (($variant['scenarios'] ?? []) as $sc) {
                    if (is_array($sc) && ($sc['id'] ?? null) === $selScenId) {
                        $scenarioLabel = $sc['label'] ?? null;
                        break;
                    }
                }

                $scenarioLabel = $scenarioLabel ?? ($variant['scenarios'][0]['label'] ?? null);
            }

            return [$strategyName, $scenarioLabel];
        }

        return [null, null];
    }

    private function parseRecommendedActionRows($text): array
    {
        $data = $this->ai->parseJson($text);
        $rows = [];

        if (is_array($data) && isset($data['rows']) && is_array($data['rows'])) {
            foreach ($data['rows'] as $r) {
                $role = isset($r['role']) ? trim($r['role']) : '';
                $action = isset($r['action']) ? trim(preg_replace('/\s+/', ' ', $r['action'])) : '';

                if ($role !== '' && $action !== '') {
                    $rows[] = ['role' => $role, 'action' => $action];
                }
            }
        }

        return $rows;
    }

    private function parseRoleGoalsFromText($text): array
    {
        $roleGoals = [];
        $currentRole = null;
        $currentGoal = '';
        $currentActions = [];
        $mode = null;

        $flush = function () use (&$roleGoals, &$currentRole, &$currentGoal, &$currentActions) {
            if ($currentRole && (trim($currentGoal) !== '' || ! empty($currentActions))) {
                $roleGoals[] = ['role' => $currentRole, 'goal' => trim($currentGoal), 'actions' => implode("\n", $currentActions), 'notes' => ''];
            }
        };

        foreach (explode("\n", $text) as $line) {
            $line = trim($line);

            if (empty($line) || strpos($line, '👥') !== false || stripos($line, 'Rephrased Goals') !== false) {
                continue;
            }

            if (preg_match('/^Goal:\s*(.+)$/i', $line, $m)) {
                $currentGoal = trim($m[1]);
                $mode = 'goal';

                continue;
            }

            if (preg_match('/^Actions:\s*(.*)$/i', $line, $m)) {
                $mode = 'actions';
                if (trim($m[1]) !== '') {
                    $currentActions[] = trim($m[1]);
                }

                continue;
            }

            if (preg_match('/^[-•*]\s*(.+)$/', $line, $m)) {
                if ($mode === 'actions') {
                    $currentActions[] = trim($m[1]);
                } elseif ($mode === 'goal') {
                    $currentGoal .= ' '.trim($m[1]);
                }

                continue;
            }

            if (preg_match('/^(\d+\.?\s*)?([A-Z][^:]+?):?\s*$/', $line, $m)) {
                $flush();
                $currentRole = trim($m[2]);
                $currentGoal = '';
                $currentActions = [];
                $mode = 'role';

                continue;
            }

            if ($currentRole) {
                if ($mode === 'actions') {
                    $currentActions[] = $line;
                } else {
                    $currentGoal = trim(($currentGoal !== '' ? $currentGoal.' ' : '').$line);
                    $mode = 'goal';
                }
            }
        }

        $flush();

        return $roleGoals;
    }
}
