<?php

namespace App\Http\Controllers\Backend\AI;

use App\Models\Project;
use App\Models\Language;
use App\Models\ProjectLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Services\Integration\IntegrationService;

class AiWriterController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_rewriter') == '0') {
            flash(localize('AI Writer is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    public function index()
    {
        $user = auth()->user();
        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_rewriter == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_rewriter')) {
                abort(403);
            }
        }
        $languages = Language::isActiveForTemplate()->latest()->get();
        return view('backend.pages.aiWriter.index-ai-writer', [
            'languages' => $languages
        ]);
    }
    # generate contents
    public function generate(Request $request)
    {
        $user = auth()->user();

        # 2. verify if user has access to the template [template available in subscription package]
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

        $prompt = strip_tags($request->about) . ' in ' . $request->lang . ' language ' . strip_tags($request->about);
        if ($request->max_tokens != -1) {
            $prompt .= ' .The tone of voice should be ' . $request->tone . ' and the output must be completed in ' . $request->max_tokens . ' words. Do not generate translation.';
        } else {
            $prompt .= ' .The tone of voice should be ' . $request->tone . '. Do not generate translation.';
        }
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
        $integrationService->setParams(aiRewriterEngine(), $request, $model);
        # SET AI PARAMS ENDS

        if ($request->project_id != null) {
            $project = Project::whereId($request->project_id)->first();
            $request->session()->put('project_id', $project->id);
        } else {
            $projectTitle = "Untitled Project - " . date("Y-m-d");
            $project = new Project;
            $project->user_id       = $user->id;
            $project->model_name    = $model;
            $project->title         = $projectTitle;
            $project->slug          = preg_replace('/\s+/', '-', trim($projectTitle)) . '-' . strtolower(Str::random(5));
            $project->content_type  = 'Ai ReWriter';
            $project->save();
            $request->session()->put('project_id', $project->id);
        }

        $data = [
            'status'            => 200,
            'success'           => true,
            'title'             => $project->title,
            'project_id'        => $project->id ?? ''
        ];
        return $data;
    }

    # processContents
    public function processContents()
    {
        $request            = request();
        $integrationService = new IntegrationService();

        $request->merge([
            'stream'        => true,
            'content_type'  => 'ai_rewriter'
        ]);
        
        return $integrationService->contentGenerator(aiRewriterEngine(), $request);
    }

    # updateUserWords - take token as word
    public function updateUserWords($tokens, $user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('words', $tokens, $user);
        }
    }
    # keep log
    public function createLog($data)
    {
        ProjectLog::create($data);
    }
}
