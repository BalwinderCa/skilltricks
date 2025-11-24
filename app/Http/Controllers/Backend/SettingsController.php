<?php

namespace App\Http\Controllers\Backend;

use App;
use App\Models\Currency;
use App\Mail\EmailManager;
use App\Models\EMailSetting;
use App\Models\MediaManager;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Orhanerday\OpenAi\OpenAi;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use App\Models\GeneralSetupLocalization;

class SettingsController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:open_ai'])->only('openAi');
        $this->middleware(['permission:general_settings'])->only(['index', "update", "envKeyUpdate"]);
        $this->middleware(['permission:smtp_settings'])->only('smtpSettings');
        $this->middleware(['permission:payment_settings'])->only(['paymentMethods', "updateGatewayDetails", 'updatePaymentMethods']);
        $this->middleware(['permission:social_login_settings'])->only(["socialLogin"]);
        $this->middleware(['permission:cron_job'])->only(['cronJobList']);
        $this->middleware(['permission:auth_settings'])->only(['authSettings']);
        $this->middleware(['permission:otp_settings'])->only(['otpSettings']);

        $this->middleware(["permission:open_ai"])->only(["openAi"]);
    }

    # admin general settings
    public function index(Request $request)
    {
        $data['lang_key'] =  $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return view('backend.pages.systemSettings.general', $data);
    }

    # open ai settings
    public function openAi()
    {
        return view('backend.pages.systemSettings.openAiSettings');
    }


    # smtp settings
    public function smtpSettings()
    {
        $emailSettings = EMailSetting::where('is_active', 1)->first();
        $mail_engines =   [
            'smtp'     => localize('SMTP'),
            'sendmail' => localize('Send Mail'),           
        ];
        $msg = null;
        if(getSetting('amazon_ses')){
            $mail_engines['ses'] = localize('Amazon SES');
            $msg = !getSetting('AWS_ACCESS_KEY_ID') || !getSetting('AWS_SECRET_ACCESS_KEY') ? localize('Please setup aws credentials before set Amazon SES') :'';
        }

        return view('backend.pages.systemSettings.smtp', compact('emailSettings', 'mail_engines', 'msg'));
    }

    # update env values
    public function envKeyUpdate(Request $request)
    {
      
        foreach ($request->types as $key => $type) {
            writeToEnvFile($type, $request[$type]);
        }
        cacheClear();
        flash(localize("Settings updated successfully"))->success();
        return back();
    }

    # test email
    public function testEmail(Request $request)
    {
        $array['view'] = 'emails.bulkEmail';
        $array['subject'] = "SMTP Test";
        $array['from'] = env('MAIL_FROM_ADDRESS');
        $array['content'] = "This is a test email.";
        try {
            Mail::to($request->email)->queue(new EmailManager($array));
        } catch (\Exception $e) {
        }

        flash(localize('An email has been sent.'))->success();
        return back();
    }

    # update settings
    public function update(Request $request)
    {

        /**
         * Custom Header, Footer Script & Custom CSS Updating Start
         *
         * */
        if ($request->ajax()) {
            $appStatic = appStatic();
            try {
                DB::beginTransaction();

                (new App\Services\Setting\SettingService())->updateEntityValues($request->types, $request->typeValues);

                DB::commit();

                cacheClear();

                return apiResponse(
                    $appStatic::TRUE,
                    $appStatic::SUCCESS_WITH_DATA,
                    "Script is updated successfully."
                );
            } catch (\Throwable $e) {
                commonLog("Failed to update custom scripts", errorArray($e));

                DB::rollBack();
                flash($e->getMessage())->error();

                return apiResponse(
                    $appStatic::TRUE,
                    $appStatic::SUCCESS_WITH_DATA,
                    $e->getMessage()
                );
            }
        }



        // Only User Type Admin is allowed to update the settings

        if (isDemoMode()) {
            flash(localize("This is turned off in demo"))->warning();
            return back();
        }
        $typesForLocalizations = ['hero_title', 'how_it_works_1_title', 'cta_heading_title', 'homepage_trusted_by_title', 'feature_image_1_title', 'cta_colored_title', 'aboutUsContents'];
        foreach ($request->types as $key => $type) {
            // for currency rate
            if ($type == 'DEFAULT_CURRENCY') {
                $currency = Currency::where('code', $request[$type])->first();
                writeToEnvFile('DEFAULT_CURRENCY', $currency->code);
                writeToEnvFile('DEFAULT_CURRENCY_RATE', $currency->rate);
                writeToEnvFile('DEFAULT_CURRENCY_SYMBOL', $currency->symbol);
                writeToEnvFile('DEFAULT_CURRENCY_SYMBOL_ALIGNMENT', $currency->alignment);
            }

            # web maintenance mode
            if ($type == 'enable_maintenance_mode') {
                # maintenance
                if ($request[$type] == "1") {
                    Artisan::call('down');
                } else {
                    Artisan::call('up');
                }
            }

            # timezone
            if ($type == 'timezone') {
                writeToEnvFile('APP_TIMEZONE', $request[$type]);
            } else if ($type == "GOOGLE_CLIENT_ID" || $type == "GOOGLE_CLIENT_SECRET" || $type == "FACEBOOK_APP_ID" || $type == "FACEBOOK_APP_SECRET" || $type == "RECAPTCHAV3_SECRET" || $type == "RECAPTCHAV3_SITEKEY") {
                writeToEnvFile($type, $request[$type]);
            } else if ($type == "OPENAI_SECRET_KEY" || $type == "SD_API_KEY") {
                if ($request[$type] != null) {
                    writeToEnvFile($type, $request[$type]);
                }
            } else {
                $value = $request[$type];

                $reqLang = $request->language_key ?? null;
                if (checkLanguage($reqLang) || is_null($reqLang)) {

                    if ($type == 'system_title') {
                        writeToEnvFile('APP_NAME', $value);
                    }

                    $settings = SystemSetting::where('entity', $type)->first();

                    if ($settings != null) {
                        if (gettype($value) == 'array') {
                            $settings->value = json_encode($value);
                        } else {
                            $settings->value = $value;
                        }
                    } else {
                        $settings = new SystemSetting;
                        $settings->entity = $type;
                        if (gettype($value) == 'array') {
                            $settings->value = json_encode($value);
                        } else {
                            $settings->value = $value;
                        }
                    }
                    if (in_array($type, $typesForLocalizations)) {
                        $this->storeLocalizationData($request);
                    }

                    $settings->save();
                } else {
                    if ($request->filled('language_key')) {
                        $this->storeLocalizationData($request);
                    }
                }
            }
        }

        cacheClear();
        flash(localize("Settings updated successfully"))->success();
        return back();
    }

    # social login
    public function socialLogin()
    {
        return view('backend.pages.systemSettings.socialLogin');
    }

    # activation
    public function updateActivation(Request $request)
    {
        $setting = SystemSetting::where('entity', $request->entity)->first();
        if ($setting != null) {
            $setting->value = $request->value;
            $setting->save();
        } else {
            $setting = new SystemSetting;
            $setting->entity = $request->entity;
            $setting->value = $request->value;
            $setting->save();
        }
        cacheClear();
        return 1;
    }

    # admin payment Methods settings
    public function paymentMethods()
    {
        $paymentmethods = PaymentGateway::get();

        return view('backend.pages.systemSettings.paymentMethods', compact('paymentmethods'));
    }

    # update payment methods
    public function updatePaymentMethods(Request $request)
    {

        foreach ($request->types as $type) {
            writeToEnvFile($type, $request[$type]);
        }

        foreach ($request->payment_methods as $payment_method) {
            if ($request->has(['enable_' . $payment_method])) {
                $activationSetting = SystemSetting::where('entity', 'enable_' . $payment_method)->first();
                $value = $request['enable_' . $payment_method];

                if ($activationSetting != null) {
                    $activationSetting->value = $value;
                    $activationSetting->save();
                } else {
                    $activationSetting = new SystemSetting;
                    $activationSetting->entity = 'enable_' . $payment_method;
                    $activationSetting->value = $value;
                    $activationSetting->save();
                }
            }

            if ($request->has($payment_method . '_sandbox')) {
                $setting = SystemSetting::where('entity', $payment_method . '_sandbox')->first();
                $value = $request[$payment_method . '_sandbox'];

                if ($setting != null) {
                    $setting->value = $value;
                    $setting->save();
                } else {
                    $setting = new SystemSetting;
                    $setting->entity = $payment_method . '_sandbox';
                    $setting->value = $value;
                    $setting->save();
                }
            }

            if ($request->has('offline_image')) {
                $setting = SystemSetting::where('entity', 'offline_image')->first();
                $value = $request['offline_image'];
                if ($setting != null) {
                    $setting->value = $value;
                    $setting->save();
                } else {
                    $setting = new SystemSetting;
                    $setting->entity = 'offline_image';
                    $setting->value = $value;
                    $setting->save();
                }
            }
        }

        cacheClear();
        flash(localize("Payment settings updated successfully"))->success();
        return back();
    }
    # open ai setting update
    public function updateOpenAiSettings(Request $request)
    {
        try {
            $request_ai_models = ["default_open_ai_model", "ai_blog_wizard_model", "ai_chat_model"];
            foreach ($request->types as $key => $type) {
                # check model supported
                if (in_array($type, $request_ai_models) && openAiKey() && env('OPENAI_SECRET_KEY') != "") {
                    if (!in_array($request[$type], $this->openAiModels($request["OPENAI_SECRET_KEY"])) && !empty($this->openAiModels($request["OPENAI_SECRET_KEY"]))) {
                        flash(localize("Your Selected Model Not Supported in Your Open Ai Key"))->warning();
                    }
                }
                if ($type == "OPENAI_SECRET_KEY" || $type == "SD_API_KEY" || $type == "ANTHROPIC_API_KEY" || $type == "DEEPSEEK_API_KEY") {
                    if ($request[$type] != null) {
                        writeToEnvFile($type, $request[$type]);
                    }
                } else {
                 
                    $value = $request[$type];                    
                  
                    $settings = SystemSetting::where('entity', $type)->first();

                    if ($settings != null) {
                        $settings->value = $value;
                    } else {
                        $settings = new SystemSetting;
                        $settings->entity = $type;
                        $settings->value = $value;
                    }
                    $settings->save();
                }
            }
            foreach($request->features as $key=>$type){
                $value = $request[$type] == 'on' ? 1 : 0;              
                $settings = SystemSetting::where('entity', $type)->first();
                if ($settings != null) {
                    $settings->value = $value;
                } else {
                    $settings = new SystemSetting;
                    $settings->entity = $type;
                    $settings->value = $value;
                }
                $settings->save();
            }
            cacheClear();
            flash(localize('Open Ai settings update successfully'))->success();
            return redirect()->back();
        } catch (\Throwable $e) {
            flash($e->getMessage())->error();
            Log::info('Open Ai setting update issues: ', errorArray($e));
            return redirect()->back();
        }
    }
    # auth  settings
    public function authSettings(Request $request)
    {
        $lang_key = $request->lang_key ?? env('DEFAULT_LANGUAGE');
        return view('backend.pages.systemSettings.authSettings', compact('lang_key'));
    }

    # otp  settings
    public function otpSettings()
    {
        return view('backend.pages.systemSettings.otpSettings');
    }

    private function storeLocalizationData($request)
    {
        $lang_key = $request->language_key ?? App::getLocale();
        foreach ($request->types as $type) {
            $settings = SystemSetting::where('entity', $type)->first();

            $system_setting_id = $settings ? $settings->id : null;
            $value = $request[$type];
            if (gettype($value) == 'array') {
                $value = json_encode($value);
            }

            if (!is_null($system_setting_id)) {
                GeneralSetupLocalization::updateOrCreate([
                    'lang_key' => $lang_key,
                    'entity' => $type
                ], [
                    'value' => $value,
                    'system_setting_id' => $system_setting_id
                ]);
            }
        }
    }

    // cron job list
    public function cronJobList()
    {
        return view('backend.pages.systemSettings.cron_list');
    }
    //  logo static update
    public function logoStaticUpdate($mediaManagerId)
    {
        if (!$mediaManagerId) return false;
        $mediaManager = MediaManager::where('id', $mediaManagerId)->first();
        if (!$mediaManager) return false;
        $from = public_path($mediaManager->media_file);

        $exit_file = base_path('public/logo.png');
        if (file_exists($exit_file)) {
            unlink($exit_file);
        }
        if (file_exists($from)) {
            File::copy($from, base_path('public/logo.png'));
        }
    }
    public function openAiModeList()
    {
        // TODO :: TESTING REQUIRED OPEN-AI
        try {
            $message = null;
            if (!openAiKey()) {
                flash(localize('Operation failed'))->error();
                return redirect()->back();
            };

            $open_ai = new OpenAi(openAiKey());
            $models = $open_ai->listModels();
            $models =  json_decode($models);
            if ($models) {

                if (property_exists($models, 'error')) {
                    $message = $models->error->message;
                    $models = null;
                } else {
                    $models = $models->data;
                }
            } else {

                $models = null;
            }

            return view('backend.pages.systemSettings.openAi.model_details', compact('models', 'message'));
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
    public function openAiModels($open_ai_key = null): array
    {
        $key    = $open_ai_key ?? openAiKey();
        $open_ai = new OpenAi($key);
        $models = $open_ai->listModels();
        $models =  json_decode($models);

        $data = [];
        if ($models != null) {
            if (property_exists($models, 'error')) {
                return $data;
            }
            $models = $models->data;
            foreach ($models as $model) {
                $data[] = $model->id;
            }
        }
        session()->put('supported_models', $data);
        return $data;
    }
    public function checkMaxToken(Request $request)
    {
        try {
            $max_token = (int)$request->max_token;
            $data = [
                'status' => 'error',
                'message' => 'Max token not found',
            ];
            if (!$max_token) {
                return response()->json($data);
            }
            $model =  openAiModel('chat');
            $messages = [
                [
                    "role" => "system",
                    "content" => "You are a helpful assistant."
                ],
                [
                    "role" => "user",
                    "content" => "Hello!"
                ]
            ];
            # 1. init openAi
            $open_ai = new OpenAi(openAiKey());
            $user    = auth()->user();
            $opts    = [
                'model'             => $model,
                "max_tokens"        => $max_token, //TODO :-> get from ai setting make_tokens
                'messages'          => $messages,
                'temperature'       => 1.0,
                'presence_penalty'  => 0.6,
                'frequency_penalty' => 0,
                'stream'            => false
            ];
            $data = $open_ai->chat($opts);
            $output = json_decode($data);
            if ($output) {
                if (property_exists($output, 'error')) {
                    $data = [
                        'status' => 'error',
                        'message' => $output->error->message,
                    ];
                } else {
                    $data = [
                        'status' => 'success',
                        'message' => 'Max token supported openAi model. Click Save Configuration for Save.',
                    ];
                }
            }
            return response()->json($data);
        } catch (\Throwable $th) {
            Log::info('Max token check : ', errorArray($th));
            $data = [
                'status' => 'error',
                'message' => 'Connection failed to api.openai.com',
            ];
        }
    }
}
