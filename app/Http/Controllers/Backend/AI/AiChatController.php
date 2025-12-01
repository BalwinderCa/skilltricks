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
        
        $selectedStrategyFromDB = null;
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
        }

        return view('backend.pages.aiChat.users-new-chat', compact('user','promptGroups', 'prompts','searchKey','searchuserchatdata','id','searchuserchatdatanew', 'documentCount', 'selectedStrategyFromDB'));
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

    //         $answer = $response->json('choices.0.message.content');

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

//         $answer = $response->json('choices.0.message.content');
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

//     $answer = $response->json('choices.0.message.content');

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
    $chectdata = DB::table('search_user_chat')->where('id', $chatId)->first();

    // Check if chat exists
    if (!$chectdata) {
        return response()->json(['error' => 'Chat session not found.'], 404);
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
    - Format: "- Path Name: [Rationale] | Teams: [list] | Trade-offs: [description] | Risk: [Low/Med/High]"
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

    👥 Rephrased Goals by Role:
    - Translate the goal into role-specific directions referencing:
      * The scenario chosen
      * That role's core responsibilities
      * Dependencies identified earlier
    - Avoid OKR phrasing - use leadership-alignment language
    - Directions must vary per role
    - Reference scenario impacts
    - Reference at least one dependency per role
    - No template-style outputs - make each unique

    EOT;
 
        // Send to OpenAI API with comprehensive error handling
        try {
            Log::info('OpenAI API Request - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'prompt_length' => strlen($prompt),
                'system_message_length' => strlen($systemMessage),
                'documents_count' => $documents->count(),
                'has_api_key' => !empty(env('OPENAI_API_KEY')),
            ]);

            $openAiResponse = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(90) // Increase timeout to 90 seconds
                ->connectTimeout(10) // Connection timeout
                ->retry(2, 1000) // Retry 2 times with 1 second delay
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4-turbo',
                    'temperature' => 0.7,
                    'max_tokens' => 3000,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

            Log::info('OpenAI API Response - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'successful' => $openAiResponse->successful(),
                'response_length' => strlen($openAiResponse->body()),
            ]);

            DB::table('user_chat_answers')->where('user_id', $user->id)->update(['status1' => 1]);
            DB::table('search_user_chat')->where('id', $chatId)->update(['status1' => 1]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OpenAI API Connection Exception - First Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'OpenAI API connection timeout. The request took too long to complete.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'ConnectionException',
                    'suggestion' => 'Please try again. If the issue persists, the OpenAI API may be experiencing high load.',
                ]
            ], 504);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('OpenAI API Request Exception - First Chat', [
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
                'error' => 'OpenAI API request failed.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'RequestException',
                    'response' => $e->response ? $e->response->body() : null,
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('OpenAI API General Exception - First Chat', [
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

        // Send to OpenAI API with comprehensive error handling
        try {
            Log::info('OpenAI API Request - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'question_length' => strlen($question),
                'system_message_length' => strlen($systemMessage),
                'documents_count' => $documents->count(),
                'has_api_key' => !empty(env('OPENAI_API_KEY')),
            ]);

            $openAiResponse = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(90) // Increase timeout to 90 seconds
                ->connectTimeout(10) // Connection timeout
                ->retry(2, 1000) // Retry 2 times with 1 second delay
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4-turbo',
                    'temperature' => 0.7,
                    'max_tokens' => 3000,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $question
                        ],
                    ],
                ]);

            Log::info('OpenAI API Response - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'successful' => $openAiResponse->successful(),
                'response_length' => strlen($openAiResponse->body()),
            ]);

            DB::table('user_chat_answers')->where('user_id', $user->id)->update(['status2' => 1]);
            DB::table('search_user_chat')->where('id', $chatId)->update(['status2' => 1]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OpenAI API Connection Exception - Follow-up Chat', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'OpenAI API connection timeout. The request took too long to complete.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'ConnectionException',
                    'suggestion' => 'Please try again. If the issue persists, the OpenAI API may be experiencing high load.',
                ]
            ], 504);

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('OpenAI API Request Exception - Follow-up Chat', [
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
                'error' => 'OpenAI API request failed.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'RequestException',
                    'response' => $e->response ? $e->response->body() : null,
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('OpenAI API General Exception - Follow-up Chat', [
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
            Log::error('OpenAI API Unsuccessful Response', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'response_body' => $openAiResponse->body(),
            ]);

            return response()->json([
                'error' => 'OpenAI API request failed.',
                'details' => $openAiResponse->body()
            ], 500);
        }

        $responseContent = $openAiResponse->json('choices.0.message.content');

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
    - For each role, write the role name on one line, then a second line starting with “Goal:” followed by 1–2 sentences.  
    - Only use role titles that actually appear in the company documents.  

📌 Complementary Goals
2 goals, 1 sentence each.

✅ Final Outcome Summary
2 sentences on impact.

EOT;

       // Send to OpenAI API with comprehensive error handling
       try {
           Log::info('OpenAI API Request - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'prompt_length' => strlen($prompt),
               'system_message_length' => strlen($systemMessage),
               'selected_strategy' => $selectedStrategy,
               'has_api_key' => !empty(env('OPENAI_API_KEY')),
           ]);

           $openAiResponse = Http::withToken(env('OPENAI_API_KEY'))
               ->timeout(90) // Increase timeout to 90 seconds
               ->connectTimeout(10) // Connection timeout
               ->retry(2, 1000) // Retry 2 times with 1 second delay
               ->post('https://api.openai.com/v1/chat/completions', [
                   'model' => 'gpt-4-turbo',
                   'temperature' => 0.7,
                   'max_tokens' => 3000,
                   'messages' => [
                       [
                           'role' => 'system',
                           'content' => $systemMessage
                       ],
                       [
                           'role' => 'user',
                           'content' => $prompt
                       ],
                   ],
               ]);

           Log::info('OpenAI API Response - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'status' => $openAiResponse->status(),
               'successful' => $openAiResponse->successful(),
               'response_length' => strlen($openAiResponse->body()),
           ]);

       } catch (ConnectionException $e) {
           Log::error('OpenAI API Connection Exception - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'error_message' => $e->getMessage(),
               'error_code' => $e->getCode(),
               'file' => $e->getFile(),
               'line' => $e->getLine(),
               'trace' => $e->getTraceAsString(),
           ]);

           return response()->json([
               'error' => 'OpenAI API connection timeout. The request took too long to complete.',
               'details' => [
                   'message' => $e->getMessage(),
                   'type' => 'ConnectionException',
                   'suggestion' => 'Please try again. If the issue persists, the OpenAI API may be experiencing high load.',
               ]
           ], 504);

       } catch (RequestException $e) {
           Log::error('OpenAI API Request Exception - Update Strategy', [
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
               'error' => 'OpenAI API request failed.',
               'details' => [
                   'message' => $e->getMessage(),
                   'type' => 'RequestException',
                   'response' => $e->response ? $e->response->body() : null,
               ]
           ], 500);

       } catch (\Exception $e) {
           Log::error('OpenAI API General Exception - Update Strategy', [
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
           Log::error('OpenAI API Unsuccessful Response - Update Strategy', [
               'user_id' => $user->id,
               'chat_id' => $chatId,
               'status' => $openAiResponse->status(),
               'response_body' => $openAiResponse->body(),
           ]);

           return response()->json([
               'error' => 'OpenAI API request failed.',
               'details' => $openAiResponse->body()
           ], 500);
       }

       $updatedSections = $openAiResponse->json('choices.0.message.content');

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
- For each role, write the role name on one line, then a second line starting with "Goal:" followed by 1–2 sentences.
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
            Log::info('OpenAI API Request - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'scenario' => $selectedScenario,
                'strategy' => $selectedStrategy,
                'prompt_length' => strlen($prompt),
                'has_api_key' => !empty(env('OPENAI_API_KEY')),
            ]);

            $openAiResponse = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(90)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4-turbo',
                    'temperature' => 0.7,
                    'max_tokens' => 2500,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

            Log::info('OpenAI API Response - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'successful' => $openAiResponse->successful(),
                'response_length' => strlen($openAiResponse->body()),
            ]);

        } catch (ConnectionException $e) {
            Log::error('OpenAI API Connection Exception - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'OpenAI API connection timeout. The request took too long to complete.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'ConnectionException',
                    'suggestion' => 'Please try again. If the issue persists, the OpenAI API may be experiencing high load.',
                ]
            ], 504);

        } catch (RequestException $e) {
            Log::error('OpenAI API Request Exception - Update Scenario', [
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
                'error' => 'OpenAI API request failed.',
                'details' => [
                    'message' => $e->getMessage(),
                    'type' => 'RequestException',
                    'response' => $e->response ? $e->response->body() : null,
                ]
            ], 500);

        } catch (\Exception $e) {
            Log::error('OpenAI API General Exception - Update Scenario', [
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
            Log::error('OpenAI API Unsuccessful Response - Update Scenario', [
                'user_id' => $user->id,
                'chat_id' => $chatId,
                'status' => $openAiResponse->status(),
                'response_body' => $openAiResponse->body(),
            ]);

            return response()->json([
                'error' => 'OpenAI API request failed.',
                'details' => $openAiResponse->body()
            ], 500);
        }

        $updatedSections = $openAiResponse->json('choices.0.message.content');

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
            $openAiResponse = Http::withToken(env('OPENAI_API_KEY'))
                ->timeout(90)
                ->connectTimeout(10)
                ->retry(2, 1000)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4-turbo',
                    'temperature' => 0.7,
                    'max_tokens' => 2000,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

            if ($openAiResponse->successful()) {
                $brief = $openAiResponse->json('choices.0.message.content');
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
        $currentGoal = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and section headers
            if (empty($line) || strpos($line, '👥') !== false || strpos($line, 'Rephrased Goals') !== false) {
                continue;
            }
            
            // Check if line is a role (usually numbered: 1., 2., etc. or starts with a role name)
            if (preg_match('/^(\d+\.?\s*)?([A-Z][^:]+?):?\s*$/i', $line, $matches)) {
                // Save previous role if exists
                if ($currentRole && $currentGoal) {
                    $roleGoals[] = [
                        'role' => $currentRole,
                        'goal' => $currentGoal,
                        'notes' => ''
                    ];
                }
                $currentRole = trim($matches[2]);
                $currentGoal = '';
            }
            // Check if line starts with "Goal:"
            elseif (preg_match('/^Goal:\s*(.+)$/i', $line, $matches)) {
                $currentGoal = trim($matches[1]);
            }
            // If we have a role but no goal yet, this might be the goal
            elseif ($currentRole && empty($currentGoal) && !empty($line)) {
                $currentGoal = $line;
            }
            // Additional goal text
            elseif ($currentRole && !empty($currentGoal) && !empty($line)) {
                $currentGoal .= ' ' . $line;
            }
        }
        
        // Save last role
        if ($currentRole && $currentGoal) {
            $roleGoals[] = [
                'role' => $currentRole,
                'goal' => $currentGoal,
                'notes' => ''
            ];
        }
        
        return $roleGoals;
    }
}

