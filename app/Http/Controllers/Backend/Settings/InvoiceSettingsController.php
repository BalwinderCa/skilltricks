<?php

namespace App\Http\Controllers\Backend\Settings;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Http\Controllers\Controller;

class InvoiceSettingsController extends Controller
{
    //
    public function index()
    {
        return view('backend.pages.systemSettings.invoice-settings');
    }
    public function update(Request $request)
    {
        try {
            $types = $request->types;
            foreach ($types as $type) {
                $setting = SystemSetting::where('entity', $type)->first();
                $value = $request[$type]; 
                if ($setting != null) {
                    $setting->value = $value;
                    $setting->save();
                } else {
                    $setting = new SystemSetting;
                    $setting->entity = $type;
                    $setting->value = $value;
                    $setting->save();
                }
            }
            cacheClear();
            flash(localize('Settings Updated Successfully'))->success();
            return redirect()->route('admin.invoice-settings.index');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
}
