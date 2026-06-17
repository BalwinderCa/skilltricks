<?php

use App\Models\User;
use App\Models\AdSense;
use App\Models\Currency;
use App\Models\OpenAiKey;
use App\Models\PWASettings;
use App\Models\Localization;
use App\Models\MediaManager;
use App\Models\AiResponseLog;
use App\Models\EmailTemplate;
use App\Models\SystemSetting;
use Orhanerday\OpenAi\OpenAi;
use App\Models\PaymentGateway;
use App\Models\StorageManager;
use App\Models\WritebotModule;
use App\Models\WrNotification;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Models\TextToSpeechSetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use App\Models\PaymentGatewayDetail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Models\GeneralSetupLocalization;
use App\Models\SubscriptionRecurringPayment;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;
use League\CommonMark\Normalizer\SlugNormalizer;

if (!function_exists('getTheme')) {
    # get system theme
    function getTheme()
    {
        if (session('theme') != null && session('theme') != '') {
            return session('theme');
        }
        return Config::get('app.theme');
    }
}

if (!function_exists('getView')) {
    # get view of theme
    function getView($path, $data = [])
    {
        return view('frontend.' . getTheme() . '.' . $path, $data);
    }
}

if (!function_exists('getViewRender')) {
    # get view of theme with render
    function getViewRender($path, $data = [])
    {
        return view('frontend.' . getTheme() . '.' . $path, $data)->render();
    }
}

if (!function_exists('ddError')) {
    # get view of theme with render
    function ddError($e)
    {
        return dd(errorArray($e));
    }
}

if (!function_exists('errorArray')) {
    # get view of theme with render
    function errorArray($e)
    {
        return [
            "message" => $e->getMessage(),
            "line"    => $e->getLine(),
            "file"    => $e->getFile(),
        ];
    }
}

if (!function_exists('cacheClear')) {
    # clear server cache
    function cacheClear()
    {
        try {
            Artisan::call('cache:forget spatie.permission.cache');
        } catch (\Throwable $th) {
            //throw $th;
        }

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');
    }
}

if (!function_exists('clearPaymentSession')) {
    # clear session cache
    function clearPaymentSession()
    {
        session()->forget('package_id');
        session()->forget('amount');
        session()->forget('payment_method');
        session()->forget('admin_customer');
        session()->forget('active_now');
    }
}

if (!function_exists('csrfToken')) {
    #  Get the CSRF token value.
    function csrfToken()
    {
        $session = app('session');

        if (isset($session)) {
            return $session->token();
        }
        throw new RuntimeException('Session store not set.');
    }
}

if (!function_exists('paginationNumber')) {
    # return number of data per page
    function paginationNumber($value = null)
    {
        return $value != null ? $value : config('custom.default_pagination');
    }
}

if (!function_exists('areActiveRoutes')) {
    # return active class
    function areActiveRoutes(array $routes, $output = "active")
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) return $output;
        }
        return '';
    }
}

if (!function_exists('validatePhone')) {
    # validatePhone
    function validatePhone($phone)
    {
        $phone = str_replace(' ', '', $phone);
        $phone = str_replace('-', '', $phone);
        return $phone;
    }
}

if (!function_exists('staticAsset')) {
    # return path for static assets
    function staticAsset($path, $secure = null)
    {
        if (strpos(url('/'), '.test') !== false || strpos(url('/'), 'http://127.0.0.1:') !== false) {
            return app('url')->asset('' . $path, $secure) . '?v=' . config('custom.app_version');
        }
        return app('url')->asset('public/' . $path, $secure) . '?v=' . config('custom.app_version');
    }
}
if (!function_exists('userAvatar')) {
    # return path for static assets
    function userAvatar()
    {

        if (isLoggedIn()) {
            $user = user();

            return  !empty($user->avatar && $user->profileImage->media_file) ? asset("/public/" . auth()->user()->profileImage->media_file) : staticAsset("/backend/assets/img/avatar/1.jpg");
        }
    }
}

if (!function_exists('uploadedAsset')) {
    #  Generate an asset path for the uploaded files.
    function uploadedAsset($fileId)
    {
        if (!$fileId) return null;
        $mediaFile = MediaManager::find($fileId);
        if (!is_null($mediaFile)) {
            if (strpos(url('/'), '.test') !== false || strpos(url('/'), 'http://127.0.0.1:') !== false) {
                return app('url')->asset('' . $mediaFile->media_file);
            }
            return app('url')->asset('public/' . $mediaFile->media_file);
        }
        return '';
    }
}

if (!function_exists('noImage')) {
    #  Generate an asset path for the uploaded files.
    function noImage()
    {
        return asset('frontend/default/assets/img/logo-white.png');
    }
}

if (!function_exists('localize')) {
    # add / return localization
    function localize($key, $lang = null, $localize = true)
    {
        if ($localize == false) {
            return $key;
        }

        if ($lang == null) {
            $lang = App::getLocale();
        }

        $t_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($key)));

        $localization_english = Cache::rememberForever('localizations-en', function () {
            return Localization::where('lang_key', 'en')->pluck('t_value', 't_key');
        });

        if (!isset($localization_english[$t_key])) {
            # add new localization
            newLocalization('en', $t_key, $key);
        }

        # return user session lang
        $localization_user = Cache::rememberForever("localizations-{$lang}", function () use ($lang) {
            return Localization::where('lang_key', $lang)->pluck('t_value', 't_key')->toArray();
        });

        if (isset($localization_user[$t_key])) {
            return trim($localization_user[$t_key]);
        }

        return isset($localization_english[$t_key]) ? trim($localization_english[$t_key]) : $key;
    }
}

if (!function_exists('newLocalization')) {
    # new localization
    function newLocalization($lang, $t_key, $key, $type = null)
    {
        $localization = new Localization;
        $localization->lang_key = $lang;
        $localization->t_key = $t_key;
        $localization->t_key = $t_key;
        $localization->t_value = str_replace(array("\r", "\n", "\r\n"), "", $key);
        $localization->save();

        # clear cache
        Cache::forget('localizations-' . $lang);

        return trim($key);
    }
}

if (!function_exists('writeToEnvFile')) {
    # write To Env File
    function writeToEnvFile($type, $val)
    {
        $path = base_path('.env');
        if (file_exists($path)) {

            $val = '"' . trim($val) . '"';
            if (is_numeric(strpos(file_get_contents($path), $type)) && strpos(file_get_contents($path), $type) >= 0) {
                file_put_contents($path, str_replace(
                    $type . '="' . env($type) . '"',
                    $type . '=' . $val,
                    file_get_contents($path)
                ));
            } else {
                file_put_contents($path, file_get_contents($path) . "\r\n" . $type . '=' . $val);
            }
        }
    }
}

if (!function_exists('getFileType')) {
    #  Get file Type
    function getFileType($type)
    {
        $fileTypeArray = [
            // audio
            "mp3"       =>  "audio",
            "wma"       =>  "audio",
            "aac"       =>  "audio",
            "wav"       =>  "audio",

            // video
            "mp4"       =>  "video",
            "mpg"       =>  "video",
            "mpeg"      =>  "video",
            "webm"      =>  "video",
            "ogg"       =>  "video",
            "avi"       =>  "video",
            "mov"       =>  "video",
            "flv"       =>  "video",
            "swf"       =>  "video",
            "mkv"       =>  "video",
            "wmv"       =>  "video",

            // image
            "png"       =>  "image",
            "svg"       =>  "image",
            "gif"       =>  "image",
            "jpg"       =>  "image",
            "jpeg"      =>  "image",
            "webp"      =>  "image",

            // document
            "doc"       =>  "document",
            "txt"       =>  "document",
            "docx"      =>  "document",
            "pdf"       =>  "document",
            "csv"       =>  "document",
            "xml"       =>  "document",
            "ods"       =>  "document",
            "xlr"       =>  "document",
            "xls"       =>  "document",
            "xlsx"      =>  "document",

            // archive
            "zip"       =>  "archive",
            "rar"       =>  "archive",
            "7z"        =>  "archive"
        ];
        return isset($fileTypeArray[$type]) ? $fileTypeArray[$type] : null;
    }
}

if (!function_exists('fileDelete')) {
    # file delete
    function fileDelete($file)
    {
        if (File::exists('public/' . $file)) {
            File::delete('public/' . $file);
        }
    }
}


# is Demo Mode
if (!function_exists('isDemoMode')) {
    function isDemoMode()
    {
        return config('custom.demo_mode') == appStatic()::MODE_DEMO;
    }
}

# Is Logged IN
if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        return auth()->check();
    }
}


# Logged IN User
if (!function_exists('user')) {
    function user()
    {
        return auth()->user();
    }
}

# Logged IN User ID
if (!function_exists('userId')) {
    function userId()
    {
        return auth()->id();
    }
}

# Logged IN User Type
if (!function_exists('userType')) {
    function userType()
    {
        return isLoggedIn() ? user()->user_type : false;
    }
}


# Logged in user is Admin
if (!function_exists('isAdmin')) {
    function isAdmin()
    {
        return isLoggedIn() && user()->user_type == appStatic()::TYPE_ADMIN;
    }
}

# Logged in user is Staff
if (!function_exists('isStaff')) {
    function isStaff()
    {
        return isLoggedIn() && user()->user_type == appStatic()::TYPE_STAFF;
    }
}

# Logged in user is customer
if (!function_exists('isCustomer')) {
    function isCustomer($user = null)
    {
        $user = empty($user) ? user() : $user;

        return isLoggedIn() && $user->user_type == appStatic()::TYPE_CUSTOMER;
    }
}

# App Static
if (!function_exists('appStatic')) {
    function appStatic()
    {
        return new \App\Utils\AppStatic();
    }
}

if (!function_exists('getSetting')) {
    # return system settings value
    function getSetting($key, $default = null)
    {
        try {
            $settings = Cache::remember('settings', 86400, function () {
                return SystemSetting::all();
            });

            $setting = $settings->where('entity', $key)->first();

            return $setting == null ? $default : $setting->value;
        } catch (\Throwable $th) {
            return $default;
        }
    }
}

if (!function_exists('renderStarRating')) {
    # render ratings
    function renderStarRating($rating, $maxRating = 5)
    {
        $fullStar = "<i data-feather='star' width='16' height='16' class='text-primary'></i>";

        $rating = $rating <= $maxRating ? $rating : $maxRating;
        $fullStarCount = (int)$rating;

        $html = str_repeat($fullStar, $fullStarCount);
        echo $html;
    }
}

if (!function_exists('renderStarRatingFront')) {
    # render ratings frontend
    function renderStarRatingFront($rating, $maxRating = 5)
    {
        $theme = session('theme');
        if (getTheme() == 'theme1') {
            $fullStar = '<li>
            <span class="d-block text-warning fs-14">
              <i class="bi bi-star-fill"></i>
            </span>
          </li>';
        } else {

            $fullStar = '<li><i class="las la-star text-warning"></i></li>';
        }

        $rating = $rating <= $maxRating ? $rating : $maxRating;
        $fullStarCount = (int)$rating;

        $html = str_repeat($fullStar, $fullStarCount);
        echo $html;
    }
}

if (!function_exists('formatWords')) {
    # format Words
    function formatWords($words)
    {
        if ($words < 10000) {
            // less than 10 thousands
            $words = $words;
        } else if ($words < 1000000) {
            // less than a million
            $words = $words / 1000  . 'k';
        } else if ($words < 1000000000) {
            // less than a billion
            $words = $words / 1000000 . 'M';
        } else {
            // at least a billion
            $words = $words / 1000000000 . 'B';
        }

        return $words;
    }
}

if (!function_exists('formatPrice')) {
    //formats price - truncate price to 1M, 2K if activated by admin
    function formatPrice($price, $truncate = false, $forceTruncate = false, $addSymbol = true, $decimalSeparator = true, $currency_code = null)
    {

        $originalPrice = $price;
        $currency = null;

        if ($currency_code) {
            $currency = Currency::where('code', $currency_code)->first();
        }

        // convert amount equal to local currency
        if (Session::has('currency_code') && Session::has('local_currency_rate')) {

            $price = floatval($price) / (floatval(config('custom.default_currency_rate')) || 1);
            $price = floatval($price) * floatval(Session::get('local_currency_rate'));

            if (session()->get('currency_code') != $currency_code && $currency) {
                $price = floatval($originalPrice) / $currency->rate;
            }
        }
        if (session()->get('currency_code') == $currency_code) {
            $price = $originalPrice;
        }
        // truncate price
        if ($truncate) {
            if (getSetting('truncate_price') == 1 || $forceTruncate == true) {
                if ($price < 1000000) {
                    // less than a million
                    $price = number_format($price, getSetting('no_of_decimals'), getSetting('decimal_separator'), getSetting('thousands_separator'));
                } else if ($price < 1000000000) {
                    // less than a billion
                    $price = number_format($price / 1000000, getSetting('no_of_decimals')) . 'M';
                } else {
                    // at least a billion
                    $price = number_format($price / 1000000000, getSetting('no_of_decimals')) . 'B';
                }
            }
        } else {
            if ($decimalSeparator) {
                // decimals
                $price = number_format($price, getSetting('no_of_decimals'), getSetting('decimal_separator'), getSetting('thousands_separator'));
            }
        }

        if ($addSymbol) {
            // currency symbol
            $symbol             = Session::has('currency_symbol')           ? Session::get('currency_symbol')           : config('custom.default_currency_symbol');
            if ($currency_code && session()->get('currency_code') == $currency_code && $currency) {
                $symbol = $currency->symbol;
            }
            $symbolAlignment    = Session::has('currency_symbol_alignment') ? Session::get('currency_symbol_alignment') : config('custom.default_currency_symbol_alignment');

            if ($symbolAlignment == 0) {
                return $symbol . $price;
            } else if ($symbolAlignment == 1) {
                return $price . $symbol;
            } else if ($symbolAlignment == 2) {
                # space
                return $symbol . ' ' . $price;
            } else {
                # space
                return $price . ' ' .  $symbol;
            }
        }

        return $price;
    }
}

if (!function_exists('priceToUsd')) {
    // price to usd
    function priceToUsd($price)
    {
        // convert amount equal to local currency
        if (Session::has('currency_code') && Session::has('local_currency_rate')) {
            $price = floatval($price) / floatval(Session::get('local_currency_rate'));
        }

        return $price;
    }
}

if (!function_exists('getProjectIcon')) {
    // getProjectIcon
    function getProjectIcon($type)
    {
        $icon = '';
        switch ($type) {
            case 'image':
                $icon = "image";
                break;
            case 'code':
                $icon = "code";
                break;
            case 'speech':
                $icon = "mic";
                break;
            default:
                $icon = "file-text";
                break;
        }
        return $icon;
    }
}

if (!function_exists('availableDataCheck')) {
    // availableDataCheck
    function availableDataCheck($dataType)
    {
        $user = auth()->user();
        $latestPackage = activePackageHistory();
        if (is_null($latestPackage)) {
            return 0;
        }

        $available = 0;
        switch ($dataType) {
            case 'words':
                $available = $latestPackage->new_word_balance == -1 ? PHP_INT_MAX : $latestPackage->this_month_available_words;
                break;

            case 'images':
                $available = $latestPackage->new_image_balance == -1 ? PHP_INT_MAX :  $latestPackage->this_month_available_images;
                break;

            case 's2t':
                $available = $latestPackage->new_s2t_balance == -1 ? PHP_INT_MAX : $latestPackage->this_month_available_s2t;
                break;

            default:
                # code...
                break;
        }
        return  $available;
    }
}

/**
 * When latest package is not null Update the user data balance
 * Either just return
 *
 *
 * */
if (!function_exists('updateDataBalance')) {
    // updateDataBalance
    function updateDataBalance($dataType, $tokens, $user)
    {
        $latestPackage = activePackageHistory();

        if (is_null($latestPackage)) {
            return;
        }

        switch ($dataType) {
            case 'words':
                $latestPackage->this_month_used_words        += (int) $tokens;
                $latestPackage->this_month_available_words   -= (int) $tokens;
                $latestPackage->total_used_words             += (int) $tokens;
                break;

            case 'images':
                $latestPackage->this_month_used_images       += (int) $tokens;
                $latestPackage->this_month_available_images  -= (int) $tokens;
                $latestPackage->total_used_images            += (int) $tokens;

                break;

            case 's2t':
                $latestPackage->this_month_used_s2t        += 1;
                $latestPackage->this_month_available_s2t   -= 1;
                $latestPackage->total_used_s2t             += 1;
                break;

            default:
                # code...
                break;
        }
        $latestPackage->save();
    }
}

if (!function_exists('getUsedWordsPercentage')) {
    // getUsedWordsPercentage
    function getUsedWordsPercentage()
    {
        $user = user();
        if (isCustomer()) {
            $latestPackage = activePackageHistory();
            if (is_null($latestPackage)) {
                return 0;
            }
            $total = $latestPackage->this_month_available_words + $latestPackage->this_month_used_words;
            if ($total == 0) {
                $total = 1;
            }
            $usedPercent = (100 * $latestPackage->this_month_used_words) / $total;
            return $usedPercent > 100 ? 100 : round($usedPercent);
        }
    }
}

if (!function_exists('getUsedImagesPercentage')) {
    // getUsedImagesPercentage
    function getUsedImagesPercentage()
    {
        $user = auth()->user();

        $latestPackage = activePackageHistory();
        if (is_null($latestPackage)) {
            return 0;
        }

        $total = $latestPackage->this_month_used_images + $latestPackage->this_month_available_images;
        if ($total == 0) {
            $total = 1;
        }
        $usedPercent = (100 * $latestPackage->this_month_used_images) / $total;
        return $usedPercent > 100 ? 100 : round($usedPercent);
    }
}

if (!function_exists('getUsedS2TPercentage')) {
    // getUsedS2TPercentage
    function getUsedS2TPercentage()
    {
        $user = auth()->user();
        $latestPackage = activePackageHistory();
        if (is_null($latestPackage)) {
            return 0;
        }
        $total = $latestPackage->this_month_available_s2t + $latestPackage->this_month_used_s2t;
        if ($total == 0) {
            $total = 1;
        }
        $usedPercent = (100 * $latestPackage->this_month_used_s2t) / $total;
        return $usedPercent > 100 ? 100 : round($usedPercent);
    }
}

if (!function_exists('checkLanguage')) {
    function checkLanguage($lang_key)
    {
        return  config('custom.default_language') == $lang_key ? true : false;
    }
}

if (!function_exists('systemSetting')) {
    function systemSetting($key)
    {
        $settings = Cache::remember('settings', 86400, function () {
            return SystemSetting::all();
        });

        $setting = $settings->where('entity', $key)->first();
        return $setting;
    }
}

if (!function_exists('systemSettingsLocalization')) {
    function systemSettingsLocalization($entity, $lang_key = null)
    {
        if ($lang_key == null) {
            $lang_key = App::getLocale();
        }
        $settings = systemSetting($entity);
        $default_lang = getSetting($entity);
        $lang = $default_lang;
        if ($settings) {
            $data = $settings->collectLocalization($entity, $lang_key);

            $lang = $data ?? $default_lang;
        }
        return $lang;
    }
}

if (!function_exists('openAiKey')) {
    #get Api key
    function openAiKey($engine = "openai")
    {
        $key = ($engine == "openai" ? config('services.open-ai.key') : config('services.stable-ai.key'));

        $uses_key =  $engine == "openai" ? getSetting('api_key_use') : getSetting('sd_api_key_use');

        if (!$uses_key) return $key;

        $activeKeys = [];

        if ($uses_key) {
            if ($uses_key == 'random') {
                // get all active key
                if ($engine == "openai") {
                    $activeKeys = OpenAiKey::isActive()->where('engine', 1)->pluck('api_key')->toArray();
                } else {
                    $activeKeys = OpenAiKey::isActive()->where('engine', 2)->pluck('api_key')->toArray(); // stable diffusion
                }
            }
        }

        // merge main key and active key
        array_push($activeKeys, $key);
        $key = $activeKeys[array_rand($activeKeys, 1)];
        return $key;
    }
}


//Count Words
function countWords($text)
{

    $encoding = mb_detect_encoding($text);

    if ($encoding === 'UTF-8') {
        // Count Chinese words by splitting the string into individual characters
        $words = preg_match_all('/\p{Han}|\p{L}+|\p{N}+/u', $text);
    } else {
        // For other languages, use str_word_count()
        $words = str_word_count($text, 0, $encoding);
    }

    return (int)$words;
}
// file upload
if (!function_exists('fileUpload')) {
    function fileUpload($path, $file, $change_name = false)
    {
        $fileName = '';
        if (!$file) {
            return $fileName;
        }

        $allowedExtensions = [
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'txt', 'csv', 'zip',
            'mp3', 'wav', 'ogg', 'mp4', 'webm',
        ];

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv',
            'application/zip', 'application/x-zip-compressed',
            'audio/mpeg', 'audio/wav', 'audio/ogg',
            'video/mp4', 'video/webm',
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if (! in_array($extension, $allowedExtensions, true) || ! in_array($mime, $allowedMimes, true)) {
            return $fileName;
        }

        $original_name = $file->getClientOriginalName();
        if ($change_name) {
            $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($original_name, PATHINFO_FILENAME));
            $name = ($baseName ?: 'file') . '_' . Str::random(16) . '.' . $extension;
        } else {
            $name = Str::random(40) . '.' . $extension;
        }

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $file->move($path, $name);
        $fileName = $path . $name;

        return $fileName;
    }
}
// file update
if (!function_exists('fileUpdate')) {
    function fileUpdate($databaseFile, $path, $file)
    {

        $fileName = "";


        if ($file) {
            $fileName = fileUpload($path, $file);

            if ($databaseFile && file_exists($databaseFile)) {

                unlink($databaseFile);
            }
        } elseif (!$file and $databaseFile) {
            $fileName = $databaseFile;
        }


        return $fileName;
    }
}
// voice over setting enable
if (!function_exists('voiceOverEnable')) {
    function voiceOverEnable()
    {
        $enableVoiceOver = getSetting('default_voiceover');
        if (!$enableVoiceOver) return false;
        if ($enableVoiceOver) {
            $settings = TextToSpeechSetting::where('type', $enableVoiceOver)->first();
            if ($enableVoiceOver == 'google') {
                return $settings->file_name ? true : false;
            }
            if ($enableVoiceOver == 'azure') {
                return $settings->key && $settings->region ? true : false;
            }
        }
        return false;
    }
}
// voice of enable method credentials

if (!function_exists('voiceSettingCredential')) {
    function voiceSettingCredential($service = null)
    {
        if ($service == appStatic()::ELEVEN_LAB) {
            return TextToSpeechSetting::where('type', 'eleven_labs')->first();
        }
        if ($service == appStatic()::OpenAiTTS) {
            return TextToSpeechSetting::where('type', 'open_ai_tts')->first();
        }
        $enableVoiceOver = getSetting('default_voiceover');
        if (!$enableVoiceOver) return false;
        return TextToSpeechSetting::where('type', $enableVoiceOver)->first();
    }
}
// active subscription package
if (!function_exists('activePackageHistory')) {
    function activePackageHistory($user_id = null)
    {
        $user_id = $user_id ?? userId();

        return SubscriptionHistory::latest()->where('subscription_status', 1)
            ->where('user_id', $user_id)
            ->first() ?? [];
    }
}
// active package balance validation
// return status, message, success array
if (!function_exists('activePackageBalance')) {
    function activePackageBalance($type = null, $user_id = null): array
    {
        $user_id = $user_id ?? auth()->user()->id;
        $user = User::findOrFail($user_id);
        $data = [];
        if ($user->user_type == "customer") {

            $activePackageHistory = activePackageHistory($user_id);

            if ($activePackageHistory == null) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Please upgrade your subscription plan'),
                ];
                return $data;
            }

            // package
            $package = $activePackageHistory->subscriptionPackage;
            # 3. validity of the package & verify if the user has word limit

            //  check if allow images is enabled
            if ($type == 'allow_speech_to_text') {
                if ((int) $package->allow_speech_to_text == 0) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Speech to text is not available in this package, please upgrade you plan'),
                    ];
                    return $data;
                }
            }
            //  check if allow_text_to_speech is enabled
            if ($type == 'allow_text_to_speech') {
                if ((int) $package->allow_text_to_speech == 0) {
                    $data = [
                        'status'    => 400,
                        'success'    => false,
                        'message'   => localize('Text to speech is not available in this package, please upgrade you plan'),
                    ];
                    return $data;
                }
            }
            //  check if allow images is enabled
            if ($type == 'allow_ai_code') {
                if ((int) $package->allow_ai_code == 0) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('AI Code is not available in this package, please upgrade you plan'),
                    ];
                    return $data;
                }
            }
            // check if allow custom template content is enabled
            if ($type == 'allow_custom_templates') {
                if ((int) $package->allow_custom_templates == 0) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Custom template is not available in this package, please upgrade you plan'),
                    ];
                    return $data;
                }
            }
            //  check if allow images is enabled
            if ($type == 'allow_images') {
                if ((int) $package->allow_images == 0) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('AI Images is not available in this package, please upgrade you plan'),
                    ];
                    return $data;
                }
            }

            //  check if allow sd images is enabled
            if ($type == 'allow_sd_images') {
                if ((int) $package->allow_sd_images == 0) {
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => localize('Stable Diffusion Image is not available in this package, please upgrade you plan'),
                    ];
                    return $data;
                }
            }

            if (empty($activePackageHistory)) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Please upgrade your subscription plan'),
                ];
                return $data;
            }

            // check validity
            $days = 30;
            $today = date('Y-m-d');
            if ($package->package_type == "yearly") {
                $days = 365; // 1 year
            }

            if ($package->package_type == "lifetime" || $package->package_type == "prepaid") {
                $days = 365 * 100; // 100 years
            }

            if (($activePackageHistory->end_date && $today > $activePackageHistory->end_date) || ($activePackageHistory->expire_by_admin_date && $today > $activePackageHistory->expire_by_admin_date)) {
                $data = [
                    'status'  => 400,
                    'success' => false,
                    'message' => localize('Your subscription is expired, please upgrade you plan'),
                ];
                return $data;
            }
        }
        return $data;
    }
}


// package types
if (!function_exists('readableDate')) {
    function readableDate($dateTime)
    {
        return $dateTime->diffForHumans();
    }
}

// package types
if (!function_exists('package_types')) {
    function package_types(): array
    {
        return [
            'starter' => 'Starter',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
            'lifetime' => 'LifeTime',
            'prepaid' => 'Prepaid'
        ];
    }
}
// subscription log
if (!function_exists('subscriptionLogs')) {
    function subscriptionLogs($total_text, $type, $total_balance, $promptsToken = null, $completionToken = null)
    {
        // types  ,words, tts, images
        if (isCustomer()) {

            $activePackageHistory = activePackageHistory();
            $log = new SubscriptionLog();
            $log->subscription_history_id = $activePackageHistory->id;
            $log->subscription_package_id = $activePackageHistory->subscription_package_id;
            $log->type = $type;
            $log->total_text = $total_text;
            $log->user_id = $activePackageHistory->user_id;
            $log->created_by = auth()->user()->id;
            $log->save();
            return true;
        }
        return false;
    }
}
// limit package purchase
if (!function_exists('limitPurchasePackage')) {
    function limitPurchasePackage()
    {
        $user = auth()->user();
        if ($user->user_typ == 'customer') {
            $package_count = SubscriptionHistory::where('user_id', $user->id)->whereIn('subscription_status', [1, 3])->count();
            if ($package_count > appStatic()::limitPurchasePackage) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }
}
// Subscription Status
if (!function_exists('subscriptionStatus')) {
    function subscriptionStatus(): array
    {
        return [
            '1' => 'Active',
            '2' => 'Expired',
            '3' => 'Subscribed',
        ];
    }
}
// Subscription Status
if (!function_exists('getSubscriptionStatusName')) {
    function getSubscriptionStatusName($status_id)
    {
        $list = subscriptionStatus();
        if (array_key_exists($status_id, $list)) {
            return $list[$status_id];
        }
        return 'Invalid Status';
    }
}

// package price
if (!function_exists('packageSellPrice')) {
    function packageSellPrice($package_id)
    {
        $package = SubscriptionPackage::where('id', $package_id)->first();
        $price = 0;
        if ($package) {
            if ($package->discount_status == 1 && $package->discount) {
                $price = $package->discount_price;
            } else {
                $price = $package->price;
            }
        }
        return $price;
    }
}

//  package discount status
if (!function_exists('packageDiscountStatus')) {
    function packageDiscountStatus($package_id)
    {
        $package = SubscriptionPackage::where('id', $package_id)->first();
        if ($package->discount_status == 1 && $package->discount) {
            return true;
        }
        return false;
    }
}
// check storage manager active
if (!function_exists('activeStorage')) {
    function activeStorage($type = null)
    {
        $storage_type = $type ?? getSetting('active_storage');
        if (!$storage_type) {
            $storage_type = 'local';
        }
        $data = StorageManager::when($storage_type, function ($q) use ($storage_type) {
            $q->where('type', $storage_type);
        })->where('is_active', 1)->first();
        if ($data) {
            return true;
        }
        return false;
    }
}
// store notification

if (!function_exists('saveNotification')) {
    function saveNotification(
        string $title,
        string $url = null,
        string $user_role = null,
        int $user_id = null,
        int $role_id = null,
        string $type = null,
        string $description = null
    ) {
        try {
            $notification = new WrNotification();
            $notification->title = $title;
            $notification->url = $url;
            $notification->user_role = $user_role;
            $notification->user_id = $user_id;
            $notification->role_id = $role_id;
            $notification->type = $type;
            $notification->description = $description;
            $notification->save();
        } catch (\Throwable $th) {

            \Illuminate\Support\Facades\Log::info("Wr Notification failed to process" . json_encode(errorArray($th)));
        }
    }
}

if (!function_exists('recaptchaValidation')) {
    // recaptchaValidation
    function recaptchaValidation($request)
    {
        $score = 1;
        if (getSetting('enable_recaptcha') == 1) {
            $score = RecaptchaV3::verify($request->get('g-recaptcha-response'), 'recaptcha_token');
        }
        return $score;
    }
}

if (!function_exists('currentVersion')) {
    function currentVersion($isNumber = false)
    {
        $version = config('custom.app_version') ? str_replace('v', '', config('custom.app_version')) : null;
        # need to check bcz of setup route
        if (Schema::hasTable('system_settings')) {
            $settings = SystemSetting::where('entity', 'software_version')->first();
            if ($settings) {
                $version = $settings->value;
            }
        }
        if (empty($version)) {
            $version = config('custom.app_version') ? str_replace('v', '', config('custom.app_version')) : null;
        }

        return $isNumber ? intval(str_replace(".", "", $version)) : $version;
    }
}

if (!function_exists('getNumberFromString')) {
    function getNumberFromString($str, $replaceValue = ["."], $replaceWith = "")
    {
        return intval(str_replace($replaceValue, $replaceWith, $str));
    }
}

if (!function_exists('isGreater')) {
    function isGreater($currentVersion, $upcomingVersion, $isNumberConversion = false, $replaceValue = ["."], $replaceWith = "")
    {
        if ($isNumberConversion) {
            $currentVersion  = intval(str_replace($replaceValue, $replaceWith, $currentVersion));
            $upcomingVersion = intval(str_replace($replaceValue, $replaceWith, $upcomingVersion));
        }

        return $currentVersion < $upcomingVersion;
    }
}

if (!function_exists('paymentGateway')) {
    function paymentGateway($type = null)
    {
        $paymentGateway = Cache::remember('paymentGateway', 86400, function () {
            return PaymentGateway::all();
        });
        if ($type) {
            $paymentGateway = $paymentGateway->where('gateway', $type)->first();
        }
        return $paymentGateway;
    }
}
if (!function_exists('paymentGatewayValue')) {
    function paymentGatewayValue($gateway, $key)
    {
        $paymentGateway = paymentGateway($gateway);
        $value = '';
        if ($paymentGateway) {
            $gateway_id = $paymentGateway->id;
            $value = PaymentGatewayDetail::where('payment_gateway_id', $gateway_id)->where('key', $key)->value('value');
        }
        return $value;
    }
}
# auto subscription
if (!function_exists('autoSubscription')) {
    function autoSubscription($gateway, $history_id)
    {
        $user_id = userId();

        $recurringPayment = SubscriptionRecurringPayment::where('subscription_history_id', $history_id)
            ->where('user_id', $user_id)
            ->where('gateway', $gateway)
            ->first();

        if ($recurringPayment) {
            if ($recurringPayment->is_active == 1) {
                $text = 'Auto Renew Package Active';
            } elseif ($recurringPayment->is_active != 1) {
                $text = 'Auto Renew Package DeActive';
            }
            return $text;
        }
        return false;
    }
}
# module check
if (!function_exists('isModuleActive')) {
    function isModuleActive($name)
    {
        return true;
        $status = false;

        $module = Module::find($name);
        if ($module) {
            $status = $module->isEnabled();
            if ($status) {
                $modulePath = $module->getPath() . '/Providers/RouteServiceProvider.php';
                if (file_exists($modulePath)) {
                    $module = WritebotModule::where('name', $name)->first();
                    if ($module) {
                        if ($module->is_default == 1) {
                            $status = true;
                        }
                        if ($module->is_paid == 1) {
                            $status = $module->purchase_code && $module->domain  ? true : false;
                        }
                    }
                }
            }
        }
        return $status;
    }
}
# open ai model
if (!function_exists('openAiModel')) {
    function openAiModel($type = 'chat')
    {
        $user = auth()->user();
        if ($type == 'chat') {
            $key = getSetting('ai_chat_model') ?? 'gpt-3.5-turbo';
        } elseif ($type == 'blog_wizard') {
            $key = getSetting('ai_blog_wizard_model') ?? 'gpt-3.5-turbo-16k';
        } elseif ($type == 'code') {
            $key = getSetting('default_open_ai_model') ?? 'gpt-3.5-turbo';
        }
        if ($user) {
            if (isCustomer()) {
                $activePackageHistory = activePackageHistory();
                if ($activePackageHistory) {
                    $package = $activePackageHistory->subscriptionPackage;
                    if ($package) {
                        $model = $package->openai_model;
                        if ($model) {
                            $key = $model->key;
                        }
                    }
                }
            }
        }
        return $key;
    }
}
# text to a slug.
if (!function_exists('convertToSlug')) {
    function convertToSlug($text)
    {
        $text = mb_strtolower(trim(preg_replace('~[^\pL\d]+~u', ' ', $text)));

        // Remove diacritics from the text
        $textV = removeDiacritics($text);

        // Replace spaces and special characters with dashes
        $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($textV));

        // Remove leading and trailing dashes
        $slug = trim($slug, '-');
        if ($slug == '') {
            $normalize = new SlugNormalizer;
            $slug = $normalize->normalize($text);
        }
        return $slug;
    }
}
# This function removes diacritics from Vietnamese text.
if (!function_exists('removeDiacritics')) {
    function removeDiacritics($text)
    {
        $diacritics = array(
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
        );
        return strtr($text, $diacritics);
    }
}
# response log data store
if (!function_exists('responseDataStore')) {
    function responseDataStore(array $data)
    {
        if (!empty($data)) {
            AiResponseLog::create($data);
        }
    }
}
# pwa settings

if (!function_exists('pwaSettings')) {
    function pwaSettings()
    {
        return  Cache::rememberForever('pwaSettings', function () {
            return PWASettings::first();
        });
    }
}

# adSense
if (!function_exists('adSense')) {
    function adSense($type)
    {
        if (!getSetting('enable_google_adsense')) {
            return null;
        }
        // return  Cache::rememberForever('adSense', function () use ($type) {
        return    AdSense::where('slug', $type)->when(getSetting('dashboard_adSense'), function ($q) {
            $q->where('is_dashboard', 1);
        })->where('is_active', 1)->first();
        // });
    }
}

if (!function_exists('adSense_header_top')) {
    function adSense_header_top()
    {
        if (adSense('header-top')) {
            return  adSense('header-top')->code;
        }
    }
}

if (!function_exists('adSense_bottom_trusted_by')) {
    function adSense_bottom_trusted_by()
    {
        if (adSense('bottom-trusted-by')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('bottom-trusted-by')->code . '</div>
                    </center>';
        }
    }
}
if (!function_exists('adSense_top_best_feature')) {
    function adSense_top_best_feature()
    {
        if (adSense('top-best-feature')) {
            return '<center>
                            <div class="google-ads-728 mb-6">' . adSense('top-best-feature')->code . '</div>
                        </center>';
        }
    }
}
if (!function_exists('adSense_top_template_section')) {
    function adSense_top_template_section()
    {
        if (adSense('top-template-section')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('top-template-section')->code . '</div>
                    </center>';
        }
    }
}
if (!function_exists('adSense_top_subscription_package')) {
    function adSense_top_subscription_package()
    {
        if (adSense('top-subscription-package')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('top-subscription-package')->code . '</div>
                    </center>';
        }
    }
}
if (!function_exists('adSense_top_trail_banner_section')) {
    function adSense_top_trail_banner_section()
    {
        if (adSense('	')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('top-trail-banner-section')->code . '</div>
                    </center>';
        }
    }
}
if (!function_exists('adSense_top_footer_section')) {
    function adSense_top_footer_section()
    {
        if (adSense('top-footer-section')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('top-footer-section')->code . '</div>
                    </center>';
        }
    }
}
// adSense for backend dashboard

# adSense dashboard header bottom section
if (!function_exists('adSense_dashboard_header_bottom_section')) {
    function adSense_dashboard_header_bottom_section()
    {
        if (!dashboardAdSense()) return null;
        if (adSense('header-bottom')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('header-bottom')->code . '</div>
                    </center>';
        }
    }
}

# adSense dashboard footer top section
if (!function_exists('adSense_dashboard_footer_top_section')) {
    function adSense_dashboard_footer_top_section()
    {
        if (!dashboardAdSense()) return null;
        if (adSense('footer-top')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('footer-top')->code . '</div>
                    </center>';
        }
    }
}

# adSense dashboard profile bottom section
if (!function_exists('adSense_dashboard_profile_bottom_section')) {
    function adSense_dashboard_profile_bottom_section()
    {
        if (!dashboardAdSense()) return null;
        if (adSense('dashboard-profile-bottom')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('dashboard-profile-bottom')->code . '</div>
                    </center>';
        }
    }
}

# adSense sidebar customer profile top section
if (!function_exists('adSense_sidebar_customer_profile_top_section')) {
    function adSense_sidebar_customer_profile_top_section()
    {
        if (!dashboardAdSense()) return null;
        if (adSense('sidebar-customer-profile-top')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('sidebar-customer-profile-top')->code . '</div>
                    </center>';
        }
    }
}
# adSense sidebar customer profile top section
if (!function_exists('adSense_recent_project_top_section')) {
    function adSense_recent_project_top_section()
    {
        if (!dashboardAdSense()) return null;
        if (adSense('recent-project-top')) {
            return '<center>
                        <div class="google-ads-728 mb-6">' . adSense('recent-project-top')->code . '</div>
                    </center>';
        }
    }
}
# adSense sidebar customer profile top section
if (!function_exists('dashboardAdSense')) {
    function dashboardAdSense()
    {
        if (!getSetting('dashboard_adSense')) return null;

        if (auth()->user()->user_type == 'customer') {
            $package = activePackageHistory();
            if (!$package) return true;
            return $package->subscriptionPackage?->package_type == 'starter' ? true : false;
        }
        return null;
    }
}

//  end dashboard adSense 

if (!function_exists('defaultThemeMode')) {
    function defaultThemeMode()
    {
        return !empty($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
    }
}

if (!function_exists('maxTokenForWord')) {
    function maxTokenForWord($request_max_tokens = null)
    {

        $max_tokens =  getSetting('default_max_result_length', -1);

        if (isCustomer()) {
            $this_month_available_words = activePackageHistory()->this_month_available_words;

            if ($max_tokens != -1 && !is_null($max_tokens)) {

                if (!$request_max_tokens) {
                    $max_tokens = $this_month_available_words <= $max_tokens ? $this_month_available_words : $max_tokens;
                } elseif ($request_max_tokens) {
                    $max_tokens = $request_max_tokens <= $this_month_available_words ? $request_max_tokens : $this_month_available_words;
                }
            } else if ($max_tokens == -1 && $request_max_tokens) {

                $max_tokens = $request_max_tokens;
            } elseif ($max_tokens != -1 && $request_max_tokens) {

                if (!$request_max_tokens) {
                    $max_tokens = $this_month_available_words <= $max_tokens ? $this_month_available_words : $max_tokens;
                } elseif ($request_max_tokens) {
                    $max_tokens = $request_max_tokens <= $this_month_available_words ? $request_max_tokens : $this_month_available_words;
                }
            } else if (is_null($max_tokens) && is_null($request_max_tokens)) {
                $max_tokens = $this_month_available_words;
            } elseif ($max_tokens == -1 && is_null($request_max_tokens)) {
                $max_tokens = $this_month_available_words;
            } else {

                return null;
            }
        } else {
            $max_tokens = $request_max_tokens;
        }
        return $max_tokens;
    }
}

if (!function_exists("convertJsonDecode")) {
    function convertJsonDecode($value = null)
    {
        if (empty($value)) {
            return [];
        }

        $jsonDecode = json_decode($value, true);

        if (gettype($jsonDecode) == "string") {
            $jsonDecode = json_decode($jsonDecode, true);
        }

        return $jsonDecode;
    }
}


if (!function_exists("getCustomerBalance")) {
    function getCustomerBalance()
    {

        if (!isLoggedIn()) {

            return abort(401);
        }

        $activePackageHistory = activePackageHistory(userId());

        if($activePackageHistory->new_word_balance == -1) {
            return PHP_INT_MAX;
        }
        
        $balance = $activePackageHistory ? $activePackageHistory->this_month_available_words : 0;

        return max($balance, 0);
    }
}




# Random String Number Generator

if (!function_exists('randomStringNumberGenerator')) {
    function randomStringNumberGenerator(
        $length = 6,
        $includeNumbers = true,
        $includeLetters = false,
        $includeSymbols = false
    ) {
        $chars = [
            'letters' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numbers' => '0123456789',
            'symbols' => '!@#$%^&*()-_+=<>?'
        ];

        $password = '';
        $charSets = [];

        if ($includeLetters) {
            $charSets[] = $chars['letters'];
        }

        if ($includeNumbers) {
            $charSets[] = $chars['numbers'];
        }

        if ($includeSymbols) {
            $charSets[] = $chars['symbols'];
        }

        $charSetsCount = count($charSets);

        if ($charSetsCount === 0) {
            return 'Invalid character set configuration';
        }

        for ($i = 0; $i < $length; $i++) {
            $charSet = $charSets[$i % $charSetsCount];
            $password .= $charSet[random_int(0, strlen($charSet) - 1)];
        }

        return $password;
    }
}


if (!function_exists("initPdfParser")) {
    function initPdfParser()
    {
        return new \Smalot\PdfParser\Parser();
    }
}



if (!function_exists("commonLog")) {
    function commonLog(
        $title,
        $payloads = [],
        $channel  = "daily"
    ) {
        return Log::info($title . " " . json_encode($payloads));
    }
}



if (!function_exists("initOpenAi")) {
    function initOpenAi($openAiKey = null)
    {
        $openAiKey = $openAiKey ?? openAiKey();

        return new OpenAi($openAiKey);
    }
}

if (!function_exists("customOpenAi")) {
    function customOpenAi($openAiKey = null)
    {
        $openAiKey = $openAiKey ?? openAiKey();


        return new OpenAi($openAiKey);
    }
}



if (!function_exists("miliTimeFormat")) {
    function miliTimeFormat()
    {

        return now()->format("Y-m-d H:i:s, v");
    }
}

if (!function_exists("currentUrl")) {
    function currentUrl()
    {

        return request()->fullUrl();
    }
}


if (!function_exists("setArticleGenMaxWord")) {
    function setArticleGenMaxWord()
    {
        $request = request();

        return isset($request->article_generate_max_word) && $request->article_generate_max_word > 0 ? $request->article_generate_max_word : 0;
    }
}

if (!function_exists("getArticleGenMaxWord")) {
    function getArticleGenMaxWord()
    {

        return session()->get('article_generate_max_word') ?? 0;
    }
}

if (!function_exists("balanceError")) {
    function balanceError()
    {

        return [
            'status'  => 404,
            'success' => false,
            'message' => 'Insufficient Balance',
        ];
    }
}

if (!function_exists("apiError")) {
    function apiError($message = null)
    {

        return [
            'status'  => 404,
            'success' => false,
            'message' => $message,
        ];
    }
}

if (!function_exists("promptGenerator")) {
    function promptGenerator($lang = null, $title = null, $promptOutlines = null)
    {

        return "Write an Article in " . $lang . " language. Generate article (NB: Must not contain title) about " . $title . " with following outline " . $promptOutlines . " Do not add other headings or write more than the specific headings. Give the heading output in bold font.";
    }
}

if (!function_exists("isCapable")) {
    function isCapable($isCapable = true)
    {

        if (isCustomer() && getCustomerBalance() <= 0) {
            flash(localize("Sorry, " . user()->name . " you don't have enough balance to proceed next step."))->warning();

            $isCapable = false;
        }

        return $isCapable;
    }
}

if (!function_exists('sendMail')) {
    function sendMail($receiverEmail, $receiverName, $type, $data = [])
    {
        $senderEmail  = config('custom.mail_from_address');
        $senderName   = config('custom.mail_from_name');
        $email_driver = config('custom.mail_mailer');
        $template     = EmailTemplate::where('type', $type)->where('is_active', 1)->first();
        if (!$template) return false;
        $subject = $template->subject;
        $body    = EmailTemplate::emailTemplateBody($template->code, $data);
        try {

            Mail::send('emails.emailBody', compact('body'), function ($message) use ($receiverEmail, $receiverName, $senderName, $senderEmail, $subject) {
                $message->to($receiverEmail, $receiverName)->subject($subject);
                $message->from($senderEmail, $senderName);
            });
        } catch (\Throwable $th) {          
            Log::info('send mail issues :'.$th->getMessage());
        }
    }
}
if (!function_exists('activeEmailTemplate')) {
    function activeEmailTemplate($type)
    {
        $template     = EmailTemplate::where('type', $type)->where('is_active', 1)->first();
        if (!$template) return false;
        return true;
    }
}

if (!function_exists('openAiErrorMessage')) {
    function openAiErrorMessage($openAiKey = null)
    {
        $openAiKey = $openAiKey ?? openAiKey();
        $message = null;

        if (!$openAiKey) {
            return  $message = 'Open Ai key not found.Please setup open ai key from Ai setting';
        };

        $open_ai = initOpenAi($openAiKey);
        $models  = $open_ai->listModels();
        $models  = json_decode($models);

        if ($models) {
            if (property_exists($models, 'error')) {
                $message = $models->error->message;
            }
        }

        return $message;
    }
}
if (!function_exists('openAiSupportedModels')) {
    function openAiSupportedModels($openAiKey = null)
    {
        $openAiKey = $openAiKey ?? openAiKey();
        if (openAiErrorMessage($openAiKey) != null) return [];

        $open_ai = new OpenAi($openAiKey);
        $models  = $open_ai->listModels();
        $models  = json_decode($models);
        $data    = [];

        if ($models != null) {
            if (property_exists($models, 'error')) {
                return $data;
            }
            $models = $models->data;
            foreach ($models as $model) {
                $data[] = $model->id;
            }
        }

        return $data;
    }
}




if (!function_exists("apiResponse")) {
    function apiResponse(
        $status = true,
        $code = 201,
        $message = null,
        $data = [],
        $optional = []
    ) {

        $payloads = [
            "status"   => $status,
            "code"     =>  $code,
            "message"  => $message,
            "data"     => $data,
            "optional" => [
                $optional
            ],
        ];

        return response()->json($payloads, $code);
    }
}

if (!function_exists("getPdfSessionChatCode")) {
    function getPdfSessionChatCode()
    {

        return session("chat_code");
    }
}

if (!function_exists("getSession")) {
    function getSession($keyword)
    {

        return session($keyword);
    }
}

if (!function_exists("setSession")) {
    function setSession($keyword, $value)
    {

        return session([$keyword => $value]);
    }
}


if (!function_exists("errorName")) {
    function errorName($name)
    {
        return view("errors.error", ["name" => $name])->render();
    }
}

if (!function_exists("modelEngines")) {
    function modelEngines()
    {
        return new \App\Services\Engine\ModelEngine();
    }
}


if (!function_exists("getFileContents")) {
    function getFileContents($file)
    {
        return file_get_contents($file, "r");
    }
}


/**
 * Method Will Return false either array.
 * */
if (!function_exists("isOpenAiRaiseError")) {
    function isOpenAiRaiseError($jsonDecodeFineTune)
    {

        if (isset($jsonDecodeFineTune["error"])) {
            Log::info("Open AI Errors : " . json_encode($jsonDecodeFineTune["error"]));

            return $jsonDecodeFineTune["error"]["message"];
        }

        return false;
    }
}

# get file height, width
if (!function_exists("imageDimension")) {
    function imageDimension($imageUrl, $withNHeight = false, $width = false, $height = false)
    {
        if (!$imageUrl) return null;
        try {
            [$w, $h] = getimagesize($imageUrl);
            if ($width) {
                return  $w;
            } elseif ($height) {
                return $h;
            } else if ($withNHeight) {
                return  $w . 'x' . $h;
            }
        } catch (Throwable $th) {
            Log::info('image dimension :', errorArray($th));
            return null;
        }
    }
}

if (!function_exists("getSubscriptionBasedModel")) {
    function getSubscriptionBasedModel($default)
    {
        $user = user();

        if ($user->user_type == "customer" && activePackageHistory() && activePackageHistory()->subscriptionPackage) {
            return activePackageHistory()?->subscriptionPackage?->openai_model?->key ?? $default;
        }

        return $default;
    }
}

if (!function_exists("getDefaultModelBasedOnAiEngine")) {
    function getDefaultModelBasedOnAiEngine()
    {
        $fallback   = 'gpt-3.5-turbo'; 
        $appStatic  = appStatic();

        $model = match (getSetting('default_ai_engine')){
            $appStatic::OPEN_AI_ENGINE      => getSetting('default_open_ai_model'),
            $appStatic::CLAUDE_AI_ENGINE    => getSetting('default_claude_ai_model'),
            $appStatic::DEEPSEEK_AI_ENGINE  => getSetting('default_deepseek_ai_model'),
        };

        return $model ?? $fallback;
    }
}

if (!function_exists("templatesEngine")) {
    function templatesEngine()
    {
        return getSetting('default_ai_engine');
    }
}

if (!function_exists("aiChatEngine")) {
    function aiChatEngine()
    {
        return getSetting('default_ai_engine');
    }
}

if (!function_exists("aiRewriterEngine")) {
    function aiRewriterEngine()
    {
        return getSetting('default_ai_engine');
    }
}

if (!function_exists("aiCodeEngine")) {
    function aiCodeEngine()
    {
        return getSetting('default_ai_engine');
    }
}

if (!function_exists("aiBlogWizardEngine")) {
    function aiBlogWizardEngine()
    {
        return getSetting('default_ai_engine');
    }
}

