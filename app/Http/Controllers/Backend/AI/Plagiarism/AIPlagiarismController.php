<?php

namespace App\Http\Controllers\Backend\AI\Plagiarism;

use Illuminate\Http\Request;
use App\Models\AiContentDetector;
use App\Models\AiPlagiarismCheck;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Http\Services\PlagiarismService;

class AIPlagiarismController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_plagiarism') == '0') {
            flash(localize('AI Plagiarism is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    public function index()
    {
        $user = user();
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_plagiarism == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_plagiarism')) {
                abort(403);
            }
        }
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }
        $content_detectors = AiContentDetector::where('type', 2)->where('created_by', auth()->user()->id)->paginate(paginationNumber());
        return view('backend.pages.plagiarism.index-plagiarism', compact('content_detectors', 'msg'));
    }
    public function create()
    {
        $user = user();
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_plagiarism == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_plagiarism')) {
                abort(403);
            }
        }
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }
        return view('backend.pages.plagiarism.create-plagiarism', compact('msg'));
    }
    public function store(Request $request)
    {
        try {
            $service = SetupController::plagiarismInstance();
            $result  = $service->plagiarismCheck($this->formattedParams($request));
            $result  = json_decode($result);
            # if have api error 
            if(property_exists($result, 'error') && property_exists($result, 'description')) {
                flash($result->description)->warning();
                return redirect()->back();
            }
            $total   = 100;
            if ($result) {
                $this->updateUserWords(strlen($request->text), auth()->user());
                $plagiarism             = new AiContentDetector;
                $plagiarism->ai         = $total - (int)number_format($result->score);
                $plagiarism->human      = (int)number_format($result->score);
                $plagiarism->content    = $request->text;
                $plagiarism->title      = $request->title;
                $plagiarism->length      = strlen($request->text);;
                $plagiarism->response   = $result;
                $plagiarism->type       = 2;
                $plagiarism->created_by = auth()->user()->id;
                $plagiarism->save();
            }
            if ($plagiarism->id) {
                return redirect()->route('admin.content.plagiarism.matching-content', $plagiarism->id);
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
            'text' => $request->text
        ];

        return $params;
    }
    public function edit(int $id)
    {
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }
        $content_detector = AiContentDetector::where('type', 2)->where('id', $id)->where('created_by', auth()->user()->id)->first();

        return view('backend.pages.plagiarism.create-plagiarism', compact('content_detector', 'msg'));
    }
    public function show(int $id)
    {
        $activeKey = SetupController::activeKey();
        $msg = null;
        if (!$activeKey) {
            $msg = localize('not found plagiarism active key.');
        }
        $content_detector = AiContentDetector::where('type', 2)->where('id', $id)->where('created_by', auth()->user()->id)->first();
        return view('backend.pages.plagiarism.create-plagiarism', compact('content_detector', 'msg'));
    }

    public function destroy(int $id)
    {
        $content_detector = AiContentDetector::where('type', 2)->where('created_by', auth()->user()->id)->where('id', $id)->first();
        $content_detector->delete();
        flash(localize('Operation successfully'))->success();
        return back();
    }
    public function matchingContent(int $id)
    {
        $content_detector = AiContentDetector::where('type', 2)->where('created_by', auth()->user()->id)->where('id', $id)->first();

        return view('backend.pages.plagiarism.matching-plagiarism', compact('content_detector'));
    }
    # updateUserWords - take token as word
    public function updateUserWords($tokens, $user)
    {
        if ($user->user_type == "customer") {
            updateDataBalance('words', $tokens, $user);
        }
    }
}
