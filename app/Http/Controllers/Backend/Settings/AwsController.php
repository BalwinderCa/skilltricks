<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Ods\Settings;

class AwsController extends Controller
{
    public function  __construct()
    {
        if (!getSetting('amazon_ses')) {
            flash(localize('Feature is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }
    public function index()
    {
        return view('backend.pages.systemSettings.aws-settings');
    }
    public function update(Request $request)
    {
        if ($request->types) {
            foreach ($request->types as $key => $value) {
                SystemSetting::updateOrCreate([
                    'entity' => $key
                ], [
                    'value' => $value
                ]);
                writeToEnvFile($key, $value);
            }
        }
        cacheClear();
        flash(localize('AWS Setup Successfully'))->success();
        return redirect()->route('admin.awsSettings.index');
    }
}
