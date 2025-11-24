<?php

namespace App\Http\Controllers\Backend\AI\Plagiarism;

use Illuminate\Http\Request;
use App\Models\AiContentDetector;
use App\Models\PlagiarismSettings;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Services\PlagiarismService;
use App\Http\Requests\ScanTextFormRequest;

class AIDetectorController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_detector') == '0') {
            flash(localize('AI Detector is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    public function index()
    {
        $user = user();
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_detector == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_detector')) {
                abort(403);
            }
        }
        $content_detectors = AiContentDetector::where('type', 1)->where('created_by', auth()->user()->id)->paginate(paginationNumber());
        return view('backend.pages.plagiarism.index-ai-detector', compact('content_detectors'));
    }
    public function create()
    {
        $user = user();
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_detector == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_detector')) {
                abort(403);
            }
        }
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }

        return view('backend.pages.plagiarism.create-ai-detector', compact('msg'));
    }
    public function store(ScanTextFormRequest $request)
    {
        try {
            $service = SetupController::plagiarismInstance();
            $result  = $service->textDetector($this->formattedParams($request));
            $result  = json_decode($result);
            
            # if have api error 
            if(property_exists($result, 'error') && property_exists($result, 'description')) {
                flash($result->description)->warning();
                return redirect()->back();
            }

            $total   = 100;
            if ($result) {
                $this->updateUserWords(strlen($request->text), auth()->user());
                $detector           = new AiContentDetector;
                $detector->ai         = $total - number_format($result->score);
                $detector->human      = number_format($result->score);
                $detector->content    = $request->text;
                $detector->title      = $request->title;
                $detector->length     = strlen($request->text);
                $detector->response   = $result;
                $detector->created_by = auth()->user()->id;
                $detector->type       = 1;
                $detector->save();
            }
            if ($detector->id) {
                return redirect()->route('admin.content.detector.show', $detector->id);
            }
            flash(localize('Operation Successfully'))->success();
            return redirect()->back();
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }

    public function formattedParams($request): array
    {
        $params = [
            'text' => $request->text,
            'language' => 'en',
            'sentences' => true,
            'version' => "3.0",
        ];

        return $params;
    }
    public function edit($id)
    {
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }
        $content_detector = AiContentDetector::where('type', 1)->where('created_by', auth()->user()->id)->where('id', $id)->first();

        return view('backend.pages.plagiarism.create-ai-detector', compact('content_detector', 'msg'));
    }
    public function show($id)
    {
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }
        $content_detector = AiContentDetector::where('type', 1)->where('created_by', auth()->user()->id)->where('id', $id)->first();
        return view('backend.pages.plagiarism.create-ai-detector', compact('content_detector', 'msg'));
    }
    public function destroy(int $id)
    {
        $content_detector =AiContentDetector::where('type', 1)->where('created_by', auth()->user()->id)->where('id', $id)->first();
        $content_detector->delete();
        flash(localize('Operation successfully'))->success();
        return back();
    }
    # updateUserWords - take token as word
    public function updateUserWords($tokens, $user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('words', $tokens, $user);
        }
    }
}
