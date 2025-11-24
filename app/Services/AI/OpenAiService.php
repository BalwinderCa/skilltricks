<?php

namespace App\Services\AI;

use App\Http\Controllers\Backend\AI\AiChatController;
use App\Http\Controllers\Backend\AI\GenerateContentsController;
use App\Http\Services\SerperService;
use App\Models\AiBlogWizardArticle;
use App\Models\AiBlogWizardArticleLog;
use App\Models\AiChat;
use App\Models\AiChatMessage;
use App\Models\CustomTemplate;
use App\Models\Project;
use App\Models\Template;
use App\Models\Document;
use App\Services\GenerateCapability\GenerateCapabilityService;
use Orhanerday\OpenAi\OpenAi;

class OpenAiService
{
    public $open_ai;

    public function __construct()
    {
        $this->open_ai = new OpenAi(openAiKey());
    }
  
    public function setParams($request, $model , $stream = true)
    {
        $temperature    = (float)$request->creativity;
        
        // ai params
        $aiParams = [
            'model'             => $model,
            'temperature'       => $temperature,
            'presence_penalty'  => 0.6,
            'frequency_penalty' => 0,
            'stream'            => $stream
        ];
        
        $max_tokens            = session('max_tokens');

        if ($max_tokens != -1) {
            $aiParams['max_tokens'] = $max_tokens;
        }

        # opts
        $aiParams['messages'] = [[
            "role" => "user",
            "content" => session('prompt')
        ]];
        
        session()->put('aiParams', $aiParams);
    }
  
    public function contentGenerator($request)
    {
        return $request->has("stream") && $request->stream ? $this->streamCompletion($request) : $this->rawCompletion($request);
    }

    # rawCompletion
    public function rawCompletion()
    {
        $params = [
            'model'    => session('model'),
            'messages' => [
                [
                    "role"    => "system",
                    "content" => session('system_command'),
                ],
                [
                    "role"    => "user",
                    "content" => session('prompt'),
                ],
            ],
            'temperature' => 1,
            'max_tokens'  => 4000,
        ];

        if(session('num_of_results')){
            $params['n'] = (int) session('num_of_results');
        }

        $result = $this->open_ai->chat($params);

        
        $result = json_decode($result, true);
        
        $data   = [
            'status'  => 200,
            'success' => true,
        ];
        
        if (isset($result['choices'])) {
            
            $data["promptsToken"]    = $result['usage']['prompt_tokens'];
            $data["completionToken"] = $result['usage']['completion_tokens'];
            $data["tokens"]          = $result['usage']['total_tokens'];

            if (count($result['choices']) > 1) {
                $tempOutputContents = [];
                foreach ($result['choices'] as $value) {
                    $tempOutputContents[] = $value['message']['content'];
                }
                $data["outputContents"]  = $tempOutputContents;
            }else{
                $data["outputContents"]  = $result['choices'][0]['message']['content'];
            }
        }else{
            if (isset($result['error']['message'])) {
                $data["message"] = $result['error']['message']; 
            } else {
                $message = localize('There is an issue with the openai account');
                $data["message"] = $message; 
            }
            $data["status"]  = 400;
            $data["success"] = false;
        }

        return $data;

    }

    # streamCompletion
    public function streamCompletion($request)
    {
        switch ($request->content_type) {
            case 'template_content':
                return $this->streamTemplateAndRewriterContents($request);
                break;
                
            case 'ai_chat':
                return $this->streamAiChat($request);
                break;
                    
            case 'ai_rewriter':
                return $this->streamTemplateAndRewriterContents($request);
                break;

            case 'ai_blog_wizard':
                return $this->streamBlogWizardArticle($request);
                break;
            default:
                # code...
                break;
        }
    }

    // stream template contents
    public function streamTemplateAndRewriterContents($request)
    {
        $user            = auth()->user();
        $opts            = session('aiParams');
        $project_id      = session('project_id');
        $project         = Project::where('id', $project_id)->first();

        $promptsToken     = count(explode(' ', $opts['messages'][0]['content']));
        $project->prompts = $promptsToken;
        $project->input_prompt = $opts['messages'][0]['content'];

        if ($project->template_id) {
            if (!empty(GenerateContentsController::wordBalanceCheck())) {
                return GenerateContentsController::wordBalanceCheck();
            }
        } elseif ($project->custom_template_id) {
            if (!empty(GenerateContentsController::wordBalanceCheck('allow_custom_templates'))) {
                return GenerateContentsController::wordBalanceCheck('allow_custom_templates');
            }
        }
        
        return response()->stream(function () use ($project, $user, $opts){

            $text   = "";
            $output = "";


            $this->open_ai->chat($opts, function ($curl_info, $data) use (&$text, &$project, &$user) {
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
                    $text = str_replace(["\r\n", "\r", "\n"], "<br>", $text);
                    $project->content = $text;

                    $completionToken     = count(explode(' ', $text));
                    $project->completion = $completionToken;
                    $project->words      = $project->prompts + $completionToken;
                    $project->save();


                }
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
            $tokens          = $project->prompts + $completionToken;

            (new GenerateContentsController())->updateUserWords($tokens, $user);

            $latestPackage      = activePackageHistory();
            $previousBalance    = $latestPackage ? $latestPackage->this_month_available_words : null;
            $after_balance      = $latestPackage ? $latestPackage->this_month_available_words - $tokens : null;

            # keep log
            $logData                      =  [
                'user_id'                 => $project->user_id,
                'project_id'              => $project->id,
                'subscription_history_id' => $latestPackage ? $latestPackage->id : null,
                'subscription_package_id' => $latestPackage ? $latestPackage->subscription_package_id : null,
                'template_id'             => $project->template_id != null ? $project->template_id : null,
                'custom_template_id'      => $project->custom_template_id != null ? $project->custom_template_id : null,
                'model_name'              => $project->model_name,
                'content'                 => $output,
                'content_type'            => $project->content_type,
                'words'                   => $tokens,
                'prompt_words'            => $project->prompts,
                'completion_words'        => $completionToken,
                'previous_balance'        => $previousBalance,
                'after_balance'           => $after_balance
            ];
            (new GenerateContentsController())->createLog($logData);

            # update template usage
            if(!is_null($project->template_id)) {
                $template = Template::whereId($project->template_id)->first();
                (new GenerateContentsController())->updateTemplateUsages($tokens, $template, $user);
            } elseif(!is_null($project->custom_template_id)) {
                $template = CustomTemplate::whereId($project->custom_template_id)->first();
                (new GenerateContentsController())->updateTemplateUsages($tokens, $template, $user, true);
            }
        }, 200, [
            'X-Accel-Buffering' => 'no',
            'Cache-Control'     => 'no-cache',
            'Content-Type'      => 'text/event-stream',
        ]);
    }

    // stream ai chat
    public function streamAiChat($request)
    {
        $chat_id    = session('chat_id');
        $message_id = session('message_id');
        $realTime   = session('real_time_data');
        $message    = AiChatMessage::whereId((int)$message_id)->first();

        $chat                     = AiChat::whereId((int) $chat_id)->first();
        $lastThreeMessageQuery    = $chat->messages()->where('prompt', null)->latest()->take(4);
        $lastThreeMessage         = $lastThreeMessageQuery->get()->reverse();


        $expert = $chat->category;
        $expert->chat_training_data = str_replace(array("\r", "\n"), '', $expert->chat_training_data) ?? null; // TODO : Training like

        // Get user's documents for company context
        $user = auth()->user();
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

        $prompt    = '';
        $newPrompt = [];

        if ($expert->chat_training_data != null) {
            $trainedData = json_decode(json_decode($expert->chat_training_data));
            foreach ($trainedData as $data) {
                $msg = [
                    "role"      => $data->role,
                    "content"   => $data->content,
                ];
                $history[] = $msg;
            }
            $prompt = "You will now play a character and respond as that character (You will never break character). Your name is $expert->short_name. I want you to act as a $expert->role. As a $expert->role please answer this, $message->prompt. Do not include your name, role in your answer.";
            
            // Add document context to system message if available
            if (!empty($documentContext)) {
                // Find system message in history and append context
                $systemFound = false;
                foreach ($history as $key => $msg) {
                    if ($msg['role'] === 'system') {
                        $history[$key]['content'] .= $documentContext;
                        $systemFound = true;
                        break;
                    }
                }
                // If no system message found, add one at the beginning
                if (!$systemFound) {
                    array_unshift($history, ["role" => "system", "content" => "You are a helpful assistant." . $documentContext]);
                }
            }
        } else {
            $prompt    = $message->prompt;
            $systemMessage = "You are a helpful assistant.";
            if (!empty($documentContext)) {
                $systemMessage .= $documentContext;
            }
            $history[] = ["role" => "system", "content" => $systemMessage];
        }

        $message->input_prompt = $prompt;
        $message->save();

        /**
         * Todo::Promt generating as like Sofliq -- always sent expert message & User prompt
         * */
        if (count($lastThreeMessage) > 1 && !$realTime) {
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
        }
        elseif($realTime && getSetting('serper_api_key') !=null){

            $serper = new SerperService(getSetting('serper_api_key'));
            $question  = [
              'q'=> $message->prompt
            ];
            $search = $serper->search($question);
            $final_prompt =
            "Prompt: " . $message->prompt.
            '\n\nWeb search json results: '
            .json_encode($search).
            '\n\nInstructions: Based on the Prompt generate a proper response with help of Web search results(if the Web search results in the same context).Only if the prompt require links: (make curated list of links and descriptions using only the <a target="_blank">,write links with using <a target="_blank"> with mrgin Top of <a> tag is 5px and start order as number and write link first and then write description).Must not write links if its not necessary. Must not mention anything about the prompt text.';
            // unset($history);
            $newPrompt = ["role" => "user", "content" => $final_prompt];
        }
        else {
            $newPrompt = ["role" => "user", "content" => $prompt];
        }

        $history[] = $newPrompt;

        $model =  openAiModel('chat');
        # 1. init openAi
        $open_ai = new OpenAi(openAiKey());
        $user    = auth()->user();
        $opts    = [
            'model'             => $model,
            'messages'          => $history,
            'temperature'       => 1.0,
            'presence_penalty'  => 0.6,
            'frequency_penalty' => 0,
            'stream'            => true
        ];
        if(getSetting('max_tokens')){
            $opts["max_tokens"] = (int) getSetting('max_tokens');
        }

        $random_number = time();
        session()->put('random_number', $random_number);
        session()->save();

        return response()->stream(function () use ($chat_id, $open_ai, $user, $opts, $random_number, $prompt) {
            $text = "";
            $open_ai->chat($opts, function ($curl_info, $data) use (&$text, $chat_id, $user, $random_number) {

                if ($obj = json_decode($data) and $obj->error->message != "") {
                    echo (json_encode($obj->error->message));
                } else {
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
                    ]);
                }
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

            (new AiChatController())->updateUserWords($completionToken, $user);
        }, 200, [
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Content-Type'      => 'text/event-stream',
        ]);
    }

    // stream blog wizard articles
    public function streamBlogWizardArticle($request){
        $aiBlogWizardArticle        = AiBlogWizardArticle::where('id', session('ai_blog_wizard_article_id'))->first();
        $user                       = auth()->user();
        
        # ai prompt
        $promptOutlines            = session('outlines');
        $title                     = session('title');
        $keywords                  = session('keywords');
        $lang                      = session('lang');
        $request_max_tokens        = session()->get('request_max_tokens');
        $article_generate_max_word = getArticleGenMaxWord();

        if(isCustomer()){
            $isAllowed        = (new GenerateCapabilityService())->checkGenerateCapability( $article_generate_max_word);
            if(!$isAllowed) {
                return balanceError();
            }
        }

        $prompt = promptGenerator($lang,$title, $promptOutlines);

        $promptsToken                       = count(explode(' ', $prompt));
        $aiBlogWizardArticle->prompt_tokens = $promptsToken;
        $model                              = getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine());
        
        // session forget every stream
        session()->forget('request_max_tokens');

        $opts = [
            'model'    => $model,
            'messages' => [[
                "role"    => "user",
                "content" => $prompt
            ]],
            'temperature'       => 1.0,
            'presence_penalty'  => 0.6,
            'frequency_penalty' => 0,
            'stream'            => true
        ];

        if (getSetting('max_tokens')) {
            $opts["max_tokens"] = (int) getSetting('max_tokens');
        }
        // Max Token Assign
        ($article_generate_max_word > 0 ? $opts['max_tokens'] = (int) $article_generate_max_word : null);


        // Log::info("Options for the Model : ".json_encode($opts));

        session()->put('ai_blog_wizard_article_id_for_balance', $aiBlogWizardArticle->id);
        session()->save();
 
        # make api call to openAi
        return response()->stream(function () use ($opts, $user, $aiBlogWizardArticle, $prompt){
            # 1. init openAi
            $open_ai = $this->open_ai;
            $text = "";
            $open_ai->chat($opts, function ($curl_info, $data) use (&$text, &$aiBlogWizardArticle, &$user) {
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
                    $aiBlogWizardArticle->value         = $text;
                    $words                              = count(explode(' ', ($text)));
                    $aiBlogWizardArticle->total_words   = $words;
                    $aiBlogWizardArticle->save();
                }

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
            $promptsToken    = count(explode(' ', $prompt));
            $tokens          = $promptsToken + $completionToken;

            if (isCustomer()) {
                updateDataBalance('words', $completionToken, $user);
            }

            $aiBlogWizardArticle->save();

            $aiBlogWizard                 = $aiBlogWizardArticle->aiBlogWizard;
            $aiBlogWizard->completed_step = 5;
            $aiBlogWizard->total_words    += $tokens;
            $aiBlogWizard->save();

            // log
            $aiBlogWizardArticleLog                            = new AiBlogWizardArticleLog();
            $aiBlogWizardArticleLog->user_id                   = $user->id;
            $aiBlogWizardArticleLog->ai_blog_wizard_id         = $aiBlogWizard->id;
            $aiBlogWizardArticleLog->ai_blog_wizard_article_id = $aiBlogWizardArticle->id;
            $aiBlogWizardArticleLog->subscription_history_id   = session('subscription_history_id');
            $aiBlogWizardArticleLog->total_words               = $aiBlogWizardArticle->total_words;
            $aiBlogWizardArticleLog->prompt_tokens             = $promptsToken;
            $aiBlogWizardArticleLog->save();

        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
        ]);
    }
}
