<?php

namespace App\Http\Controllers\Backend\AI;

use App\Models\AiChat;
use Illuminate\Support\Str;
use App\Models\AiChatPrompt;
use App\Models\MediaManager;
use Illuminate\Http\Request;
use App\Models\AiChatMessage;
use Orhanerday\OpenAi\OpenAi;
use App\Models\AiChatCategory;
use App\Models\AiChatPromptGroup;
use App\Models\SubscriptionPackage;
use App\Traits\GenerateVoiceToText;
use App\Http\Controllers\Controller;
use App\Http\Services\GenerateImage;

class AiImageChatController extends Controller
{
    use GenerateVoiceToText;
    public function __construct()
    {
        if (getSetting('enable_ai_image_chat') == '0') {
            flash(localize('AI Chat Image is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    # chat index
    public function index(Request $request)
    {

        $searchKey = null;
        $user = auth()->user();
        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_image_chat == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_image_chat')) {
                abort(403);
            }
        }
        $chatExpert = AiChatCategory::where('type', 'image')->first();
        self::storeFirstMessage($chatExpert->id);
        $chatListQuery = AiChat::orderBy('updated_at', 'DESC')->with('messages', 'category')->where('user_id', $user->id)->where('ai_chat_category_id', $chatExpert->id);

        if ($request->search != null) {
            $chatListQuery = $chatListQuery->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        if ($request->expert != null) {
            $chatList     = $chatListQuery->where('ai_chat_category_id', $chatExpert->id)->get();
        } else {
            $chatList     = $chatListQuery->where('ai_chat_category_id', $chatExpert->id)->get();
        }


        $promptGroups       = AiChatPromptGroup::oldest();
        $promptGroups       = $promptGroups->get();
        $prompts            = AiChatPrompt::latest()->get();

        $conversation = $chatListQuery->first();
        return view('backend.pages.aiChatImage.index', compact('chatExpert', 'chatList', 'conversation', 'searchKey', 'promptGroups', 'prompts'));
    }

    # new message
    public function newMessage(Request $request)
    {

        try {

            $chat = AiChat::where('id', $request->chat_id)->first();
            $category = AiChatCategory::where('type', 'image')->first();

            $user = auth()->user();

            // check word limit; need to have min 10 words balance
            if (isCustomer() && availableDataCheck('words') <= 10) {
                $data = [
                    'status'                => 400,
                    'ai_chat_category_id'   => $category->id,
                    'success'               => false,
                    'message'               => localize('Your word balance is low, please upgrade you plan'),
                ];

                return $data;
            }


            $prompt = $request->prompt;

            $message                = new AiChatMessage;
            $message->ai_chat_id    = $chat->id;
            $message->user_id       = $user->id;
            $message->prompt        = $prompt;
            $message->result        = $prompt;
            $message->type          = 'image';
            $message->save();

            $message->aiChat->touch(); // updated at

            $chat_id = $chat->id;
            $message_id = $message->id;


            $message    = AiChatMessage::whereId((int)$message_id)->first();

            $chat                     = AiChat::whereId((int) $chat_id)->first();
            $lastThreeMessageQuery    = $chat->messages()->whereNotNull('prompt')->latest()->take(4);
            $lastThreeMessage         = $lastThreeMessageQuery->get()->reverse();


            $expert = $chat->category;



            $prompt    = '';
            $newPrompt = [];

            if ($lastThreeMessage != null) {
                $trainedData = $lastThreeMessage;
                foreach ($trainedData as $data) {

                    $msg = [
                        "role"      => 'user',
                        "content"   => $data->prompt,
                    ];
                    $history[] = $msg;
                }
                $prompt = "You will now play a character and respond as that character (You will never break character). Your name is $expert->short_name. I want you to act as a $expert->role. As a $expert->role please answer this, $message->prompt. Do not include your name, role in your answer.";
            } else {
                $prompt    = $message->prompt;
                $history[] = ["role" => "system", "content" => "You are a helpful assistant."];
            }

            $message->input_prompt = $prompt;
            $message->save();

            if (count($lastThreeMessage) > 1) {
                foreach ($lastThreeMessage as $key => $threeMessage) {
                    if ($key != 0) {
                        if ($threeMessage->prompt != null) {
                            $history[] = ["role" => "user", "content" => $threeMessage->prompt];
                        } else {
                            $history[] = ["role" => "assistant", "content" => $threeMessage->response];
                        }
                    } else {
                        $newPrompt = ["role" => "user", "content" => $prompt];
                    }
                }
            } else {
                $newPrompt = ["role" => "user", "content" => $prompt];
            }

            $history[] = $newPrompt;

            $history = json_encode($history);
            $open_ai = new OpenAi(openAiKey());

            $messages =  [[
                'role' => 'user',
                'content' => "Write what does user want to draw at the last moment of chat history. \n\n\nChat History: $history \n\n\n\n Result is 'Draw an image of ... "
            ]];
            $chatOpts    = [
                'model'             =>  "gpt-3.5-turbo",
                'messages'          => $messages,
                'temperature'       => 1.0,
                'presence_penalty'  => 0.6,
                'frequency_penalty' => 0,
                'stream'            => false
            ];
            $completion    = $open_ai->chat($chatOpts);
            $completion    = json_decode($completion);

            $contentPrompt = $completion->choices[0]->message->content;
            $generateImage = new GenerateImage();
            $result        = $generateImage->generateImageByOpenAi(null, 'imageChat', $contentPrompt);
            $storage_type  = activeStorage('aws') ? 'aws' : 'local';
            $revers_prompt = $result['revers_prompt'];
            $completionToken = count(explode(' ', $contentPrompt));
            $file_path = null;

            if ($result['success'] == true) {
                $file_path       = $storage_type == 'aws' ?  $result['files'][0]['file_path'] : $result['files'][0]['file_path'];
            }

            if ($file_path == null) {
                $data = [
                    'status'              => 419,
                    'file_path'           => '',
                    'message'             => $result['message']
                ];
                return response()->json(['response' => $data]);
            }

            $message = new AiChatMessage();
            $message->ai_chat_id    = $chat_id;
            $message->user_id       = $user->id;
            $message->storage_type  = $storage_type;
            $message->file_path     = $file_path;
            $message->revers_prompt = $revers_prompt;
            $message->words         = $completionToken;
            $message->save();

            $this->updateUserWords($completionToken, $user);

            $this->updateUserImages($user, 1);
            $data = [
                'status'              => 200,
                'file_path'           => $storage_type == 'aws' ? $result['files'][0]['file_path'] : asset('public/' . $result['files'][0]['file_path']),
                'message'             => ''
            ];
            return response()->json(['response' => $data]);
            return $data;
        } catch (\Exception $e) {

            $data = [
                'status'  => 404,
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ];
            return $data;
        }
    }
    # updateUserWords - take token as word 
    public function updateUserWords($tokens, $user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('words', $tokens, $user);
        }
    }
    # updateUserImages - take n
    public function updateUserImages($user, $n)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('images', $n, $user);
        }
    }

    # updateBalanceStopGeneration
    public function updateBalanceStopGeneration(Request $request)
    {
        $random_number = session()->get('random_number');
        $user = auth()->user();
        if ($random_number && $user->user_type == "customer") {
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
        $chatExpert = AiChatCategory::where('type', 'image')->first();
        $data = [
            'status'            => 200,
            'messagesContainer' => view('backend.pages.aiChatImage.inc.messages-container', compact('chatExpert', 'conversation', 'promptGroups', 'prompts'))->render(),
        ];
        return $data;
    }
    #new message with file
    public function newMessageWithFile(Request $request)
    {

        $prompt = $request->prompt;
        $vision_images = explode(',', $request->images);
        $mediaFiles = MediaManager::whereIn('id', $vision_images)->get();
        return [
            'status' => 200,
            'prompt' => $prompt,
            'mediaFiles' => view('backend.pages.aiChatImage.inc.new-message-with-file', compact('mediaFiles', 'prompt'))->render()
        ];
    }
    # store first message
    private static function storeFirstMessage($ai_chat_category_id)
    {
        $user = auth()->user();
        $expert = AiChatCategory::whereId((int)$ai_chat_category_id)->first();
        $conversation = AiChat::where('user_id', $user->id)->where('ai_chat_category_id', $ai_chat_category_id)->first();
        if (!$conversation) {
            $conversation                      = new AiChat;
            $conversation->user_id             = $user->id;
            $conversation->ai_chat_category_id = $ai_chat_category_id;
            $conversation->title               = $expert->name . localize(' Chat');
            $conversation->save();
        }

        $message = AiChatMessage::where('ai_chat_id', $conversation->id)->first();
        if (!$message) {
            $message             = new AiChatMessage;
            $message->ai_chat_id = $conversation->id;
            $message->user_id    = $user->id;
            $result          = localize("Hello! I am $expert->name, and I'm $expert->role. $expert->assists_with.");
            $message->response   = $result;
            $message->result     = $result;
            $message->save();
        }
    }
    public function recordVoiceToText(Request $request)
    {
        $response = $this->openAiVoiceToText($request);
        return response()->json($response);
    }
}
