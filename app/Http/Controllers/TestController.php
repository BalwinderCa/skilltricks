<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Page;
use App\Models\User;
use App\Models\Theme;
use App\Models\AdSense;
use App\Models\Project;
use App\Traits\Language;
use Copyleaks\Copyleaks;
use App\Mail\AmazonSESMail;
use App\Models\PWASettings;
use App\Models\MediaManager;
use App\Traits\SystemUpdate;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\SystemSetting;
use App\Traits\GenerateVoice;
use Faker\Generator as Faker;
use Orhanerday\OpenAi\OpenAi;
use App\Models\PaymentGateway;
use App\Models\WritebotModule;
use App\Models\ElevenLabsModel;
use App\Exports\CustomersExport;
use App\Models\PageLocalization;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use App\Http\Services\SerperService;
use App\Models\ElevenLabsModelVoice;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Support\Entities\Priority;
use Illuminate\Support\Facades\Artisan;
use App\Http\Services\ElevenLabsService;
use App\Http\Services\OpenAiCustomService;
use League\CommonMark\Normalizer\SlugNormalizer;
use App\Http\Controllers\Backend\Templates\TemplatesController;
use App\Http\Controllers\Backend\Payments\Paypal\PaypalController;
use App\Http\Controllers\Backend\Payments\Stripe\StripeWithAutoRecurringPaymentController;

class TestController extends Controller
{
  //
  use Language;
  use GenerateVoice;
  use SystemUpdate;
  public function index(Faker $faker)
  {
  }
  /**
   * fact 1 : unlimited
   * fact 2 : customer check and available balance checking
   * fact 3 :request max token compare with available balance
   */
  public function test(Request $request)
  {
    // SystemSetting::updateOrCreate([
    //   'entity' => 'enable_stripe_autoPayment'
    // ], [
    //   'value' => 1,
    // ]);
    // cacheClear();
    // dd('ok');

    // $createProduct = StripeWithAutoRecurringPaymentController::createProduct();
    // $lists         = StripeWithAutoRecurringPaymentController::listProducts();
    // $createPlan         = StripeWithAutoRecurringPaymentController::createPlan($package = '');
    // $lists         = StripeWithAutoRecurringPaymentController::listPlans();
    $lists         = StripeWithAutoRecurringPaymentController::subscribeCancel();

    dd($lists);
    // $email = new AmazonSESMail();
    // Mail::to('nayem7.cse@gmail.com')->send($email);

    // return 'Email Sent Successfully!';
    $data['name'] = 'abu nayem';
    $data['email'] = 'nayem7.cse@gmail.com';
    $data['phone'] = '032957438';
    sendMail('nayem7.cse@gmail.com', 'Abu Nayem', 'welcome-email');
    dd('ok');

    DB::table('system_settings')->insert(['entity' => 'enable_stripe_autoPayment', 'value' => 1]);
    DB::table('system_settings')->insert(['entity' => 'dashboard_adSense', 'value' => 1]);
    DB::table('system_settings')->insert(['entity' => 'is_hide_contact_us', 'value' => 1]);
    DB::table('system_settings')->insert(['entity' => 'amazon_ses', 'value' => 1]);
  }
  private function storePage($request, $modelId = null)
  {

    DB::table('system_settings')->insert(['key' => 'dashboard_adSense', 'entity' => 1]);
    cacheClear();
    dd('ok');
  }
  // baclup
  function migrate(Request $request)
  {

    if ($request->has('smartyCoder') && $request->smartyCoder == 'aminulislam') {
      Artisan::call('migrate');

      dd('Welcome! see you again');
    }
    dd('You are not a smarty coder');
  }
  public function VersionChange(Request $request)
  {
    if ($request->version) {
      $version = $request->version;
      writeToEnvFile('APP_VERSION', 'v' . $version);
      SystemSetting::updateOrCreate(
        [
          'entity' => 'software_version'
        ],
        [
          'value' => $version
        ]
      );

      SystemSetting::updateOrCreate(
        [
          'entity' => 'last_update'
        ],
        [
          'value' => Carbon::now()
        ]
      );
      cacheClear();
      dd('Version Change Successfully');
    }
  }
}
