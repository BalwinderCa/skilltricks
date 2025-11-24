<?php

namespace App\Http\Controllers\Backend\AI;

use App\Models\AiChat;
use App\Services\UserService;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use App\Models\AiChatPrompt;
use App\Models\MediaManager;
use Illuminate\Http\Request;
use App\Models\AiChatMessage;
use Orhanerday\OpenAi\OpenAi;
use App\Models\AiChatCategory;
use App\Models\AiChatPromptGroup;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;

class VisionController extends Controller
{
    protected $client;
    public function __construct()
    {
        if (getSetting('enable_ai_vision') == '0') {
            flash(localize('AI Vision is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    # chat index
    public function index(Request $request)
    {

        $searchKey = null;
        $user = auth()->user();
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_vision == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_vision')) {
                abort(403);
            }
        }
        $chatExpert = AiChatCategory::where('type', 'vision')->first();
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
        $conversation       = $chatListQuery->first();
        $supportedModelMessage  = !in_array('gpt-4o', openAiSupportedModels()) ? localize('Your Current Open AI API key does not support GPT 4o.') : null;
        
        return view('backend.pages.aiVision.index', compact('chatExpert', 'chatList', 'conversation', 'searchKey', 'promptGroups', 'prompts', 'supportedModelMessage'));
    }
    # new message
    public function newMessage(Request $request)
    {
        try{
            $chat = AiChat::where('id', $request->chat_id)->first();
            $category = AiChatCategory::where('type', 'vision')->first();

            $user = auth()->user();
            if(!$request->vision_images) {
                $data = [
                    'status'                => 400,
                    'ai_chat_category_id'   => $category->id,
                    'success'               => false,
                    'message'               => localize('Please select Image'),
                ];
                return $data;
            }
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
            $total_used_tokens = 0;

            $vision_images = explode(',', $request->vision_images);
            $images = [];
            if($vision_images) {
                $media_images = MediaManager::whereIn('id', $vision_images)->get('media_file');
                foreach($media_images as $image) {
                        $images[] =
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => asset('public/'.$image->media_file),
                                'detail'=>'low'
                            ]
                        ];
                }
            }

            $message                = new AiChatMessage;
            $message->ai_chat_id    = $chat->id;
            $message->user_id       = $user->id;
            $message->prompt        = $prompt;
            $message->result        = $prompt;
            $message->type          = 'vision';
            $message->images        = json_encode($images);
            $message->save();

            $message->aiChat->touch(); // updated at

            $chat_id = $chat->id;
            $message_id = $message->id;


            $request->session()->put('chat_id', $chat_id);
            $request->session()->put('message_id', $message_id);
            $request->session()->put('category_id', $category->id);

            $data = [
                'status'              => 200,
                'ai_chat_category_id' => $category->id,
                'success'             => false,
                'message'             => '',
            ];
            return $data;
        }catch(\Exception $e){
            $data = [
                'status'              => 404,
                'ai_chat_category_id' => '',
                'success'             => false,
                'message'             => $e->getMessage(),
            ];
            return $data;
        }

    }
    # ai response
    public function process()
    {

        try {
            $chat_id    = session('chat_id');
            $message_id = session('message_id');

            $message    = AiChatMessage::whereId((int)$message_id)->first();

            $chat                     = AiChat::whereId((int) $chat_id)->first();
            $lastThreeMessageQuery    = $chat->messages()->where('prompt', null)->latest()->take(4);
            $lastThreeMessage         = $lastThreeMessageQuery->get()->reverse();


            $expert = $chat->category;
            $expert->chat_training_data = str_replace(array("\r", "\n"), '', $expert->chat_training_data) ?? null;


            $prompt    = '';
            $newPrompt = [];

            $prompt    = $message->prompt;

            $message->save();


            $model =  openAiModel('chat');

            # 1. init openAi
            $open_ai = new OpenAi(openAiKey());
            $user    = auth()->user();

            $images = $message->images ? json_decode($message->images) : [];

            $content = [
                [
                    'type' => 'text',
                    'text' => $prompt,
                ]
            ];

            $opts = [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                    'role' => 'user',
                    'content' => array_merge($content, $images)
                    ],
                ],
                'max_tokens' => 2000,
                'stream' => true
            ];
            $random_number = time();
            session()->put('random_number', $random_number);
            session()->save();

        return response()->stream(function () use ($chat_id, $open_ai, $user, $opts, $random_number, $prompt, $content, $images) {
            $text = "";

            $open_ai->chat($opts, function ($curl_info, $data) use (&$text, $chat_id, $user, $random_number, $images) {
                $chatResponse = explode("data:", $data);

                if (!empty($chatResponse)) {
                    $output = "";
                    foreach ($chatResponse as $singleData) {
                        if (!empty($singleData)) {
                            $singleData = json_decode(trim($singleData), true);

                            if (isset($singleData["choices"][0]["delta"]["content"])) {
                                $content = $singleData["choices"][0]["delta"]["content"];
                                $text   .= $content;
                                $output .= $content;
                            }
                        }
                    }
                }
                # Update credit balance
                $words   = count(explode(' ', $text));
                $output  = str_replace(["\r\n", "\r", "\n"], "<br>", $text);

                $message = AiChatMessage::updateOrCreate([
                    'random_number' => $random_number
                ], [
                    'ai_chat_id'    => $chat_id,
                    'user_id'       => $user->id,
                    'response'      => $text,
                    'result'        => $output,
                    'words'         => $words,
                    'type'         => 'vision',
                ]);
                echo $data;
                echo "\n\n";
                echo PHP_EOL;

                if (ob_get_level() < 1) {
                    ob_start();
                }
                ob_flush();
                flush();
                return strlen($data);
            });

            $completionToken = count(explode(' ', $text));

            (new UserService())->updateUserWords($completionToken);
        }, 200, [
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Content-Type'      => 'text/event-stream',
        ]);
        } catch (\Throwable $th) {
            throw $th;
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
        $chatExpert         = AiChatCategory::where('type', 'vision')->first();
        $data = [
            'status'            => 200,
            'messagesContainer' => view('backend.pages.aiVision.inc.messages-container', compact('conversation', 'chatExpert', 'promptGroups', 'prompts'))->render(),
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
            'prompt'=>$prompt,
            'mediaFiles' => view('backend.pages.aiVision.inc.new-message-with-file', compact('mediaFiles', 'prompt'))->render()
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
}
