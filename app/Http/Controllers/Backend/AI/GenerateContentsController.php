<?php

namespace App\Http\Controllers\Backend\AI;

use App\Models\Project;
use App\Models\Template;
use App\Models\ProjectLog;
use App\Services\AiService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TemplateUsage;
use App\Models\CustomTemplate;
use App\Http\Controllers\Controller;
use App\Services\Integration\IntegrationService;

class GenerateContentsController extends Controller
{
    # generate contents
    public function generate(Request $request, AiService $aiService)
    {
        try {
            $user = user();

            $template = $aiService->getTemplateByCode($request->template_code);
            if (empty($template)) {
                abort(404);
            }

            # verify if user has access to the template [template available in subscription package]
            if (isCustomer()) {
                // check package balance
                $checkBalanceData = activePackageBalance();
                if (!empty($checkBalanceData)) {
                    return $checkBalanceData;
                }
                // check word limit
                if (availableDataCheck('words') <= 10) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Your word balance is low, please upgrade you plan'),
                    ];
                    return $data;
                    flash(localize('Your word balance is low, please upgrade you plan'))->warning();
                    return redirect()->route('subscriptions.index');
                }
            }

            # apply ai model based on admin configuration or subscription
            $model          = getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine());

            # ------------------------------------------------------------
            $max_tokens     = getSetting('default_max_result_length', -1);

            if ($request->max_tokens != null) {
                $max_tokens     = (int)$request->max_tokens;
            }
 
            session()->put('max_tokens', $max_tokens);

            $inputAll = $request->all();
            $inputAll['max_tokens'] = $max_tokens; 

            $parsePromptsController = new ParsePromptsController;
            $prompt                 = $parsePromptsController->index($inputAll);

            if (preg_match("/bad_words_found/i", $prompt) == 1) {
                $badWords =  explode('_#themeTags', rtrim($prompt, ","));
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Please remove these words from your inputs') . '-' . $badWords[1],
                ];
                return $data;
            }
            session()->put('prompt', $prompt);


            # SET AI PARAMS STARTS
            $integrationService = new IntegrationService(); 
            $integrationService->setParams(templatesEngine(), $request, $model);
            # SET AI PARAMS ENDS

            if($request->project_id != null){
                $project = Project::where('user_id', $user->id)->findOrFail($request->project_id);
                $request->session()->put('project_id', $project->id);
            }else{
                $projectTitle = "Untitled Project - " . date("Y-m-d");
                $project = new Project;
                $project->user_id       = $user->id;
                $project->template_id   = $template->id;
                $project->model_name    = $model;
                $project->title         = $projectTitle;
                $project->slug          = preg_replace('/\s+/', '-', trim($projectTitle)) . '-' . strtolower(Str::random(5));
                $project->content_type  = 'content';
                $project->save();
                $request->session()->put('project_id', $project->id);
            }

            session()->put('template_id', $template->id);

            $data = [
                'status'            => 200,
                'success'           => true,
                'title'             => $project->title,
                'project_id'        => $project->id ?? ''
            ];
            return $data;
        }
        catch (\Throwable $e){
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => $e->getMessage(),
                "data" => errorArray($e)
            ];

            \Log::info("Template Generate Contents Error : " . json_encode($data));

            return $data;
        }
    }

    # generate contents
    public function generateCustom(Request $request)
    {
        $user = auth()->user();

        $template = CustomTemplate::where('code', $request->template_code)->first();
        if (empty($template)) {
            abort(404);
        }

        # 2. verify if user has access to the template [template available in subscription package]
        if ($user->user_type == "customer") {
            // check package balance

            $checkBalanceData = activePackageBalance('allow_custom_templates');
            if (!empty($checkBalanceData)) {
                return $checkBalanceData;
            }
            // check word limit
            if (availableDataCheck('words') <= 0) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Your word balance is low, please upgrade you plan'),
                ];
                return $data;
            }
        }
        
        # apply ai model based on admin configuration or subscription
        $model          = getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine());

        # generate prompt in selected language
        $max_tokens     = getSetting('default_max_result_length', -1);

        if ($request->max_tokens != null) {
            $max_tokens     = (int)$request->max_tokens;
        }

        session()->put('max_tokens', $max_tokens);
        
        # 4. generate prompt
        $prompt  = $template->prompt;

        foreach ($request->all() as $name => $inpVal) {
            if ($name != '_token' && $name != 'project_id' && $name != 'max_tokens') {
                $name = '{_' . $name . '_}';
                if (!is_null($inpVal) && !is_null($name)) {
                    $prompt = str_replace($name, $inpVal, $prompt);
                } else {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Your input does not match with the custom prompt'),
                    ];
                    return $data;
                }
            }
        }
 
        session()->put('prompt', $prompt);

        if ($max_tokens != -1) {
            $aiParams['max_tokens'] = $max_tokens;
            $prompt .= 'Write in ' . $request->lang . ' language.'  . ' The tone of voice should be ' . $request->tone . ' and the output must be completed in ' . $max_tokens . ' words. Do not write translations.';
        }  else{
            $prompt .= 'Write in ' . $request->lang . ' language.'  . ' The tone of voice should be ' . $request->tone . '. Do not write translations.';
        }

        
        # SET AI PARAMS STARTS
        $integrationService = new IntegrationService(); 
        $integrationService->setParams(templatesEngine(), $request, $model);
        # SET AI PARAMS ENDS

        if($request->project_id != null){
            $project = Project::whereId($request->project_id)->first();
            $request->session()->put('project_id', $project->id);
        }else{
            $projectTitle = "Untitled Project - " . date("Y-m-d");
            $project = new Project;
            $project->user_id       = $user->id;
            $project->custom_template_id   = $template->id;
            $project->model_name    = $model;
            $project->title         = $projectTitle;
            $project->slug          = preg_replace('/\s+/', '-', trim($projectTitle)) . '-' . strtolower(Str::random(5));
            $project->content_type  = 'content';
            $project->save();
            $request->session()->put('project_id', $project->id);
        }

        $request->session()->put('template_id', $template->id);

        $data = [
            'status'            => 200,
            'success'           => true,
            'title'             => $project->title,
            'project_id'        => $project->id ?? ''
        ];
        return $data;
    }

    # processContents
    public function processContents(){
        $request            = request();
        $integrationService = new IntegrationService();

        $request->merge([
            'stream'        => true,
            'content_type'  => 'template_content'
        ]);
        
        return $integrationService->contentGenerator(templatesEngine(), $request);
    }

    # updateBalanceStopGeneration
    public function updateBalanceStopGeneration(Request $request)
    {
        $project_id = session()->get('project_id');
        $user = auth()->user();
        if ($project_id && $user->user_type == "customer") {
            $project = Project::where('id', $project_id)->where('user_id', $user->id)->first();
            if ($project) {
                $words = $project->words;
                $this->updateUserWords($words, $user);
                session()->forget('project_id');

                $latestPackage      = activePackageHistory();
                $previousBalance    = $latestPackage ? $latestPackage->this_month_available_words : null;
                $after_balance      = $latestPackage ? $latestPackage->this_month_available_words - $words : null;

                # keep log
                $logData                      =  [
                    'user_id'                 => $project->user_id,
                    'project_id'              => $project->id,
                    'subscription_history_id' => $latestPackage ? $latestPackage->id : null,
                    'subscription_package_id' => $latestPackage ? $latestPackage->subscription_package_id : null,
                    'template_id'             => $project->template_id != null ? $project->template_id : null,
                    'custom_template_id'      => $project->custom_template_id != null ? $project->custom_template_id : null,
                    'model_name'              => $project->model_name,
                    'content'                 => $project->content,
                    'content_type'            => $project->content_type,
                    'words'                   => $words,
                    'prompt_words'            => $project->prompts,
                    'completion_words'        => $project->completion,
                    'previous_balance'        => $previousBalance,
                    'after_balance'           => $after_balance
                ];
                $this->createLog($logData);

                # update template usage
                if(!is_null($project->template_id)) {
                    $template = Template::whereId($project->template_id)->first();
                    $this->updateTemplateUsages($words, $template, $user);
                } else {
                    $template = CustomTemplate::whereId($project->custom_template_id)->first();
                    $this->updateTemplateUsages($words, $template, $user, true);
                }

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false]);
    }

    # updateUserWords - take token as word
    public function updateUserWords($tokens, $user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('words', $tokens, $user);
        }
    }

    # updateTemplateUsages - take token as word
    public function updateTemplateUsages($tokens, $template, $user, $customTemplate = false)
    {
        // user wise template usage
        $template->total_words_generated += (int) $tokens;
        $template->save();

        // user wise template usage
        $templateUsage                      = new TemplateUsage;
        $templateUsage->user_id             = $user->id;
        if ($customTemplate) {
            $templateUsage->custom_template_id         = $template->id;
        } else {
            $templateUsage->template_id         = $template->id;
        }
        $templateUsage->total_used_words    = (int) $tokens;
        $templateUsage->save();

    }

    # keep log
    public function createLog($data)
    {
        ProjectLog::create($data);
    }
    # downalod content
    public function downalodBlogContent(Request $request)
    {

        try {
            $basePath = public_path('/');
            $type = $request->type;

            $project = Project::where('id', $request->id)->where('user_id', auth()->user()->id)->first();
            if(!$project) {
                flash(localize('Content not found for you'));
                return redirect()->back();
            }
            $data = ['slug' => $project->slug, 'content'=>$project->content, 'type'=>$type];

            if($type == 'html') {
                $name =  $project->slug .'.html';
                $file_path = $basePath.$name;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $view = view('backend.pages.projects.download_project', $data)->render();
                file_put_contents($file_path, $view);
                return response()->download($file_path);


            }
            if($type == 'word') {
                $name =  $project->slug .'.doc';
                $file_path = $basePath.$name;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }

                $view = view('backend.pages.projects.download_project', $data)->render();
                file_put_contents($file_path, $view);
                return response()->download($file_path);
            }
            if($type == 'pdf') {
                return  view('backend.pages.projects.download_project', $data);
            }

        } catch (\Throwable $th) {
            throw $th;
        }

    }
    public static function wordBalanceCheck($type = null)
    {
        $user = auth()->user();
        if ($type == 'allow_custom_templates') {
            # 2. verify if user has access to the template [template available in subscription package]
            if ($user->user_type == "customer") {
                // check package balance

                $checkBalanceData = activePackageBalance('allow_custom_templates');
                if (!empty($checkBalanceData)) {
                    return $checkBalanceData;
                }
                // check word limit
                if (availableDataCheck('words') <= 10) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Your word balance is low, please upgrade you plan'),
                    ];
                    return $data;
                }
            }
        } else {
            if ($user->user_type == "customer") {
                // check package balance
                $checkBalanceData = activePackageBalance();
                if (!empty($checkBalanceData)) {
                    return $checkBalanceData;
                }
                // check word limit
                if (availableDataCheck('words') <= 10) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Your word balance is low, please upgrade you plan'),
                    ];
                    return $data;
                }
            }
        }
        return [];
    }
}
