<?php

namespace App\Http\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class SmsServices
{
    # send sms
    public function sendSMS($to, $text, $from = null)
    {
        if (getSetting('active_sms_gateway') == 'twilio') {

            $TWILIO_SID = config('custom.twilio_sid');
            $TWILIO_AUTH_TOKEN = config('custom.twilio_auth_token');

            try {
                Http::withHeaders([
                    'Authorization' => 'Basic ' . \base64_encode("$TWILIO_SID:$TWILIO_AUTH_TOKEN")
                ])->asForm()->post("https://api.twilio.com/2010-04-01/Accounts/$TWILIO_SID/Messages.json", [
                    "Body" => $text,
                    "From" => config('custom.valid_twilio_number'),
                    "To" => $to,
                ]);
            } catch (Exception $e) {
                
            }
        }
    }

    # phone verification
    public function phoneVerificationSms($to, $code)
    {
        $sms = 'Your verification code for ' . config('custom.app_name') . ' is ' . $code . '.';
        $this->sendSMS($to, $sms);
    }

    # forgot password
    public function forgotPasswordSms($to, $code)
    {
        $sms = 'Your password reset code for ' . config('custom.app_name') . ' is ' . $code . '.';
        $this->sendSMS($to, $sms);
    }
}
