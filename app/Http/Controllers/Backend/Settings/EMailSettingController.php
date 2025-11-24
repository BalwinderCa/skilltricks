<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Models\EMailSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EMailSettingController extends Controller
{
    public function index()
    {
        return '';
    }
    public function update(Request $request)
    {
        if ($request->types) {
            foreach ($request->types as $key) {
                writeToEnvFile($key, $request[$key]);
            }
        }
        $settings =  EMailSetting::first();
        if ($settings) {
            $settings->email_engine    = $request->MAIL_MAILER;
            $settings->mail_driver     = $request->MAIL_MAILER;
            $settings->mail_host       = $request->MAIL_HOST;
            $settings->mail_port       = $request->MAIL_PORT;
            $settings->mail_username   = $request->MAIL_USERNAME;
            $settings->mail_password   = $request->MAIL_PASSWORD;
            $settings->mail_encryption = $request->MAIL_ENCRYPTION;
            $settings->from_email      = $request->MAIL_FROM_ADDRESS;
            $settings->from_name       = $request->MAIL_FROM_NAME;
            $settings->is_active       = 1;
            $settings->save();
        }

        cacheClear();
        flash(localize('Operation Successfully'))->success();
        return redirect()->route('admin.smtpSettings.index');
    }
}
