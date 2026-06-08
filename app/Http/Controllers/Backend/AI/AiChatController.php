<?php



namespace App\Http\Controllers\Backend\AI;



use App\Models\AiChat;

use App\Mail\EmailManager;

use App\Models\AiChatPrompt;

use Illuminate\Http\Request;

use App\Models\AiChatMessage;

use App\Models\Document;

use Orhanerday\OpenAi\OpenAi;

use App\Models\AiChatCategory;

use App\Models\AiChatPromptGroup;

use App\Services\WriteBotService;

use App\Models\SubscriptionPackage;

use App\Http\Controllers\Controller;

use App\Http\Services\SerperService;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Session;

use App\Notifications\EmailChatMessages;

use App\Services\Integration\IntegrationService;
use App\Exports\RoleGoalsExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;

use Carbon\Carbon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

use PhpOffice\PhpWord\IOFactory;



class AiChatController extends Controller

{

    public function __construct()

    {

        if (getSetting('enable_ai_chat') == '0') {

            flash(localize('AI chat is not available'))->info();

            redirect()->route('writebot.dashboard')->send();

        }

    }

    # Gemini generate: primary gemini-3.1-pro-preview, fallback gemini-3.5-flash-lite
    private function geminiGenerate($systemMessage, $userText, $maxOutputTokens = 3000, $temperature = 0.7, $jsonMode = false)
    {
        $models = ['gemini-3.1-pro-preview', 'gemini-3.5-flash-lite'];

        $generationConfig = [
            'temperature' => $temperature,
            'maxOutputTokens' => $maxOutputTokens,
        ];

        // Ask Gemini for a raw JSON object (no markdown fences) when requested.
        if ($jsonMode) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemMessage]]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userText]]
                ]
            ],
            'generationConfig' => $generationConfig,
        ];

        $response = null;

        foreach ($models as $model) {
            $response = Http::withHeaders(['x-goog-api-key' => env('GEMINI_API_KEY')])
                ->timeout(90)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

            if ($response->successful()) {
                return $response;
            }

            Log::warning('Gemini model failed, trying fallback', [
                'model'  => $model,
                'status' => $response->status(),
            ]);
        }

        return $response; // last failed response
    }

    # AI provider dispatcher. Choose model via .env:
    #   AI_PROVIDER="gemini"  -> gemini-3.5-flash-lite (geminiGenerate)
    #   AI_PROVIDER="openai"  -> OPENAI_MODEL (default gpt-4-turbo)
    private function aiGenerate($systemMessage, $userText, $maxOutputTokens = 3000, $temperature = 0.7, $jsonMode = false)
    {
        $provider = strtolower(env('AI_PROVIDER', 'gemini'));

        if ($provider === 'openai' || $provider === 'chatgpt') {
            return $this->openAiGenerate($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);
        }

        return $this->geminiGenerate($systemMessage, $userText, $maxOutputTokens, $temperature, $jsonMode);
    }

    # OpenAI generate. Returns a Response normalized to Gemini's JSON shape
    # (candidates.0.content.parts.0.text) so all callers stay unchanged.
    private function openAiGenerate($systemMessage, $userText, $maxOutputTokens = 3000, $temperature = 0.7, $jsonMode = false)
    {
        $model = env('OPENAI_MODEL', 'gpt-4-turbo');

        // gpt-4-turbo caps completion at 4096 tokens; clamp so larger Gemini
        // budgets (e.g. 8000) don't 400 when running on OpenAI.
        $maxOutputTokens = min((int) $maxOutputTokens, (int) env('OPENAI_MAX_TOKENS', 4096));

        $payload = [
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user',   'content' => $userText],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $maxOutputTokens,
        ];

        // Force a valid JSON object response when requested (OpenAI JSON mode).
        if ($jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->timeout(90)
            ->connectTimeout(10)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($response->successful()) {
            $text = $response->json('choices.0.message.content', '');
            $normalized = ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];

            return new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode($normalized))
            );
        }

        Log::warning('OpenAI model failed', [
            'model'  => $model,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response; // failed response, callers handle status
    }



    # chat index

    public function index(Request $request, WriteBotService $writeBotService)

    {



        $searchKey = null;

        $user = user();

        if (isCustomer()) {

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            if ($package->allow_ai_chat == 0) {

                abort(403);

            }

        } else {

            if (!auth()->user()->can('ai_chat')) {

                abort(403);

            }

        }



        $chatExpertIds = [];

        $conditions = [['type', 'chat']];

        if (!isCustomer()) {

            $chatExpertIds = $writeBotService->getAiChatCategories(null, null, $conditions);

            $chatExperts   = $writeBotService->getAiChatCategories(true, 1, $conditions);

        } else {

            $chatExpertIds = $writeBotService->getAiChatCategories(null, 1, $conditions);

            $chatExperts   = $writeBotService->getAiChatCategories(true, 1, $conditions);

        }





        $chatListQuery = AiChat::orderBy('updated_at', 'DESC')->with('messages', 'category')->where('user_id', $user->id)->whereIn('ai_chat_category_id', $chatExpertIds);



        if (!empty($request->search)) {

            $chatListQuery = $chatListQuery->where('title', 'like', '%' . $request->search . '%');

            $searchKey = $request->search;

        }



        if (!empty($request->expert)) {

            $chatList     = $chatListQuery->where('ai_chat_category_id', $request->expert)->get();

        } else {

            $chatList     = $chatListQuery->where('ai_chat_category_id', 1)->get();

        }







        $promptGroups       = AiChatPromptGroup::oldest();

        $promptGroups       = $promptGroups->get();

        $prompts            = AiChatPrompt::latest()->get();



        $conversation = $chatListQuery->first();

        // Get user's documents count and parsed texts for context
        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
        
        $documentCount = $documents->count();
        $documentContext = $documents->pluck('parsed_text')->filter()->implode("\n\n--- Document Separator ---\n\n");

        return view('backend.pages.aiChat.index', compact('chatExperts', 'chatList', 'conversation', 'searchKey', 'promptGroups', 'prompts', 'documentCount', 'documentContext'));

    }



    # new conversation

    public function store(Request $request)

    {

        $user = user();

        if (isCustomer()) {

            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            if ($package->allow_ai_chat == 0) {

                $data = [

                    'status'  => 400,

                    'success' => false,

                    'message' => localize('AI Chat is not available in this package, please upgrade you plan'),

                ];

                return $data;

            }

        }

        $expert = AiChatCategory::query()->find($request->ai_chat_category_id);



        /* When Expert is empty response a error json */

        if(empty($expert)){

            return  [

                'status'                => 400,

                'ai_chat_category_id'   => $request->ai_chat_category_id,

                'success'               => false,

                'message'               => localize('Expert not found'),

            ];

        }



        $conversation                      = new AiChat;

        $conversation->user_id             = $user->id;

        $conversation->ai_chat_category_id = $request->ai_chat_category_id;

        $conversation->title               = $expert->name . localize(' Chat');

        $conversation->save();



        $message = new AiChatMessage;

        $message->ai_chat_id = $conversation->id;

        $message->user_id    = $user->id;

        if ($expert->role == 'default') {

            $result =  localize("Hello! I am $expert->name, and I'm here to answer your all questions.");

        } else {

            $result =  localize("Hello! I am $expert->name, and I'm $expert->role. $expert->assists_with.");

        }

        $message->response   = $result;

        $message->result   = $result;

        $message->save();



        $chatList = AiChat::latest();

        $chatList = $chatList->where('ai_chat_category_id', $expert->id)->where('user_id', $user->id)->get();



        $promptGroups       = AiChatPromptGroup::oldest();

        $promptGroups       = $promptGroups->get();

        $prompts            = AiChatPrompt::latest()->get();

        // Get user's documents count for display
        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
        
        $documentCount = $documents->count();

        $data = [

            'status'                 => 200,

            'chatList'               => view('backend.pages.aiChat.inc.chat-list', compact('chatList'))->render(),

            'messagesContainer'      => view('backend.pages.aiChat.inc.messages-container', compact('conversation', 'promptGroups', 'prompts', 'documentCount'))->render(),

        ];

        return $data;

    }



    # update conversation

    public function update(Request $request)

    {

        $conversation = AiChat::whereId((int) $request->chatId)->first();

        $conversation->title = $request->value;

        $conversation->save();

    }



    # delete conversation

    public function delete($id)

    {

        $conversation = AiChat::findOrFail((int)$id);

        AiChatMessage::where('ai_chat_id', $conversation->id)->delete();

        $conversation->delete();

        flash(localize('Chat has been deleted successfully'))->success();

        return back();

    }



    # new message

    public function newMessage(Request $request)

    {



        $chat = AiChat::where('id', (int) $request->chat_id)->first(); // TODO Required Existance checking

        $category = AiChatCategory::where('id', $request->category_id)->first();



        $user = auth()->user();



        // check word limit; need to have min 10 words balance

        if (isCustomer() && availableDataCheck('words') <= 10) {

            $data = [

                'status'                => 400,

                'ai_chat_category_id'   => $request->category_id,

                'success'               => false,

                'message'               => localize('Your word balance is low, please upgrade you plan'),

            ];



            return $data;

        }





        $prompt = $request->prompt; // TODO Required

        $total_used_tokens = 0;



        $message                = new AiChatMessage;

        $message->ai_chat_id    = $chat->id;

        $message->user_id       = $user->id;

        $message->prompt        = $prompt;

        $message->result        = $prompt;

        $message->save();



        $message->aiChat->touch(); // updated at



        $chat_id = $chat->id;

        $message_id = $message->id;



        $request->session()->put('chat_id', $chat_id);

        $request->session()->put('message_id', $message_id);

        $request->session()->put('category_id', $request->category_id);

        $request->session()->put('real_time_data', $request->real_time_data == 1 ? 1 :null);



        $data = [

            'status'              => 200,

            'ai_chat_category_id' => $request->category_id,

            'success'             => false,

            'message'             => '',

        ];

        return $data;

    }



    # ai response

    public function process()

    {

        $request            = request();

        $integrationService = new IntegrationService();



        $request->merge([

            'stream'        => true,

            'content_type'  => 'ai_chat'

        ]);

        

        return $integrationService->contentGenerator(aiChatEngine(), $request);

    }

    

    # updateUserWords - take token as word

    public function updateUserWords($tokens, $user)

    {

        if ($user->user_type == "customer") {

            updateDataBalance('words', $tokens, $user);

        }

    }

    # updateBalanceStopGeneration

    public function updateBalanceStopGeneration(Request $request)

    {

        $random_number = session()->get('random_number');

        $user = user();

        if ($random_number && isCustomer()) {

            $aiChatMessage = AiChatMessage::where('random_number', $random_number)->where('user_id', $user->id)->first();

            if ($aiChatMessage) {

                $words = $aiChatMessage->words;

                $this->updateUserWords($words, $user);

                session()->forget('random_number');

                return response()->json(['success' => true]);

            }

        }



        return response()->json(['success' => false]);

    }

    # get messages

    public function getMessages(Request $request)

    {

        $conversation = AiChat::whereId((int) $request->chatId)->first();

        if (is_null($conversation)) {

            $data = [

                'status' => 400

            ];

            return $data;

        }





        $promptGroups       = AiChatPromptGroup::oldest();

        $promptGroups       = $promptGroups->get();

        $prompts            = AiChatPrompt::latest()->get();

        // Get user's documents count for display
        $user = auth()->user();
        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
        
        $documentCount = $documents->count();

        $data = [

            'status'            => 200,

            'messagesContainer' => view('backend.pages.aiChat.inc.messages-container', compact('conversation', 'promptGroups', 'prompts', 'documentCount'))->render(),

        ];

        return $data;

    }



    # get conversations

    public function getConversations(Request $request)

    {

        $conversationsQuery = AiChat::where('ai_chat_category_id', (int) $request->ai_chat_category_id)->where('user_id', auth()->user()->id)->latest('updated_at');



        $chatList = $conversationsQuery->get();

        $conversation = $conversationsQuery->first();





        $promptGroups       = AiChatPromptGroup::oldest();

        $promptGroups       = $promptGroups->get();

        $prompts            = AiChatPrompt::latest()->get();

        $ai_chat_category_id = $request->ai_chat_category_id;

        $data = [

            'status'                 => 200,

            'ai_chat_category_id'   => $ai_chat_category_id,

            'chatRight'      => view('backend.pages.aiChat.inc.chat-right', compact('conversation', 'chatList', 'conversation', 'promptGroups', 'prompts'))->render(),

        ];

        return $data;

    }



    # SEND IN EMAIL

    public function sendInEmail(Request $request)

    {

        if ($request->email == null) {

            flash(localize('Please type an email'))->error();

            return back();

        }



        $conversation = AiChat::findOrFail((int) $request->conversation_id);

        if (is_null($conversation)) {

            flash(localize('Chat not found'))->error();

            return back();

        }



        try {

            $array['view'] = 'emails.chat';

            $array['from'] = env('MAIL_FROM_ADDRESS');

            $array['subject'] = $conversation->title;

            $array['conversation'] = $conversation;

            $array['messages'] = $conversation->messages;



            Mail::to($request->email)->queue(new EmailManager($array));

            flash(localize('Chat successfully sent to email'))->success();

        } catch (\Throwable $th) {

            flash($th->getMessage())->error();

        }

        return back();

    }

    // download, copy chat history

    public function downloadChatHistory(Request $request)

    {



        try {

            $basePath = public_path('/');

            $type = $request->type;

            $conversation = AiChat::whereId((int) $request->chatId)->with('messages')->first();

            $messages = null;

            $name   = $conversation->category ? $conversation->category->name : 'ai_chat';



            if ($conversation) {

                $messages  = $conversation->messages;

            }



            if (!$messages) {

                flash(localize('No Message Fund'));

                return redirect()->back();

            }

            $data = ['messages' => $messages, 'conversation' => $conversation, 'type' => $type];

            if ($type == 'html') {

                $name =  str_replace(' ', '_', $name) . '.html';

                $file_path = $basePath . $name;

                if (file_exists($file_path)) {

                    unlink($file_path);

                }



                $view = view('backend.pages.aiChat.download.AI_ChatBot', $data)->render();

                file_put_contents($file_path, $view);

                return response()->download($file_path);

            }

            if ($type == 'word') {

                $name =  str_replace(' ', '_', $name) . '.doc';

                $file_path = $basePath . $name;

                if (file_exists($file_path)) {

                    unlink($file_path);

                }



                $view = view('backend.pages.aiChat.download.AI_ChatBot', $data)->render();

                file_put_contents($file_path, $view);

                return response()->download($file_path);

            }

            if ($type == 'pdf') {

                return  view('backend.pages.aiChat.download.AI_ChatBot', $data);

            }



            if ($type == 'copyChat') {

                return  view('backend.pages.aiChat.download.copyChat', $data);

            }

        } catch (\Throwable $th) {

            throw $th;

        }

    }




      public function newchat(Request $request)
    {
        $user = auth()->user();

        $chatrolecategories = DB::table('chat_role_categories')->where('status',1)->get();
        $chatcategories = DB::table('chat_categories')->where('status',1)->where('role_name',$user->chat_role_categories)->get();

        return view('backend.pages.aiChat.newchat', compact('user','chatrolecategories','chatcategories'));
    }


      public function userchathistory(Request $request)
    {
        $user = auth()->user();
        $userhistorydata = DB::table('user_chat_answers')->where('user_id', $user->id)->get();
        return view('backend.pages.aiChat.user-chat-history', compact('user','userhistorydata'));
    }


    public function newusers_new_chat(Request $request)
{
    $userId = auth()->user();

    // $newChatchatdata = DB::table('search_user_chat')->where('id',$id)->where('user_id',$userId->id)->first();

    $newChat = DB::table('search_user_chat')->insertGetId([
        'user_id' => $userId->id,
        'status1' => 0,
        // 'answers' => $newChatchatdata->answers ?? '',
        // 'chat_role_categories' => $newChatchatdata->chat_role_categories ?? '',
        // 'categories' => $newChatchatdata->categories ?? '',
        // 'subcategories' => $newChatchatdata->subcategories ?? '',
        // 'questionmenuid' => $newChatchatdata->questionmenuid ?? '',
    ]); 

    flash(localize('New Chat.'));
    return redirect('dashboard/users-new-chat/'.$newChat);
}


      public function users_new_chat(Request $request,$id)
    {
        $searchKey = null;

        $user = auth()->user();

        $promptGroups       = AiChatPromptGroup::oldest();

        $promptGroups       = $promptGroups->get();

        $prompts            = AiChatPrompt::latest();



        if ($request->search != null) {

            $prompts = $prompts->where('title', 'like', '%' . $request->search . '%')->orWhere('prompt', 'like', '%' . $request->search . '%');

            $searchKey = $request->search;

        }



        $prompts = $prompts->get();

        $user = auth()->user();

        /*$searchuserchatdata = DB::table('search_user_chat')->where('user_id',$user->id)->get();*/
        $searchuserchatdata = DB::table('search_user_chat_data')->where('search_user_chat_id',$id)->where('user_id',$user->id)->get();

       

        $today = Carbon::now()->startOfDay();
        $yesterday = Carbon::yesterday()->startOfDay();
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();
        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();

        $searchuserchatdatanew = DB::table('search_user_chat_data')
            ->where('user_id', $user->id)
            ->get()
            ->groupBy(function ($item) use ($today, $sevenDaysAgo,$yesterday, $thirtyDaysAgo) {
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

        // Get user's documents count for display
        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
        
        $documentCount = $documents->count();

        // Get selected strategy from database if exists (for page reload)
        $chatRecord = DB::table('search_user_chat')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        
        \Log::info('Loading chat page - checking for brief in database', [
            'chat_id' => $id,
            'user_id' => $user->id,
            'chat_record_exists' => !is_null($chatRecord)
        ]);
        
        $selectedStrategyFromDB = null;
        $leadershipBriefFromDB = null;
        if ($chatRecord) {
            // Try to get selected_strategy column if it exists
            try {
                $columns = DB::select("SHOW COLUMNS FROM search_user_chat LIKE 'selected_strategy'");
                if (count($columns) > 0 && isset($chatRecord->selected_strategy)) {
                    $selectedStrategyFromDB = $chatRecord->selected_strategy;
                }
            } catch (\Exception $e) {
                // Column doesn't exist, that's okay - we'll try to add it when saving
            }
            
            // Try to get leadership_brief column if it exists
            try {
                $columns = DB::select("SHOW COLUMNS FROM search_user_chat LIKE 'leadership_brief'");
                \Log::info('Checking leadership_brief column', [
                    'chat_id' => $id,
                    'column_exists' => count($columns) > 0,
                    'has_leadership_brief_property' => isset($chatRecord->leadership_brief),
                    'leadership_brief_value' => $chatRecord->leadership_brief ?? 'NULL',
                    'leadership_brief_length' => strlen($chatRecord->leadership_brief ?? ''),
                    'leadership_brief_empty' => empty($chatRecord->leadership_brief ?? '')
                ]);
                
                if (count($columns) > 0 && isset($chatRecord->leadership_brief) && !empty($chatRecord->leadership_brief)) {
                    $leadershipBriefFromDB = $chatRecord->leadership_brief;
                    \Log::info('✅ Leadership Brief retrieved from database', [
                        'chat_id' => $id,
                        'brief_length' => strlen($leadershipBriefFromDB),
                        'brief_preview' => substr($leadershipBriefFromDB, 0, 200)
                    ]);
                } else {
                    \Log::warning('❌ Leadership Brief NOT found in database', [
                        'chat_id' => $id,
                        'column_exists' => count($columns) > 0,
                        'has_brief' => isset($chatRecord->leadership_brief),
                        'brief_empty' => empty($chatRecord->leadership_brief ?? ''),
                        'brief_value' => $chatRecord->leadership_brief ?? 'NULL'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error checking leadership_brief column', [
                    'chat_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Column doesn't exist, that's okay
            }
        } else {
            \Log::warning('Chat record not found', [
                'chat_id' => $id,
                'user_id' => $user->id
            ]);
        }

        return view('backend.pages.aiChat.users-new-chat', compact('user','promptGroups', 'prompts','searchKey','searchuserchatdata','id','searchuserchatdatanew', 'documentCount', 'selectedStrategyFromDB', 'leadershipBriefFromDB'));
    }





    //   public function users_new_chat_ask(Request $request)
    // {
    //     $user = auth()->user();
    //     $question = $request->input('question');
    //     $userid = $request->input('user_id');
    //     $chatid = $request->input('chat_id');

    //     $checkdata = DB::table('user_chat_answers')->where('user_id',$userid)->latest()->first();
          
    //     $response = Http::withToken('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')
    //             ->post('https://api.openai.com/v1/chat/completions', [
    //                 'model' => 'gpt-3.5-turbo',
    //                 'messages' => [
    //                     ['role' => 'user', 'content' => $question],
    //                 ],
    //             ]);

    //         $answer = $response->json('candidates.0.content.parts.0.text');

    //         // Save to DB

    //         $newChat = DB::table('search_user_chat')->where('id',$chatid)->update([
    //             'user_id' => $user->id,
    //             'answers' => $checkdata->answers,
    //             'chat_role_categories' => $checkdata->chat_role_categories,
    //             'categories' => $checkdata->categories,
    //             'subcategories' => $checkdata->subcategories,
    //             'questionmenuid' => $checkdata->questionmenuid,
    //             'search' => $question,
    //             'response' => $answer,
    //         ]);

    //         DB::table('search_user_chat_data')->insert([
    //             'search_user_chat_id' => $chatid,
    //             'user_id' => $user->id,
    //             'answers' => $checkdata->answers,
    //             'chat_role_categories' => $checkdata->chat_role_categories,
    //             'categories' => $checkdata->categories,
    //             'subcategories' => $checkdata->subcategories,
    //             'questionmenuid' => $checkdata->questionmenuid,
    //             'search' => $question,
    //             'response' => $answer,
    //         ]);

    //         return response()->json([
    //             'question' => $question,
    //             'answer' => $answer,
    //             'id' => $newChat
    //         ]);
    // }




// public function users_new_chat_ask(Request $request)
// {
//     $user = auth()->user();
//     $question = $request->input('question');
//     $chatId = $request->input('chat_id');

//     // Validate input
//     if (!$question || !$chatId) {
//         return response()->json(['error' => 'Question and Chat ID are required.'], 400);
//     }

//     // Fetch last context
//     $previous = DB::table('user_chat_answers')->where('user_id', $user->id)->latest()->first();

//     // GoalSync instruction (manually embedded)
//     $goalsyncInstructions = <<<TEXT
//             OBJECTIVE:
//             Respond to every user-inputted goal using the exact 7-step GoalSync structure. Never skip a step...

//             🧩 Step 1: Chat Acknowledgement  
//             📁 Step 2: Document Insights  
//             📊 Step 3: Goal Assessment Summary  
//             📈 Step 4: Scoring (Impact, Feasibility, Alignment)  
//             🗺️ Step 5: Strategy Map (Decision Paths)  
//             🔮 Step 6: Scenario Simulations  
//             👥 Step 7: Rephrased Goals by Role  
//             📌 Complementary Goals (if applicable)  
//             ✅ Final Outcome Summary

//             Rules:
//             - Do not skip any step
//             - Use fallback logic if data is missing
//             - Keep tone strategic, professional, and conversational
//             TEXT;

//                 // Construct prompt
//                 $prompt = <<<EOT
//             You are a strategic assistant trained in the GoalSync method.

//             Below is the instruction set you MUST follow:
//             $goalsyncInstructions

//             Now respond to the user's goal using the GoalSync format below.

//             User's Goal:
//             "$question"
//             EOT;

//         $response = Http::withToken('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')->post('https://api.openai.com/v1/chat/completions', [
//             'model' => 'gpt-3.5-turbo',
//             'messages' => [
//                 ['role' => 'user', 'content' => $prompt],
//             ],
//         ]);

//         if (!$response->successful()) {
//             return response()->json([
//                 'error' => 'OpenAI API failed.',
//                 'details' => $response->body()
//             ], 500);
//         }

//         $answer = $response->json('candidates.0.content.parts.0.text');
//     // dd($answer);
//        /* $responseData = preg_replace('/\/\/.*$/', '', $answer);
         
//         $responseData = mb_convert_encoding($answer, 'UTF-8', 'UTF-8');*/

//         DB::table('search_user_chat')->where('id', $chatId)->update([
//             'user_id' => $user->id,
//             'answers' => $previous->answers ?? null,
//             'chat_role_categories' => $previous->chat_role_categories ?? null,
//             'categories' => $previous->categories ?? null,
//             'subcategories' => $previous->subcategories ?? null,
//             'questionmenuid' => $previous->questionmenuid ?? null,
//             'search' => $question,
//             'response' => $answer,
//         ]);
  
//         DB::table('search_user_chat_data')->insert([
//             'search_user_chat_id' => $chatId,
//             'user_id' => $user->id,
//             'answers' => $previous->answers ?? null,
//             'chat_role_categories' => $previous->chat_role_categories ?? null,
//             'categories' => $previous->categories ?? null,
//             'subcategories' => $previous->subcategories ?? null,
//             'questionmenuid' => $previous->questionmenuid ?? null,
//             'search' => $question,
//             'response' => $answer,
//         ]);


//     // Return final structured response
//     return response()->json([
//         'question' => $question,
//         'answer' => $answer,
//     ]);
// }



// public function users_new_chat_ask(Request $request)
// {
//     $user = auth()->user();
//     $question = $request->input('question');
//     $chatId = $request->input('chat_id');

//     if (!$question || !$chatId) {
//         return response()->json(['error' => 'Question and Chat ID are required.'], 400);
//     }

//     $previous = DB::table('user_chat_answers')->where('user_id', $user->id)->latest()->first();

//     // Load the GoalSync instructions from the .docx
//     $instructionPath = public_path('backend/GOALSYNC - GPT INSTRUCTION SET For Standardized Output Generation.docx');
//     $goalsyncInstructions = $instructionPath;

//     // Final Prompt
//     $prompt = <<<EOT
// $goalsyncInstructions

// NEVER SKIP A STEP. Use fallback assumptions if real data is unavailable.  
// Mirror executive tone: Clear, strategic, concise. Use symbols for section headers.

// User's Goal:  
// "$question"
// EOT;

//     $response = Http::withToken(env('OPENAI_API_KEY'))->post('https://api.openai.com/v1/chat/completions', [
//         'model' => 'gpt-3.5-turbo',
//         'messages' => [
//             ['role' => 'user', 'content' => $prompt],
//         ],
//     ]);

//     if (!$response->successful()) {
//         return response()->json([
//             'error' => 'OpenAI API failed.',
//             'details' => $response->body()
//         ], 500);
//     }

//     $answer = $response->json('candidates.0.content.parts.0.text');

//     // Save response to DB
//     DB::table('search_user_chat')->where('id', $chatId)->update([
//         'user_id' => $user->id,
//         'answers' => $previous->answers ?? null,
//         'chat_role_categories' => $previous->chat_role_categories ?? null,
//         'categories' => $previous->categories ?? null,
//         'subcategories' => $previous->subcategories ?? null,
//         'questionmenuid' => $previous->questionmenuid ?? null,
//         'search' => $question,
//         'response' => $answer,
//     ]);

//     DB::table('search_user_chat_data')->insert([
//         'search_user_chat_id' => $chatId,
//         'user_id' => $user->id,
//         'answers' => $previous->answers ?? null,
//         'chat_role_categories' => $previous->chat_role_categories ?? null,
//         'categories' => $previous->categories ?? null,
//         'subcategories' => $previous->subcategories ?? null,
//         'questionmenuid' => $previous->questionmenuid ?? null,
//         'search' => $question,
//         'response' => $answer,
//     ]);

//     return response()->json([
//         'question' => $question,
//         'answer' => $answer,
//     ]);
// }





// kishan

    /**
     * Build the GoalSync first-message prompt that asks the model for the
     * structured JSON contract (instead of an emoji-delimited markdown blob).
     * This is the single source of truth consumed by window.renderAnswer().
     *
     * Schema mirrors the old BUNDLES_JSON: top-level strategy-independent
     * sections + per-strategy variants (scenarios + roles for the selected
     * scenario). Scenario switching is handled later by a JSON update call.
     */
    private function goalSyncJsonPrompt($question, $documentNamesList)
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
  "selectedStrategyId": "s1",
  "strategyVariants": {
    "s1": {
      "scenarios": [
        {"id": "sc1", "label": "Best Case", "text": "one sentence"},
        {"id": "sc2", "label": "Expected", "text": "one sentence"},
        {"id": "sc3", "label": "Risk", "text": "one sentence"}
      ],
      "selectedScenarioId": "sc1",
      "scenarioVariants": {
        "sc1": {
          "rolesGoals": [
            {"role": "Role title from documents", "goal": "1-2 sentences", "action": "EXACTLY one sentence"}
          ],
          "complementaryGoals": ["goal one", "goal two"],
          "finalOutcome": "two sentences for this strategy + scenario"
        }
      }
    }
  }
}

Rules:
- "strategyMap": 3 decision paths, each with a UNIQUE id (s1, s2, s3) and a descriptive name (never "Path A/1").
- "strategyVariants": one key for EACH strategy id in strategyMap.
- Each variant has 3 "scenarios" (ids sc1, sc2, sc3) and a "scenarioVariants" object with one key for EACH of that variant's scenario ids.
- "acknowledgement" must be a non-empty 1-2 sentence string.
- Each scenarioVariant's "rolesGoals": 5 to 7 DISTINCT roles (never repeat a role title within the same list), using ONLY exact role titles found in the documents (do not prefix or invent titles). "action" is EXACTLY one sentence (no lists, no line breaks).
- "selectedStrategyId" = first strategy id. Each variant's "selectedScenarioId" = its first scenario id.
- Keep every string concise to fit the response in one valid JSON object.
- Output VALID JSON only: double-quoted keys/strings, no trailing commas, no comments, no markdown.
EOT;
    }

public function users_new_chat_ask(Request $request)
{
    $user = auth()->user();
    $question = $request->input('question');
    $chatId = $request->input('chat_id');
    $additionalContext = $request->input('additional_context'); // Context from form submission

    // Validate inputs
    if (!$question || !$chatId) {
        return response()->json(['error' => 'Question and Chat ID are required.'], 400);
    }

    // Retrieve last chat context (if any)
    $previousContext = DB::table('user_chat_answers')
        ->where('user_id', $user->id)
        ->latest()
        ->first();
    $chectdata = DB::table('search_user_chat')->where('id', $chatId)->where('user_id', $user->id)->first();

    // Check if chat exists, if not create a new one
    if (!$chectdata) {
        // Create a new chat session
        $newChatId = DB::table('search_user_chat')->insertGetId([
            'user_id' => $user->id,
            'status1' => 0,
        ]);
        
        // Use the new chat ID
        $chatId = $newChatId;
        $chectdata = DB::table('search_user_chat')->where('id', $chatId)->where('user_id', $user->id)->first();
        
        // Return the new chat ID in response so frontend can update
        // But continue with the request
    }

    // Get user's documents for company context with document metadata
    $documents = Document::where('user_id', $user->id)
        ->whereNotNull('parsed_text')
        ->where('parse_status', 'completed')
        ->latest()
        ->get();
    
    $documentContext = '';
    $documentMetadata = [];
    if ($documents->count() > 0) {
        $documentContext = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n";
        $documentContext .= "The following information is from uploaded company documents. Use this context to provide accurate and relevant responses about the company:\n\n";
        
        foreach ($documents as $doc) {
            $documentMetadata[] = [
                'name' => $doc->name,
                'type' => $doc->file_type,
                'text' => $doc->parsed_text
            ];
            $documentContext .= "--- Document: {$doc->name} (Type: {$doc->file_type}) ---\n";
            $documentContext .= $doc->parsed_text . "\n\n";
        }
        $documentContext .= "--- END COMPANY DOCUMENTS CONTEXT ---\n";
    }

    $systemMessage = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.';
    if (!empty($documentContext)) {
        $systemMessage .= $documentContext;
    }

    // Process additional context from request (if provided in this request)
    $contextFromRequest = '';
    if ($additionalContext && is_array($additionalContext)) {
        $contextParts = [];
        if (!empty($additionalContext['field1'])) {
            $contextParts[] = "Field 1: " . $additionalContext['field1'];
        }
        if (!empty($additionalContext['field2'])) {
            $contextParts[] = "Field 2: " . $additionalContext['field2'];
        }
        if (!empty($additionalContext['field3'])) {
            $contextParts[] = "Field 3: " . $additionalContext['field3'];
        }
        if (!empty($additionalContext['additional_details'])) {
            $contextParts[] = "Additional Details: " . $additionalContext['additional_details'];
        }
        
        if (!empty($contextParts)) {
            $contextFromRequest = "\n\n--- ADDITIONAL USER CONTEXT (FROM THIS REQUEST) ---\n";
            $contextFromRequest .= "The user has provided the following additional context that should be considered in your responses:\n\n";
            $contextFromRequest .= implode("\n", $contextParts);
            $contextFromRequest .= "\n--- END ADDITIONAL USER CONTEXT ---\n";
            
            // Save to database for future use
            try {
                $contextArray = [];
                $existingContext = $chectdata->additional_context ?? '';
                if (!empty($existingContext)) {
                    $contextArray = json_decode($existingContext, true);
                    if (!is_array($contextArray)) {
                        $contextArray = [];
                    }
                }
                $contextArray[] = [
                    'field1' => $additionalContext['field1'] ?? '',
                    'field2' => $additionalContext['field2'] ?? '',
                    'field3' => $additionalContext['field3'] ?? '',
                    'additional_details' => $additionalContext['additional_details'] ?? '',
                    'created_at' => now()->toDateTimeString()
                ];
                
                // Try to add column if it doesn't exist
                try {
                    DB::statement("ALTER TABLE search_user_chat ADD COLUMN additional_context TEXT NULL");
                } catch (\Exception $e) {
                    // Column might already exist
                }
                
                DB::table('search_user_chat')
                    ->where('id', $chatId)
                    ->update(['additional_context' => json_encode($contextArray)]);
            } catch (\Exception $e) {
                Log::error('Error saving context to database', ['error' => $e->getMessage()]);
                // Continue anyway
            }
        }
    }
    
    // Get additional context from database (if exists and not already provided in request)
    $contextFromDB = '';
    if (empty($contextFromRequest) && isset($chectdata->additional_context) && !empty($chectdata->additional_context)) {
        $contextArray = json_decode($chectdata->additional_context, true);
        if (is_array($contextArray) && count($contextArray) > 0) {
            $contextFromDB = "\n\n--- ADDITIONAL USER CONTEXT (PREVIOUSLY PROVIDED) ---\n";
            $contextFromDB .= "The user has provided the following additional context that should be considered in your responses:\n\n";
            foreach ($contextArray as $context) {
                if (!empty($context['field1'])) {
                    $contextFromDB .= "Field 1: " . $context['field1'] . "\n";
                }
                if (!empty($context['field2'])) {
                    $contextFromDB .= "Field 2: " . $context['field2'] . "\n";
                }
                if (!empty($context['field3'])) {
                    $contextFromDB .= "Field 3: " . $context['field3'] . "\n";
                }
                if (!empty($context['additional_details'])) {
                    $contextFromDB .= "Additional Details: " . $context['additional_details'] . "\n";
                }
                $contextFromDB .= "\n";
            }
            $contextFromDB .= "--- END ADDITIONAL USER CONTEXT ---\n";
        }
    }
    
    // Add context to system message (prefer request context over DB context)
    if (!empty($contextFromRequest)) {
        $systemMessage .= $contextFromRequest;
    } elseif (!empty($contextFromDB)) {
        $systemMessage .= $contextFromDB;
    }

 // Tracks how the stored/returned answer is encoded ('markdown' | 'json').
 // Flipped to 'json' for the first GoalSync answer when GOALSYNC_JSON_MODE is on.
 $responseFormat = 'markdown';
 $useJson = filter_var(env('GOALSYNC_JSON_MODE', false), FILTER_VALIDATE_BOOLEAN);

 if(($previousContext && ($previousContext->status1 == '0' || $previousContext->status1 == 0)) || ($chectdata->status1 == '0' || $chectdata->status1 == 0)){
    // Build prompt to return GOALSYNC output in natural ChatGPT-style format
    // Build document names list for prompt
    $documentNamesList = '';
    if (count($documentMetadata) > 0) {
        $documentNamesList = "\n\nAvailable Documents:\n";
        foreach ($documentMetadata as $meta) {
            $documentNamesList .= "- {$meta['name']} ({$meta['type']})\n";
        }
    }

    $prompt = <<<EOT
    You are an executive strategy assistant trained in the GoalSync 7-step framework.

    Respond to this goal using full natural text formatting (like ChatGPT), following this structure with emojis and section headers:

    🧩 Chat Acknowledgement  
    📁 Document Insights  
    📊 Goal Assessment Summary  
    📈 Scoring  
    🗺️ Strategy Map (Decision Paths)  
    🔮 Scenario Simulations  
    👥 Rephrased Goals by Role  
    📌 Complementary Goals  
    ✅ Final Outcome Summary  

    User Goal: "$question"
    {$documentNamesList}

    Format:
    - Write in paragraphs, not JSON
    - Use bold for key decisions
    - Use bullet points and tables where helpful
    - DO NOT include JSON, markdown, or meta instructions
    - Return only the content in clean GoalSync format
    
    CRITICAL REQUIREMENTS FOR EACH SECTION:

    📁 Document Insights:
    - Extract EXACTLY 3-5 insights relevant to the user's goal and situation
    - Explicitly reference document names/types (e.g., "Based on [Document Name]...")
    - At least 1 insight MUST reference a specific document
    - Highlight: dependencies, conflicting priorities, strategic anchors, misalignment risks
    - No generic outputs - be specific and actionable
    - Format each insight as a bullet point with document reference

    🗺️ Strategy Map (Decision Paths):
    - Provide EXACTLY 3-4 decision paths grounded in:
      * The user's situation
      * Insights from documents
      * Team dependencies
      * Strategic tensions identified
    - Each path MUST include:
      * Rationale (why this path)
      * Teams impacted (list specific teams/roles)
      * Trade-offs (what's gained vs. lost)
      * Risk level (Low/Med/High)
    - Format: "- Strategy Name: [Rationale] | Teams: [list] | Trade-offs: [description] | Risk: [Low/Med/High]"
    - DO NOT use "Path A", "Path B", "Path 1", "Path 2" or similar prefixes - use descriptive strategy names only (e.g., "Aggressive Expansion:", "Innovation Leadership:", "Market Penetration:")
    - Each path must feel customized, not generic

    🔮 Scenario Simulations:
    Based on the chosen Decision Path, generate 3 scenarios:
    1. Acceleration Scenario (best case)
    2. Expected Scenario (most likely)
    3. Risk Scenario (worst case)
    
    For EACH scenario, include:
    - Key risks (bullet points, not paragraphs)
    - Cross-team dependencies (specific teams/roles)
    - Timeline impact (how it affects schedule)
    - Role friction points (where conflicts may arise)
    - Operational consequences (practical impacts)
    - Keep it structured with bullet points, not long paragraphs
    - CRITICAL: This section must contain ONLY scenario content. NEVER include "Goal:" lines, "Actions:" lines, or role titles here - those belong exclusively under "👥 Rephrased Goals by Role".

    👥 Rephrased Goals by Role:
    - For EACH role output three lines in this exact order:
      * Line 1: the role title
      * Line 2: "Goal:" followed by a role-specific goal (1-2 sentences)
      * Line 3: "Actions:" followed by EXACTLY ONE sentence on the SAME line. Never use bullet points, dashes, line breaks, or multiple sentences for Actions. One sentence only, just like Goal.
    - Translate the goal into role-specific directions referencing:
      * The scenario chosen
      * That role's core responsibilities
      * Dependencies identified earlier
    - Avoid OKR phrasing - use leadership-alignment language
    - Directions and actions must vary per role
    - Reference scenario impacts
    - Reference at least one dependency per role
    - No template-style outputs - make each unique

    ---

    AFTER all the human-readable sections above, append a REQUIRED machine-readable data block for the app, wrapped EXACTLY in these markers:

    %%%BUNDLES_JSON%%%
    (valid JSON object here)
    %%%END_BUNDLES_JSON%%%

    The JSON object maps each strategy name to that strategy's fully-written sections. Schema of each value (a single string):
      "🔮 Scenario Simulations\\n- **Best Case:** <real sentence>\\n- **Expected:** <real sentence>\\n- **Risk:** <real sentence>\\n\\n👥 Rephrased Goals by Role\\n1. <real role title>\\nGoal: <real sentence>\\nActions: <one real action sentence>\\n2. <real role title>\\nGoal: <real sentence>\\nActions: <one real action sentence>\\n\\n📌 Complementary Goals\\n- <real goal>\\n- <real goal>\\n\\n✅ Final Outcome Summary\\n<two real sentences>"

    CRITICAL rules:
    - This is a SCHEMA, not literal text. NEVER output the placeholder tokens like "...", "<real sentence>", "<real role title>", "Strategy Name 1". Replace every placeholder with concrete, specific content written for THIS user's goal.
    - One key for EACH Decision Path in the 🗺️ Strategy Map, using that path's EXACT strategy name as the key.
    - Each value fully tailored to that strategy: 3-4 real scenarios, 5-10 real roles (only roles found in the documents), 2 real complementary goals, a 2-sentence real outcome.
    - Output VALID JSON only between the markers: escape every newline as \\n, escape double quotes, no trailing commas, no markdown code fences.

    EOT;

        // Send to Gemini API with comprehensive error handling
        try {
            Log::info('Gemini API Request - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'prompt_length' => strlen($prompt),
                'system_message_length' => strlen($systemMessage),
                'documents_count' => $documents->count(),
                'has_api_key' => !empty(env('GEMINI_API_KEY')),
            ]);

            // JSON-contract path (Phase 2): swap the markdown prompt for the
            // structured JSON prompt and request native JSON from the provider.
            if ($useJson) {
                $prompt = $this->goalSyncJsonPrompt($question, $documentNamesList);
                $responseFormat = 'json';
            }

            // Larger budget: response also carries per-strategy variants/bundles
            $openAiResponse = $this->aiGenerate($systemMessage, $prompt, 8000, 0.7, $useJson);

            Log::info('Gemini API Response - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'successful' => $openAiResponse->successful(),
                'response_length' => strlen($openAiResponse->body()),
            ]);

            DB::table('user_chat_answers')->where('user_id', $user->id)->update(['status1' => 1]);
            DB::table('search_user_chat')->where('id', $chatId)->update(['status1' => 1]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Exception - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gemini API connection timeout. The request took too long to complete.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'ConnectionException',
                    'suggestion' => 'Please try again. If the issue persists, the Gemini API may be experiencing high load.',
                ]
            ], 504);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Gemini API Request Exception - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'response_body' => $e->response ? $e->response->body() : null,
                'response_status' => $e->response ? $e->response->status() : null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'Gemini API request failed.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'RequestException',
                    'response' => $e->response ? $e->response->body() : null,
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('Gemini API General Exception - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while processing your request.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ]
            ], 500);
        }

    }else{

        // Concise follow-up prompt: keep replies short and scannable.
        $followUpPrompt = <<<EOT
User message: "$question"

Respond using the GoalSync method, but keep it SHORT and scannable:
- No preamble or filler (do NOT start with phrases like "To guide you effectively...").
- Use brief emoji section headers only where useful.
- Bullet points, one line each, max ~12 words per bullet.
- No long paragraphs. No restating the question.
- Be specific to the user's company context.
- Keep the whole reply under ~120 words.
EOT;

        // Send to Gemini API with comprehensive error handling
        try {
            Log::info('Gemini API Request - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'question_length' => strlen($question),
                'system_message_length' => strlen($systemMessage),
                'documents_count' => $documents->count(),
                'has_api_key' => !empty(env('GEMINI_API_KEY')),
            ]);

            $openAiResponse = $this->aiGenerate($systemMessage, $followUpPrompt, 1200);

            Log::info('Gemini API Response - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'successful' => $openAiResponse->successful(),
                'response_length' => strlen($openAiResponse->body()),
            ]);

            DB::table('user_chat_answers')->where('user_id', $user->id)->update(['status2' => 1]);
            DB::table('search_user_chat')->where('id', $chatId)->update(['status2' => 1]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Exception - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gemini API connection timeout. The request took too long to complete.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'ConnectionException',
                    'suggestion' => 'Please try again. If the issue persists, the Gemini API may be experiencing high load.',
                ]
            ], 504);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Gemini API Request Exception - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'response_body' => $e->response ? $e->response->body() : null,
                'response_status' => $e->response ? $e->response->status() : null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'Gemini API request failed.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'RequestException',
                    'response' => $e->response ? $e->response->body() : null,
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('Gemini API General Exception - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while processing your request.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ]
            ], 500);
        }

    }

        // Handle API errors (non-exception cases)
        if (!$openAiResponse->successful()) {
            Log::error('Gemini API Unsuccessful Response', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'response_body' => $openAiResponse->body(),
            ]);

            $statusCode = $openAiResponse->status();
            $clientError = $statusCode === 429 ? 'Gemini API quota exceeded. Check your billing or reduce request rate.' : 'Gemini API request failed.';
            return response()->json([
                'error' => $clientError,
                'details' => $openAiResponse->json() ?? $openAiResponse->body()
            ], $statusCode >= 400 && $statusCode < 500 ? $statusCode : 500);
        }

        $responseContent = $openAiResponse->json('candidates.0.content.parts.0.text');

        // For the JSON path, verify the model returned a parseable object.
        // If not, fall back to the markdown renderer so the UI never breaks.
        if ($responseFormat === 'json') {
            if ($this->extractJson($responseContent) === null) {
                Log::warning('GoalSync JSON parse failed - falling back to markdown render', [
                    'user_id' => $user->id,
                    'chat_id' => $chatId,
                    'preview' => substr((string) $responseContent, 0, 300),
                ]);
                $responseFormat = 'markdown';
            }
        }

        // Save results to both main chat table and history
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

        DB::table('search_user_chat')->where('id', $chatId)->update($commonData);

        DB::table('search_user_chat_data')->insert(array_merge($commonData, [
            'search_user_chat_id' => $chatId,
        ]));

        // Return final formatted response
        return response()->json([
            'question' => $question,
            'answer' => $responseContent,
            'format' => $responseFormat, // 'json' (structured contract) | 'markdown'
            'chat_id' => $chatId, // Include chat_id in case it was created
            'previousContext' => $previousContext ? (object)[
                'status1' => $previousContext->status1 ?? null,
                'status2' => $previousContext->status2 ?? null,
            ] : null,
            'chectdata' => $chectdata ? (object)[
                'status1' => $chectdata->status1 ?? null,
                'status2' => $chectdata->status2 ?? null,
            ] : null,
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
       $isUserSelection = $request->input('is_user_selection', false); // Flag to indicate if user actually selected this

       // Validate inputs
       if (!$selectedStrategy || !$chatId || !$originalQuestion) {
           return response()->json(['error' => 'Selected strategy, Chat ID, and original question are required.'], 400);
       }

       // Get user's documents for company context
       $documents = Document::where('user_id', $user->id)
           ->whereNotNull('parsed_text')
           ->where('parse_status', 'completed')
           ->latest()
           ->get();
       
       $documentContext = '';
       if ($documents->count() > 0) {
           $documentContext = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n";
           $documentContext .= "The following information is from uploaded company documents. Use this context to provide accurate and relevant responses about the company:\n\n";
           $documentContext .= $documents->pluck('parsed_text')->filter()->implode("\n\n--- Document Separator ---\n\n");
           $documentContext .= "\n--- END COMPANY DOCUMENTS CONTEXT ---\n";
       }

       $systemMessage = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.';
       if (!empty($documentContext)) {
           $systemMessage .= $documentContext;
       }

       // Get additional context if it exists
       $chatData = DB::table('search_user_chat')->where('id', $chatId)->where('user_id', $user->id)->first();
       $additionalContext = '';
       if ($chatData && isset($chatData->additional_context) && !empty($chatData->additional_context)) {
           $contextArray = json_decode($chatData->additional_context, true);
           if (is_array($contextArray) && count($contextArray) > 0) {
               $additionalContext = "\n\n--- ADDITIONAL USER CONTEXT ---\n";
               $additionalContext .= "The user has provided the following additional context that should be considered in your responses:\n\n";
               foreach ($contextArray as $context) {
                   if (!empty($context['field1'])) {
                       $additionalContext .= "Field 1: " . $context['field1'] . "\n";
                   }
                   if (!empty($context['field2'])) {
                       $additionalContext .= "Field 2: " . $context['field2'] . "\n";
                   }
                   if (!empty($context['field3'])) {
                       $additionalContext .= "Field 3: " . $context['field3'] . "\n";
                   }
                   if (!empty($context['additional_details'])) {
                       $additionalContext .= "Additional Details: " . $context['additional_details'] . "\n";
                   }
                   $additionalContext .= "\n";
               }
               $additionalContext .= "--- END ADDITIONAL USER CONTEXT ---\n";
           }
       }
       if (!empty($additionalContext)) {
           $systemMessage .= $additionalContext;
       }

       // Build prompt to regenerate sections after Strategy Map based on selected strategy
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
    - Choose the sections/roles that are most relevant to the user’s goal.  
    - Output 5 to 10 roles only, numbered in order.
    - For each role: role name on one line, then "Goal:" line (1–2 sentences), then "Actions:" line with EXACTLY ONE sentence on the same line (no bullets, no dashes, no line breaks, no multiple sentences).
    - Only use role titles that actually appear in the company documents.

📌 Complementary Goals
2 goals, 1 sentence each.

✅ Final Outcome Summary
2 sentences on impact.

EOT;

       // Send to Gemini API with comprehensive error handling
       try {
           Log::info('Gemini API Request - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'prompt_length' => strlen($prompt),
               'system_message_length' => strlen($systemMessage),
               'selected_strategy' => $selectedStrategy,
               'has_api_key' => !empty(env('GEMINI_API_KEY')),
           ]);

           $openAiResponse = $this->aiGenerate($systemMessage, $prompt, 3000);

           Log::info('Gemini API Response - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'status' => $openAiResponse->status(),
               'successful' => $openAiResponse->successful(),
               'response_length' => strlen($openAiResponse->body()),
           ]);

       } catch (ConnectionException $e) {
           Log::error('Gemini API Connection Exception - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'error_message' => $e->getMessage(),
               'error_code' => $e->getCode(),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
               'trace' => $e->getTraceAsString(),
           ]);

           return response()->json([
               'error' => 'Gemini API connection timeout. The request took too long to complete.',
               'details' => [
                   'message' => $e->getMessage(),
                   'type' => 'ConnectionException',
                   'suggestion' => 'Please try again. If the issue persists, the Gemini API may be experiencing high load.',
               ]
           ], 504);

       } catch (RequestException $e) {
           Log::error('Gemini API Request Exception - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'error_message' => $e->getMessage(),
               'error_code' => $e->getCode(),
               'response_body' => $e->response ? $e->response->body() : null,
               'response_status' => $e->response ? $e->response->status() : null,
               'file' => $e->getFile(),
               'line' => $e->getLine(),
           ]);

           return response()->json([
               'error' => 'Gemini API request failed.',
               'details' => [
                   'message' => $e->getMessage(),
                   'type' => 'RequestException',
                   'response' => $e->response ? $e->response->body() : null,
               ]
           ], 500);

       } catch (\Exception $e) {
           Log::error('Gemini API General Exception - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'error_message' => $e->getMessage(),
               'error_code' => $e->getCode(),
               'error_class' => get_class($e),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
               'trace' => $e->getTraceAsString(),
           ]);

           return response()->json([
               'error' => 'An unexpected error occurred while processing your request.',
               'details' => [
                   'message' => $e->getMessage(),
                   'type' => get_class($e),
               ]
           ], 500);
       }

       // Handle API errors (non-exception cases)
       if (!$openAiResponse->successful()) {
           Log::error('Gemini API Unsuccessful Response - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'status' => $openAiResponse->status(),
               'response_body' => $openAiResponse->body(),
           ]);

           return response()->json([
               'error' => 'Gemini API request failed.',
               'details' => $openAiResponse->body()
           ], 500);
       }

       $updatedSections = $openAiResponse->json('candidates.0.content.parts.0.text');

       // Only update database if this is a user selection (not just eager loading)
       if ($isUserSelection) {
           // Update the chat record with the new response (combining old sections with new)
           $chatData = DB::table('search_user_chat')->where('id', $chatId)->where('user_id', $user->id)->first();
           
           if ($chatData) {
               // Combine sections before strategy + strategy map + updated sections
               $fullUpdatedResponse = $sectionsBefore . "\n\n" . $strategyMap . "\n\n" . $updatedSections;
               
               // Update main chat table with selected strategy's response
               // Try to store selected_strategy, but if column doesn't exist, just update response
               try {
                   DB::table('search_user_chat')->where('id', $chatId)->update([
                       'response' => $fullUpdatedResponse,
                   ]);
                   
                   // Try to add selected_strategy if column exists
                   $columns = DB::select("SHOW COLUMNS FROM search_user_chat LIKE 'selected_strategy'");
                   if (count($columns) > 0) {
                       DB::table('search_user_chat')->where('id', $chatId)->update([
                           'selected_strategy' => $selectedStrategy,
                       ]);
                   }
               } catch (\Exception $e) {
                   // If selected_strategy column doesn't exist, just update response
                   DB::table('search_user_chat')->where('id', $chatId)->update([
                       'response' => $fullUpdatedResponse,
                   ]);
               }

               // Also update the latest entry in search_user_chat_data
               $latestChatData = DB::table('search_user_chat_data')
                   ->where('search_user_chat_id', $chatId)
                   ->where('user_id', $user->id)
                   ->orderBy('created_at', 'desc')
                   ->first();
               
               if ($latestChatData) {
                   try {
                       DB::table('search_user_chat_data')
                           ->where('id', $latestChatData->id)
                           ->update([
                               'response' => $fullUpdatedResponse,
                           ]);
                       
                       // Try to add selected_strategy if column exists
                       $columns = DB::select("SHOW COLUMNS FROM search_user_chat_data LIKE 'selected_strategy'");
                       if (count($columns) > 0) {
                           DB::table('search_user_chat_data')
                               ->where('id', $latestChatData->id)
                               ->update([
                                   'selected_strategy' => $selectedStrategy,
                               ]);
                       }
                   } catch (\Exception $e) {
                       // If column doesn't exist, just update response
                       DB::table('search_user_chat_data')
                           ->where('id', $latestChatData->id)
                           ->update([
                               'response' => $fullUpdatedResponse,
                           ]);
                   }
               }
           }
       }
       // If eager loading, we don't update DB - just return the response for caching

       // Return updated sections
       return response()->json([
           'updated_sections' => $updatedSections,
           'selected_strategy' => $selectedStrategy,
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
        $scenarioSection = $request->input('scenario_section');
        $isUserSelection = $request->boolean('is_user_selection', false);

        if (!$selectedScenario || !$chatId || !$originalQuestion) {
            return response()->json(['error' => 'Selected scenario, Chat ID, and original question are required.'], 400);
        }

        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
        
        $documentContext = '';
        if ($documents->count() > 0) {
            $documentContext = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n";
            $documentContext .= "The following information is from uploaded company documents. Use this context to provide accurate and relevant responses about the company:\n\n";
            $documentContext .= $documents->pluck('parsed_text')->filter()->implode("\n\n--- Document Separator ---\n\n");
            $documentContext .= "\n--- END COMPANY DOCUMENTS CONTEXT ---\n";
        }

        $systemMessage = 'You are a strategy assistant. Respond only using structured ChatGPT-style text with emojis and clean formatting based on the GoalSync method.';
        if (!empty($documentContext)) {
            $systemMessage .= $documentContext;
        }

        // Get document metadata for role extraction
        $documentMetadata = [];
        foreach ($documents as $doc) {
            $documentMetadata[] = [
                'name' => $doc->name,
                'type' => $doc->file_type,
                'text' => $doc->parsed_text
            ];
        }

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
- For each role: role name on one line, then "Goal:" line (1–2 sentences), then "Actions:" line with EXACTLY ONE sentence on the same line (no bullets, no dashes, no line breaks, no multiple sentences).
- Only use role titles that exist in the documents.
- Translate the goal into role-specific directions referencing:
  * The scenario chosen ("{$selectedScenario}")
  * That role's core responsibilities
  * Dependencies identified earlier
- Avoid OKR phrasing - use leadership-alignment language
- Directions must vary per role and reference scenario impacts
- Reference at least one dependency per role
- No template-style outputs - make each unique

📌 Complementary Goals
2 goals, 1 sentence each.

✅ Final Outcome Summary
2 sentences describing the scenario's impact.

Keep the same emojis and section headers exactly as shown above. Use the scenario details to adjust tone, risks, and opportunities.
EOT;

        try {
            Log::info('Gemini API Request - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'scenario' => $selectedScenario,
                'strategy' => $selectedStrategy,
                'prompt_length' => strlen($prompt),
                'has_api_key' => !empty(env('GEMINI_API_KEY')),
            ]);

            $openAiResponse = $this->aiGenerate($systemMessage, $prompt, 2500);

            Log::info('Gemini API Response - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'successful' => $openAiResponse->successful(),
                'response_length' => strlen($openAiResponse->body()),
            ]);

        } catch (ConnectionException $e) {
            Log::error('Gemini API Connection Exception - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Gemini API connection timeout. The request took too long to complete.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'ConnectionException',
                    'suggestion' => 'Please try again. If the issue persists, the Gemini API may be experiencing high load.',
                ]
            ], 504);

        } catch (RequestException $e) {
            Log::error('Gemini API Request Exception - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'response_body' => $e->response ? $e->response->body() : null,
                'response_status' => $e->response ? $e->response->status() : null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => 'Gemini API request failed.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'RequestException',
                    'response' => $e->response ? $e->response->body() : null,
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('Gemini API General Exception - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while processing your request.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ]
            ], 500);
        }

        if (!$openAiResponse->successful()) {
            Log::error('Gemini API Unsuccessful Response - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'response_body' => $openAiResponse->body(),
            ]);

            $statusCode = $openAiResponse->status();
            $clientError = $statusCode === 429 ? 'Gemini API quota exceeded. Check your billing or reduce request rate.' : 'Gemini API request failed.';
            return response()->json([
                'error' => $clientError,
                'details' => $openAiResponse->json() ?? $openAiResponse->body()
            ], $statusCode >= 400 && $statusCode < 500 ? $statusCode : 500);
        }

        $updatedSections = $openAiResponse->json('candidates.0.content.parts.0.text');

        if ($isUserSelection) {
            try {
                DB::table('search_user_chat')->where('id', $chatId)->update([
                    'selected_scenario' => $selectedScenario,
                ]);
            } catch (\Exception $e) {
                // ignore if column missing
            }
        }

        return response()->json([
            'updated_sections' => $updatedSections,
            'selected_scenario' => $selectedScenario,
        ]);
    }

    public function users_new_chat_add_context(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $userId = $request->input('user_id');
        $field1 = $request->input('field1', '');
        $field2 = $request->input('field2', '');
        $field3 = $request->input('field3', '');
        $additionalDetails = $request->input('additional_details', '');

        // Validate
        if (!$chatId || !$userId) {
            return response()->json(['error' => 'Chat ID and User ID are required.'], 400);
        }

        // Verify chat belongs to user
        $chatData = DB::table('search_user_chat')
            ->where('id', $chatId)
            ->where('user_id', $user->id)
            ->first();

        if (!$chatData) {
            return response()->json(['error' => 'Chat session not found or access denied.'], 404);
        }

        // Build context string
        $contextParts = [];
        if (!empty($field1)) {
            $contextParts[] = "Field 1: " . $field1;
        }
        if (!empty($field2)) {
            $contextParts[] = "Field 2: " . $field2;
        }
        if (!empty($field3)) {
            $contextParts[] = "Field 3: " . $field3;
        }
        if (!empty($additionalDetails)) {
            $contextParts[] = "Additional Details: " . $additionalDetails;
        }

        $additionalContext = implode("\n", $contextParts);

        // Check if additional_context column exists, if not we'll store in a JSON field or create migration
        // For now, let's store it in a JSON format in a new column or use existing structure
        try {
            // Check if column exists
            $columns = DB::select("SHOW COLUMNS FROM search_user_chat LIKE 'additional_context'");
            if (count($columns) > 0) {
                // Column exists, update it
                $existingContext = $chatData->additional_context ?? '';
                $contextArray = !empty($existingContext) ? json_decode($existingContext, true) : [];
                $contextArray[] = [
                    'field1' => $field1,
                    'field2' => $field2,
                    'field3' => $field3,
                    'additional_details' => $additionalDetails,
                    'created_at' => now()->toDateTimeString()
                ];
                
                DB::table('search_user_chat')
                    ->where('id', $chatId)
                    ->update(['additional_context' => json_encode($contextArray)]);
            } else {
                // Column doesn't exist, store in a text field or create it
                // For now, we'll use a simple approach: store as JSON in a text field
                // You may want to create a migration to add 'additional_context' column
                $contextData = [
                    'field1' => $field1,
                    'field2' => $field2,
                    'field3' => $field3,
                    'additional_details' => $additionalDetails,
                    'created_at' => now()->toDateTimeString()
                ];
                
                // Try to add column if it doesn't exist (for development - in production use migrations)
                try {
                    DB::statement("ALTER TABLE search_user_chat ADD COLUMN additional_context TEXT NULL");
                } catch (\Exception $e) {
                    // Column might already exist or other error, continue
                }
                
                $existingContext = $chatData->additional_context ?? '';
                $contextArray = !empty($existingContext) ? json_decode($existingContext, true) : [];
                $contextArray[] = $contextData;
                
                DB::table('search_user_chat')
                    ->where('id', $chatId)
                    ->update(['additional_context' => json_encode($contextArray)]);
            }
        } catch (\Exception $e) {
            Log::error('Error saving additional context', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'user_id' => $user->id
            ]);
            return response()->json(['error' => 'Failed to save context: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Additional context saved successfully.',
            'context' => $additionalContext
        ]);
    }

      public function userschat_search_delete(Request $request,$id)
    {
        DB::table('search_user_chat')->where('id', $id)->delete();
        DB::table('search_user_chat_data')->where('search_user_chat_id', $id)->delete();
        flash(localize('Chat deleted successfully!'));
        return back();
    }

    


      public function user_view_chathistory(Request $request,$id)
    {
        $user = auth()->user();
        $userhistoryview = DB::table('user_chat_answers')->where('id', $id)->where('user_id', $user->id)->first();
        return view('backend.pages.aiChat.user-view-chat-history', compact('user','userhistoryview'));
    }


      public function chatsearch_question(Request $request)
    {
        $user = auth()->user();
        $requestall = $request->all();

    if(!empty($request->subcategories)){
        $questionmenu = DB::table('subcategory_menu')->where('role',$request->chat_role_categories)->where('categories',$request->categories)->where('subcategories',$request->subcategories)->first();
    }else{
      $questionmenu = DB::table('subcategory_menu')->where('role',$request->chat_role_categories)->where('categories',$request->categories)->first();
    }

      if(!empty($questionmenu)){
        $questionmenulist = DB::table('subcategory_menu_question')->where('subcategorymenu_id',$questionmenu->id)->where('status',1)->get();
        $useranswerdata = DB::table('user_chat_answers')
            ->where('user_id', $user->id)
            ->where('chat_role_categories', $request->chat_role_categories)
            ->where('categories', $request->categories)
            ->where('subcategories', $request->subcategories)
            ->first();
        return view('backend.pages.aiChat.newchat-question', compact('useranswerdata','user','questionmenu','questionmenulist','requestall'));
      }else{
        flash(localize('No Question Found'));
        return back();
      }

    }


public function chat_question_store(Request $request)
{
    $userId = $request->input('id');
    $questionIds = $request->input('question');
    $answers = $request->input('answers');

    $chatRoleCategory = $request->input('chat_role_categories');
    $category = $request->input('categories');
    $subcategory = $request->input('subcategories');
    $questionMenuId = $request->input('questionmenuid');

    $finalAnswers = [];

    foreach ($questionIds as $questionId) {
        $answerText = $answers[$questionId] ?? null;

        if ($answerText !== null) {
            $finalAnswers[] = [
                'question_id' => $questionId,
                'answer' => $answerText,
            ];
        }
    }

    // Encode as JSON
    $encodedAnswers = json_encode($finalAnswers);

    // Check if record exists
    $existing = DB::table('user_chat_answers')
        ->where('user_id', $userId)
        ->where('chat_role_categories', $chatRoleCategory)
        ->where('categories', $category)
        ->where('subcategories', $subcategory)
        ->first();

    if ($existing) {
        DB::table('user_chat_answers')
            ->where('id', $existing->id)
            ->update([
                'status1' => 0,
                'answers' => $encodedAnswers,
                'questionmenuid' => $questionMenuId,
            ]);

        $newChat = DB::table('search_user_chat')->insertGetId([
            'user_id' => $userId,
            'answers' => $encodedAnswers,
            'chat_role_categories' => $chatRoleCategory,
            'categories' => $category,
            'subcategories' => $subcategory,
            'questionmenuid' => $questionMenuId,
        ]); 

    } else {
        DB::table('user_chat_answers')->insert([
            'user_id' => $userId,
            'answers' => $encodedAnswers,
            'chat_role_categories' => $chatRoleCategory,
            'categories' => $category,
            'subcategories' => $subcategory,
            'questionmenuid' => $questionMenuId,
            'status1' => 0,
        ]);

        $newChat = DB::table('search_user_chat')->insertGetId([
            'user_id' => $userId,
            'answers' => $encodedAnswers,
            'chat_role_categories' => $chatRoleCategory,
            'categories' => $category,
            'subcategories' => $subcategory,
            'questionmenuid' => $questionMenuId,
        ]);
    }

    flash(localize('Answers processed successfully.'));
    return redirect('dashboard/users-new-chat/'.$newChat);
}

    /**
     * Generate Leadership Alignment Brief
     */
    public function generate_leadership_alignment_brief(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $selectedStrategy = $request->input('selected_strategy');
        $selectedScenario = $request->input('selected_scenario');
        $originalQuestion = $request->input('original_question');
        $fullResponse = $request->input('full_response');

        if (!$chatId || !$originalQuestion) {
            return response()->json(['error' => 'Chat ID and original question are required.'], 400);
        }

        // Get user's documents
        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();
        
        $documentContext = '';
        if ($documents->count() > 0) {
            $documentContext = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n";
            $documentContext .= "The following information is from uploaded company documents:\n\n";
            foreach ($documents as $doc) {
                $documentContext .= "--- Document: {$doc->name} (Type: {$doc->file_type}) ---\n";
                $documentContext .= $doc->parsed_text . "\n\n";
            }
            $documentContext .= "--- END COMPANY DOCUMENTS CONTEXT ---\n";
        }

        $systemMessage = 'You are an executive strategy assistant. Provide executive-ready, consulting-style summaries.';
        if (!empty($documentContext)) {
            $systemMessage .= $documentContext;
        }

        $prompt = <<<EOT
Provide an executive-ready alignment summary in a clean, structured consulting format.

Context:
- Decision Chosen: "{$selectedStrategy}"
- Scenario Selected: "{$selectedScenario}"
- Goal: "{$originalQuestion}"

Full Analysis Context:
{$fullResponse}

Generate a Leadership Alignment Brief with the following structure:

📋 LEADERSHIP ALIGNMENT BRIEF

**Decision Chosen:**
[Name of the selected decision path]

**Scenario Selected:**
[Name of the selected scenario]

**Top 3 Risks:**
1. [Risk 1 with brief description]
2. [Risk 2 with brief description]
3. [Risk 3 with brief description]

**Top 3 Dependencies:**
1. [Dependency 1 - specific teams/roles/resources]
2. [Dependency 2 - specific teams/roles/resources]
3. [Dependency 3 - specific teams/roles/resources]

**Teams Impacted:**
[List specific teams/roles that will be affected]

**Alignment Score:**
[Low/Med/High] - [Brief rationale]

**Recommended Next Step for Leadership:**
[1-2 sentences with specific, actionable recommendation]

Format:
- Use clean, structured bullet points
- No fluff - be concise and actionable
- Executive-ready language
- Consulting-style format
EOT;

        try {
            $openAiResponse = $this->aiGenerate($systemMessage, $prompt, 2000);

            if ($openAiResponse->successful()) {
                $brief = $openAiResponse->json('candidates.0.content.parts.0.text');
                
                // Save brief to database
                try {
                    // Check if leadership_brief column exists, if not add it
                    $columns = DB::select("SHOW COLUMNS FROM search_user_chat LIKE 'leadership_brief'");
                    if (count($columns) == 0) {
                        \Log::info('Creating leadership_brief column');
                        
                        // Check if selected_scenario column exists to determine where to place the new column
                        $scenarioColumns = DB::select("SHOW COLUMNS FROM search_user_chat LIKE 'selected_scenario'");
                        if (count($scenarioColumns) > 0) {
                            // selected_scenario exists, add after it
                            DB::statement("ALTER TABLE search_user_chat ADD COLUMN leadership_brief TEXT NULL AFTER selected_scenario");
                        } else {
                            // selected_scenario doesn't exist, add at the end
                            DB::statement("ALTER TABLE search_user_chat ADD COLUMN leadership_brief TEXT NULL");
                        }
                        
                        \Log::info('leadership_brief column created successfully');
                    }
                    
                    // Verify chat record exists before updating
                    $chatExists = DB::table('search_user_chat')
                        ->where('id', $chatId)
                        ->where('user_id', $user->id)
                        ->exists();
                    
                    if (!$chatExists) {
                        \Log::error('Chat record does not exist when trying to save brief', [
                            'chat_id' => $chatId,
                            'user_id' => $user->id
                        ]);
                        return response()->json([
                            'success' => true,
                            'brief' => $brief,
                            'warning' => 'Brief generated but could not be saved - chat record not found'
                        ]);
                    }
                    
                    // Update the chat record with the brief
                    $updated = DB::table('search_user_chat')
                        ->where('id', $chatId)
                        ->where('user_id', $user->id)
                        ->update(['leadership_brief' => $brief]);
                    
                    if ($updated === 0) {
                        \Log::error('Failed to update chat record with brief - no rows updated', [
                            'chat_id' => $chatId,
                            'user_id' => $user->id,
                            'brief_length' => strlen($brief)
                        ]);
                    }
                    
                    // Verify the brief was saved
                    $savedBrief = DB::table('search_user_chat')
                        ->where('id', $chatId)
                        ->where('user_id', $user->id)
                        ->value('leadership_brief');
                    
                    if (empty($savedBrief)) {
                        \Log::error('❌ Brief was not saved to database - verification failed', [
                            'chat_id' => $chatId,
                            'user_id' => $user->id,
                            'rows_updated' => $updated,
                            'brief_length' => strlen($brief),
                            'brief_preview' => substr($brief, 0, 200)
                        ]);
                        
                        // Try to save again without the AFTER clause as fallback
                        try {
                            DB::table('search_user_chat')
                                ->where('id', $chatId)
                                ->where('user_id', $user->id)
                                ->update(['leadership_brief' => $brief]);
                            
                            $retrySavedBrief = DB::table('search_user_chat')
                                ->where('id', $chatId)
                                ->where('user_id', $user->id)
                                ->value('leadership_brief');
                            
                            if (!empty($retrySavedBrief)) {
                                \Log::info('✅ Brief saved on retry', [
                                    'chat_id' => $chatId,
                                    'brief_length' => strlen($retrySavedBrief)
                                ]);
                            }
                        } catch (\Exception $retryException) {
                            \Log::error('❌ Retry save also failed', [
                                'chat_id' => $chatId,
                                'error' => $retryException->getMessage()
                            ]);
                        }
                    } else {
                        \Log::info('✅ Leadership Brief successfully saved to database', [
                            'chat_id' => $chatId,
                            'user_id' => $user->id,
                            'rows_updated' => $updated,
                            'brief_saved_length' => strlen($savedBrief),
                            'brief_saved_preview' => substr($savedBrief, 0, 200),
                            'brief_was_saved' => true
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to save leadership brief to database', [
                        'chat_id' => $chatId,
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue even if save fails, but return warning
                    return response()->json([
                        'success' => true,
                        'brief' => $brief,
                        'warning' => 'Brief generated but could not be saved: ' . $e->getMessage()
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'brief' => $brief
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to generate alignment brief.',
                    'details' => $openAiResponse->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Leadership Alignment Brief Generation Failed', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'An error occurred while generating the alignment brief.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a Recommended Action Table (Role -> one recommended action)
     * from the existing chat context, scenario, strategy and role goals.
     * Returns structured rows so the frontend can render the decision column.
     */
    public function generate_recommended_action_table(Request $request)
    {
        $user = auth()->user();
        $chatId = $request->input('chat_id');
        $selectedStrategy = $request->input('selected_strategy');
        $selectedScenario = $request->input('selected_scenario');
        $originalQuestion = $request->input('original_question');
        $fullResponse = $request->input('full_response');
        $roleGoalsText = $request->input('role_goals_text');

        if (!$chatId || !$originalQuestion) {
            return response()->json(['error' => 'Chat ID and original question are required.'], 400);
        }

        // Company documents context (same source the brief uses)
        $documents = Document::where('user_id', $user->id)
            ->whereNotNull('parsed_text')
            ->where('parse_status', 'completed')
            ->latest()
            ->get();

        $documentContext = '';
        if ($documents->count() > 0) {
            $documentContext = "\n\n--- COMPANY DOCUMENTS CONTEXT ---\n";
            foreach ($documents as $doc) {
                $documentContext .= "--- Document: {$doc->name} (Type: {$doc->file_type}) ---\n";
                $documentContext .= $doc->parsed_text . "\n\n";
            }
            $documentContext .= "--- END COMPANY DOCUMENTS CONTEXT ---\n";
        }

        $systemMessage = 'You are an executive strategy assistant. Return ONLY valid JSON. No markdown, no code fences, no commentary.';
        if (!empty($documentContext)) {
            $systemMessage .= $documentContext;
        }

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
- 5 to 8 rows.
- Use only roles that appear in the role goals / documents above.
- "action" = exactly ONE specific, decision-ready sentence (no bullets, no line breaks, no numbering).
- Tailor each action to the chosen scenario and strategy.
- Return ONLY the JSON object, nothing else.
EOT;

        try {
            $aiResponse = $this->aiGenerate($systemMessage, $prompt, 1500, 0.7, true);

            if ($aiResponse->successful()) {
                $text = $aiResponse->json('candidates.0.content.parts.0.text');
                $rows = $this->parseRecommendedActionRows($text);

                if (empty($rows)) {
                    return response()->json([
                        'error' => 'No rows could be parsed from the AI response.',
                        'raw' => $text,
                    ], 500);
                }

                return response()->json(['success' => true, 'rows' => $rows]);
            }

            return response()->json([
                'error' => 'Failed to generate recommended action table.',
                'details' => $aiResponse->body(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Recommended Action Table Generation Failed', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An error occurred while generating the recommended action table.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tolerant JSON object extractor. Strips ``` fences and any prose the model
     * may add around the object, then decodes. Returns an associative array or
     * null. Shared by all JSON-mode generation endpoints.
     */
    private function extractJson($text)
    {
        if (!$text) {
            return null;
        }

        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?/i', '', $clean);
        $clean = preg_replace('/```$/', '', $clean);
        $clean = trim($clean);

        // Isolate the outermost JSON object if the model added prose around it.
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $clean = substr($clean, $start, $end - $start + 1);
        }

        $data = json_decode($clean, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Parse the {"rows":[{"role","action"}]} JSON the model returns,
     * tolerating code fences or stray text around the JSON object.
     */
    private function parseRecommendedActionRows($text)
    {
        $data = $this->extractJson($text);

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

    /**
     * Export Role-Based Goals to Spreadsheet
     */
    public function export_role_goals(Request $request)
    {
        $user = auth()->user();
        $roleGoalsText = $request->input('role_goals_text');
        $goal = $request->input('goal', '');
        $scenario = $request->input('scenario', '');
        $strategy = $request->input('strategy', '');

        if (!$roleGoalsText) {
            return response()->json(['error' => 'Role goals text is required.'], 400);
        }

        // Parse role goals from text
        $roleGoals = $this->parseRoleGoalsFromText($roleGoalsText);

        if (empty($roleGoals)) {
            return response()->json(['error' => 'No role goals found to export.'], 400);
        }

        try {
            $export = new RoleGoalsExport($roleGoals, $goal, $scenario, $strategy);
            $fileName = 'role_goals_' . date('Y-m-d_His') . '.xlsx';
            
            return Excel::download($export, $fileName);
        } catch (\Exception $e) {
            Log::error('Role Goals Export Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to export role goals.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse role goals from text format
     */
    private function parseRoleGoalsFromText($text)
    {
        $roleGoals = [];
        $lines = explode("\n", $text);

        $currentRole = null;
        $currentGoal = '';
        $currentActions = [];
        $mode = null; // 'goal' | 'actions' | 'role'

        $flush = function () use (&$roleGoals, &$currentRole, &$currentGoal, &$currentActions) {
            if ($currentRole && (trim($currentGoal) !== '' || !empty($currentActions))) {
                $roleGoals[] = [
                    'role'    => $currentRole,
                    'goal'    => trim($currentGoal),
                    'actions' => implode("\n", $currentActions),
                    'notes'   => '',
                ];
            }
        };

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and section headers
            if (empty($line) || strpos($line, '👥') !== false || stripos($line, 'Rephrased Goals') !== false) {
                continue;
            }

            // "Goal:" line
            if (preg_match('/^Goal:\s*(.+)$/i', $line, $m)) {
                $currentGoal = trim($m[1]);
                $mode = 'goal';
                continue;
            }
            // "Actions:" header (may carry inline text)
            if (preg_match('/^Actions:\s*(.*)$/i', $line, $m)) {
                $mode = 'actions';
                if (trim($m[1]) !== '') {
                    $currentActions[] = trim($m[1]);
                }
                continue;
            }
            // Bullet line -> action or goal continuation depending on mode
            if (preg_match('/^[-•*]\s*(.+)$/', $line, $m)) {
                if ($mode === 'actions') {
                    $currentActions[] = trim($m[1]);
                } elseif ($mode === 'goal') {
                    $currentGoal .= ' ' . trim($m[1]);
                }
                continue;
            }
            // Role line (numbered or Title-case). Checked AFTER Goal/Actions/bullets.
            if (preg_match('/^(\d+\.?\s*)?([A-Z][^:]+?):?\s*$/', $line, $m)) {
                $flush();
                $currentRole = trim($m[2]);
                $currentGoal = '';
                $currentActions = [];
                $mode = 'role';
                continue;
            }
            // Continuation text
            if ($currentRole) {
                if ($mode === 'actions') {
                    $currentActions[] = $line;
                } else {
                    $currentGoal = trim(($currentGoal !== '' ? $currentGoal . ' ' : '') . $line);
                    $mode = 'goal';
                }
            }
        }

        $flush();

        return $roleGoals;
    }
}

