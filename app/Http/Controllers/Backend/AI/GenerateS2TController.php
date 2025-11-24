<?php

namespace App\Http\Controllers\Backend\AI;

use Str;
use Carbon\Carbon;
use App\Models\Project;
use App\Models\Language;
use Illuminate\Http\Request;
use Orhanerday\OpenAi\OpenAi;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class GenerateS2TController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_speech_to_text') == '0') {
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # code
    public function index()
    {
        $user = auth()->user();

        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_speech_to_text == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('speech_to_text')) {
                abort(403);
            }
        }
        $languages = Language::isActiveForTemplate()->latest()->get(); 
        return view('backend.pages.templates.generate-s2t', compact('languages'));
    }

    # generate code
    public function generate(Request $request)
    {

        if (env('DEMO_MODE') == "On") {
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => localize('Text to speech is turned off in demo'),
            ];
            return $data;
        }

        $user = auth()->user();
        $sizeLimit = 0; // unlimited

        # 1. init openAi
        $open_ai = new OpenAi(openAiKey());

        if ($user->user_type == "customer") {
            // check package balance
            $checkBalanceData = activePackageBalance('allow_speech_to_text');
            if (!empty($checkBalanceData)) {
                return $checkBalanceData;
            }

            // check s2t limit  
            if (availableDataCheck('s2t') < 1) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Your balance is low, please upgrade you plan'),
                ];
                return $data;
            }
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;

            if ($package->speech_to_text_filesize_limit > 0) {
                $sizeLimit = $package->speech_to_text_filesize_limit;
            }
        } else {
            $sizeLimit = 1000;
        }

        $fileLimit = (int)($sizeLimit) * 1024;
        if ($request->file('audio') != null) {

            $rules = [
                'audio' => 'required|mimes:mp3,mp4,mpeg,mpga,m4a,wav,webm|max:' . $fileLimit . ''
            ];

            $messages = ['audio.max' => localize('Max file size is: ') . $sizeLimit . 'MB'];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                $message = '';
                foreach ($validator->errors()->all() as  $msg) {
                    $message .= $msg . ' ';
                }
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => $message,
                ];
                return $data;
            }
        }

        # 4. generate code
        $model = 'whisper-1';

        $audio = $request->file('audio');
        $audioUrl = $audio->store('s2t');

        $audioPath = public_path() . DIRECTORY_SEPARATOR . $audioUrl;

        $file = curl_file_create($audioPath);

        $result = $open_ai->transcribe([
            'model' => $model,
            'file' => $file,
        ]);

        fileDelete($audioUrl);

        # parse response
        $result = json_decode($result, true);
        $outputContents = '';
        if (isset($result['text'])) {
            $outputContents = $result['text'];
            // $outputContents = nl2br($outputContents);
            $outputContents = str_replace(["\r\n", "\r", "\n"], "<br/>", $outputContents);
            $promptsToken = 0;
            $completionToken = count(explode(' ', $result['text']));
            $tokens =  count(explode(' ', $result['text']));

            # 5. Save it as a project 
            $projectTitle = $request->title;
            if ($request->project_id == null) {
                $project = new Project;
                $project->user_id       = $user->id;
                $project->model_name    = $model;
                $project->title         = $projectTitle;
                $project->slug          = preg_replace('/\s+/', '-', trim($projectTitle)) . '-' . strtolower(Str::random(5));
                $project->prompts       = $promptsToken;
                $project->completion    = $completionToken;
                $project->words         = $tokens;
                $project->content_type  = 'speech_to_text';
                $project->content       = trim($outputContents);
                $project->save();
            } else {
                $project = Project::where('id', $request->project_id)->first();
                if (!is_null($project)) {
                    $project->words         = $tokens;
                    $project->content       = trim($outputContents);
                    $project->save();
                }
            }

            $latestPackage = activePackageHistory();
            $previousBalance = $latestPackage ?  $latestPackage->this_month_available_s2t : null;
            $after_balance = $latestPackage ?  $latestPackage->this_month_available_s2t - 1 : null;
            # keep log
            $logData = [
                'user_id' => $project->user_id,
                'project_id' => $project->id,
                'subscription_history_id' => optional(activePackageHistory())->id,
                'subscription_package_id' => optional(activePackageHistory())->subscription_package_id,
                'model_name' => $project->model_name,
                'content' => $project->content,
                'content_type' => $project->content_type,
                'words' => $project->words,
                'prompt_words' => $promptsToken,
                'completion_words' => $completionToken,
                'previous_balance' => $previousBalance,
                'after_balance' => $after_balance
            ];

            $generateController = new GenerateContentsController();
            $generateController->createLog($logData);

            # 6. update word limit for user or admin/staff
            $this->updateUserS2T($user);

            $data = [
                'status'            => 200,
                'success'           => true,
                'output'            => trim($outputContents),
                'title'             => $projectTitle,
                'project_id'        => $project->id ?? '',
                'usedPercentage'    => view('backend.pages.templates.inc.used-s2t-percentage')->render(),
            ];
            return $data;
        } else {
            if (isset($result['error']['message'])) {
                $message = $result['error']['message'];
            } else {
                $message = localize('There is an issue with the openai account');
            }
            $data = [
                'status'  => 400,
                'success' => false,
                'message' => $message
            ];
            return $data;
        }
        $data = [
            'status'  => 500,
            'success' => false,
        ];
        return $data;
    }

    # updateUserS2T - take token as word
    public function updateUserS2T($user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('s2t', 1, $user);
        }
    }
}
