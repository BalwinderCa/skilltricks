<?php

namespace App\Http\Controllers\Backend\Appearance;

use App\Http\Controllers\Controller;
use App\Models\MediaManager;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use App\Models\GeneralSetupLocalization;
use App;

class ClientFeedbackController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:homepage'])->only(['index', 'edit', 'delete']);
    }

    # get the feedback
    private function getFeedback($lang_key = null)
    {
        $feedback = [];

        if (getSetting('client_feedback') != null) {
            $feedback = json_decode(systemSettingsLocalization('client_feedback', $lang_key));
        }
        return $feedback;
    }

    # feedback configuration
    public function index()
    {
        $feedback = $this->getFeedback();
        return view('backend.pages.appearance.homepage.clientFeedback', compact('feedback'));
    }

    # feedback configuration
    public function store(Request $request)
    {
        $clientFeedback = SystemSetting::where('entity', 'client_feedback')->first();
        if (!is_null($clientFeedback)) {
            if (!is_null($clientFeedback->value) && $clientFeedback->value != '') {
                $feedback                = json_decode($clientFeedback->value);
                $newFeedback              = new MediaManager; //temp obj
                $newFeedback->id          = rand(100000, 999999);
                $newFeedback->name        = $request->name ? $request->name : '';
                $newFeedback->heading        = $request->heading ? $request->heading : '';
                $newFeedback->designation        = $request->designation ? $request->designation : '';
                $newFeedback->rating      = $request->rating ? $request->rating : '';
                $newFeedback->review      = $request->review ? $request->review : '';
                $newFeedback->image       = $request->image ? $request->image : '';

                array_push($feedback, $newFeedback);
                $clientFeedback->value = json_encode($feedback);
                $clientFeedback->save();
            } else {
                $value                  = [];
                $newFeedback              = new MediaManager; //temp obj
                $newFeedback->id          = rand(100000, 999999);
                $newFeedback->name        = $request->name ? $request->name : '';
                $newFeedback->heading        = $request->heading ? $request->heading : '';
                $newFeedback->designation        = $request->designation ? $request->designation : '';
                $newFeedback->rating      = $request->rating ? $request->rating : '';
                $newFeedback->review      = $request->review ? $request->review : '';
                $newFeedback->image       = $request->image ? $request->image : '';

                array_push($value, $newFeedback);
                $clientFeedback->value = json_encode($value);
                $clientFeedback->save();
            }
        } else {
            $clientFeedback = new SystemSetting;
            $clientFeedback->entity = 'client_feedback';

            $value              = [];
            $newFeedback          = new MediaManager; //temp obj
            $newFeedback->id      = rand(100000, 999999);
            $newFeedback->name        = $request->name ? $request->name : '';
            $newFeedback->heading        = $request->heading ? $request->heading : '';
            $newFeedback->designation        = $request->designation ? $request->designation : '';
            $newFeedback->rating      = $request->rating ? $request->rating : '';
            $newFeedback->review      = $request->review ? $request->review : '';
            $newFeedback->image       = $request->image ? $request->image : '';

            array_push($value, $newFeedback);
            $clientFeedback->value = json_encode($value);
            $clientFeedback->save();
        }
        cacheClear();
        flash(localize('Feedback added successfully'))->success();
        return back();
    }

    # edit feedback
    public function edit(Request $request, $id)
    {
        $data['id'] = $id;
        $data['feedback'] = $this->getFeedback($request->lang_key);
        $data += $this->languageData($request);
        return view('backend.pages.appearance.homepage.clientFeedbackEdit', $data);
    }

    # update feedback
    public function update(Request $request)
    {
        $clientFeedback = SystemSetting::where('entity', 'client_feedback')->first();

        $feedback = $this->getFeedback();
        $tempFeedback = [];
        $lang_type = $request->language_key ?? App::getLocale();
        foreach ($feedback as $singleFeedback) {
            if ($singleFeedback->id == $request->id) {
                $singleFeedback->name        = $request->name ? $request->name : '';
                $singleFeedback->heading        = $request->heading ? $request->heading : '';
                $singleFeedback->designation        = $request->designation ? $request->designation : '';
                $singleFeedback->rating      = $request->rating ? $request->rating : '';
                $singleFeedback->review      = $request->review ? $request->review : '';
                $singleFeedback->image       = $request->image ? $request->image : '';
                array_push($tempFeedback, $singleFeedback);
            } else {
                array_push($tempFeedback, $singleFeedback);
            }
        }
        if (checkLanguage($lang_type)) {
            $clientFeedback->value = json_encode($tempFeedback);
            $clientFeedback->save();
            $this->storeLocalizationData(json_encode($tempFeedback), $lang_type, $clientFeedback->id);
        } else {
            $this->storeLocalizationData(json_encode($tempFeedback), $lang_type, $clientFeedback->id);
        }

        cacheClear();
        flash(localize('Feedback updated successfully'))->success();
        return redirect()->route('admin.appearance.homepage.clientFeedback');
    }

    # delete feedback
    public function delete($id)
    {
        $clientFeedback = SystemSetting::where('entity', 'client_feedback')->first();

        $feedback = $this->getFeedback();
        $tempFeedback = [];

        foreach ($feedback as $singleFeedback) {
            if ($singleFeedback->id != $id) {
                array_push($tempFeedback, $singleFeedback);
            }
        }

        $clientFeedback->value = json_encode($tempFeedback);
        $clientFeedback->save();

        cacheClear();
        flash(localize('Feedback deleted successfully'))->success();
        return redirect()->route('admin.appearance.homepage.clientFeedback');
    }
    private function languageData($request): array
    {
        $data = [];
        $data['lang_key'] =  $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return $data;
    }
    private function storeLocalizationData($value, $lang_key, $system_setting_id)
    {
        GeneralSetupLocalization::updateOrCreate([
            'lang_key' => $lang_key,
            'entity' => 'client_feedback'
        ], [
            'value' => $value,
            'system_setting_id' => $system_setting_id
        ]);
    }
}
