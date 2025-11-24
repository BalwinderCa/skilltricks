<?php

namespace App\Services\AI;
use App\Http\Controllers\Backend\AI\AiChatController;
use App\Http\Controllers\Backend\AI\GenerateContentsController;
use App\Models\AiBlogWizardArticle;
use App\Models\AiBlogWizardArticleLog;
use App\Models\AiChatMessage;
use App\Models\CustomTemplate;
use App\Models\Project;
use App\Models\Template;
use App\Services\GenerateCapability\GenerateCapabilityService;
use DeepSeek\DeepSeekClient;

class DeepseekAiService
{
    public function setParams($request, $model)
    {
        $temperature    = (float)$request->creativity;
        
        // ai params
        $aiParams = [
            'model'             => $model,
            'temperature'       => $temperature,
            'presence_penalty'  => 0.6,
            'frequency_penalty' => 0,
            'stream'            => true
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
        $deepseek   = app(DeepSeekClient::class);
        
        $data   = [
            'status'  => 200,
            'success' => true,
        ];

        try {
            $result = $deepseek
                ->query(session('prompt'), 'user')
                ->withModel(session('model'))
                ->setTemperature(request()->content_type == "ai_code" ? 0.0 : 1.5)
                ->run();

            $result = json_decode($result);
            
            $promptsToken            = $result?->usage->prompt_tokens;
            $completionToken         = $result?->usage->completion_tokens;

            $data["promptsToken"]    = $promptsToken;
            $data["completionToken"] = $completionToken;
            $data["tokens"]          = $completionToken + $promptsToken;
            $data["outputContents"]  = $result?->choices[0]?->message->content ?? '';
            
        } catch (\Throwable $th) {
            $message         = localize('There is an issue with the deepseek account');
            $data["message"] = $message;
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

        $deepseek   = app(DeepSeekClient::class);

        return response()->stream(function () use ($deepseek, $project, $user, $opts) {
            $text = '';
            $output = ""; 
            // \Log::info($opts["model"]);
            // Initialize the Deepseek stream
            $data = $deepseek
                ->query($opts['messages'][0]['content'], 'user')
                ->withModel($opts["model"])
                ->withStream(true)
                ->setTemperature(request()->temperature ? (double) request()->temperature : 1.5)
                ->run();
                $chatResponse = explode("data: ", $data);
                // echo $data;
                if (!empty($chatResponse)) {
                    foreach ($chatResponse as $singleData) {
                        if (!empty($singleData)) {
                            $streamData = $singleData;
                            $singleData = json_decode(trim($singleData), true);

                            if (isset($singleData["choices"][0]["delta"]["content"])) {
                                $streamContent = $singleData["choices"][0]["delta"]["content"];
                                $text         .= $streamContent;
                                $output       .= $streamContent;
                                
                                if ($streamContent) {
                                    echo "data: {$streamData} \n\n";
                                    ob_flush();
                                    flush();
                                }
                                
                            }
                        }
                    }
                }
            // Indicate the end of the stream
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

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
            } else {
                $template = CustomTemplate::whereId($project->custom_template_id)->first();
                (new GenerateContentsController())->updateTemplateUsages($tokens, $template, $user, true);
            }

        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type'  => 'text/event-stream',
            'Connection'    => 'keep-alive',
        ]);
    }

    // stream ai chat
    public function streamAiChat($request)
    {
        $user    = auth()->user();
        $chat_id    = session('chat_id');
        $message_id = session('message_id'); 
        $message    = AiChatMessage::whereId((int)$message_id)->first(); 

        $prompt     = $message->prompt; 

        $message->input_prompt = $prompt;
        $message->save();

        $model      =  getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine()); 

        $deepseek   = app(DeepSeekClient::class);

        return response()->stream(function () use ($deepseek, $prompt, $user, $model, $chat_id) {
            $text = '';
            $output = ""; 
            
            // Initialize the Deepseek stream
            $data = $deepseek
                ->query($prompt, 'user')
                ->withModel($model)
                ->withStream(true)
                ->setTemperature(request()->temperature ? (double) request()->temperature : 1.5)
                ->run();
                $chatResponse = explode("data: ", $data);
                // echo $data;
                
                if (!empty($chatResponse)) {
                    foreach ($chatResponse as $singleData) {
                        if (!empty($singleData)) {
                            $streamData = $singleData;
                            $singleData = json_decode(trim($singleData), true);

                            if (isset($singleData["choices"][0]["delta"]["content"])) {
                                $streamContent = $singleData["choices"][0]["delta"]["content"];
                                $text         .= $streamContent;
                                $output       .= $streamContent;
                                
                                if ($streamContent) {
                                    echo "data: {$streamData} \n\n";
                                    ob_flush();
                                    flush();
                                }
                                
                            }
                        }
                    }
                }
            # Update credit balance
            $random_number = time();
            $words   = count(explode(' ', $text));
            $output  = str_replace(["\r\n", "\r", "\n"], "<br>", $text);

            AiChatMessage::updateOrCreate([
                'random_number' => $random_number
            ], [
                'ai_chat_id'    => $chat_id,
                'user_id'       => $user->id,
                'response'      => $text,
                'result'        => $output,
                'words'         => $words,
            ]);

            // Indicate the end of the stream
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

            
            $completionToken = count(explode(' ', $text));

            (new AiChatController())->updateUserWords($completionToken, $user);

        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type'  => 'text/event-stream',
            'Connection'    => 'keep-alive',
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
        // Log::info("Options for the Model : ".json_encode($opts)); 
        session()->put('ai_blog_wizard_article_id_for_balance', $aiBlogWizardArticle->id);
        session()->save();
 
        # make api call to openAi
        
        $deepseek   = app(DeepSeekClient::class);

        return response()->stream(function () use ($deepseek, $model, $user, $aiBlogWizardArticle, $prompt){
            # 1. init
            $text = "";
            $output = ""; 
            
            // Initialize the Deepseek stream
            $data = $deepseek
                ->query($prompt, 'user')
                ->withModel($model)
                ->withStream(true)
                ->setTemperature(1.5)
                ->run();
                $chatResponse = explode("data: ", $data);
                // echo $data;
                
                if (!empty($chatResponse)) {
                    foreach ($chatResponse as $singleData) {
                        if (!empty($singleData)) {
                            $streamData = $singleData;
                            $singleData = json_decode(trim($singleData), true);

                            if (isset($singleData["choices"][0]["delta"]["content"])) {
                                $streamContent = $singleData["choices"][0]["delta"]["content"];
                                $text         .= $streamContent;
                                $output       .= $streamContent;
                                
                                if ($streamContent) {
                                    echo "data: {$streamData} \n\n";
                                    ob_flush();
                                    flush();
                                }
                                
                            }
                        }
                    }
                    
                    $aiBlogWizardArticle->value         = $text;
                    $words                              = count(explode(' ', ($text)));
                    $aiBlogWizardArticle->total_words   = $words;
                    $aiBlogWizardArticle->save();
                } 
                

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
