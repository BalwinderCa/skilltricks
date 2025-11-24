<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MediaManagerController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Frontend\CustomerController;
use App\Http\Controllers\Frontend\ContactUsController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Frontend\SubscribersController;
use App\Http\Controllers\Frontend\SubscriptionsController;
use App\Http\Controllers\Backend\Settings\SummerNoteController;
use App\Http\Controllers\Backend\Payments\Duitku\DuitkuController;
use App\Http\Controllers\Backend\Payments\IyZico\IyZicoController;
use App\Http\Controllers\Backend\Payments\Paypal\PaypalController;
use App\Http\Controllers\Backend\Payments\Midtrans\MidtransController;
use App\Http\Controllers\Backend\Payments\Paystack\PaystackController;
use App\Http\Controllers\Backend\Payments\Razorpay\RazorpayController;
use App\Http\Controllers\Backend\Payments\Paytm\PaytmPaymentController;
use App\Http\Controllers\Backend\Payments\Molile\MolilePaymentController;
use App\Http\Controllers\Backend\Payments\Stripe\StripePaymentController;
use App\Http\Controllers\Backend\Payments\Flutterwave\FlutterwaveController;
use App\Http\Controllers\Backend\Payments\Yookassa\YookassaPaymentController;
use App\Http\Controllers\Backend\Payments\Mercadopago\MercadopagoPaymentController;
use App\Http\Controllers\Backend\Payments\Stripe\StripeWithAutoRecurringPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get("stream", [DemoController::class, "eventTry"]);

# demo
Route::get('/demo/db-cron', [DemoController::class, 'cron_1']);
Route::get('/demo/folder-cron', [DemoController::class, 'cron_2']);

Auth::routes(['verify' => true]);

Route::controller(LoginController::class)->group(function () {
    Route::get('/logout', 'logout')->name('logout');
    Route::get('/social-login/redirect/{provider}', 'redirectToProvider')->name('social.login');
    Route::get('/social-login/{provider}/callback', 'handleProviderCallback')->name('social.callback');
});

Route::controller(VerificationController::class)->group(function () {
    Route::get('/verify-phone', 'verifyPhone')->name('verification.phone');
    Route::get('/email/resend', 'resend')->name('verification.resend');
    Route::get('/verification-confirmation/{code}', 'verification_confirmation')->name('email.verification.confirmation');
    Route::post('/verification-confirmation', 'phone_verification_confirmation')->name('phone.verification.confirmation');
});

Route::controller(ForgotPasswordController::class)->group(function () {
    # forgot password
    Route::get('/reset-password-by-phone', 'resetByPhone')->name('forgotPw.resetByPhone');
    Route::post('/reset-password-by-phone', 'updatePw')->name('forgotPw.update');
});

Route::get('/theme/{name?}', [HomeController::class, 'theme'])->name('theme.change');

Route::group(['prefix' => '', 'middleware' => ['frontendAllow']], function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/pricing', [HomeController::class, 'pricing'])->name('home.pricing');
    Route::get('/testimonials', [HomeController::class, 'testimonials'])->name('home.testimonials');

    # blogs
    Route::get('/blogs', [HomeController::class, 'allBlogs'])->name('home.blogs');
    Route::get('/blogs/{slug}', [HomeController::class, 'showBlog'])->name('home.blogs.show');

    # pages
    Route::get('/pages/about-us', [HomeController::class, 'aboutUs'])->name('home.pages.aboutUs');
    Route::get('/pages/contact-us', [HomeController::class, 'contactUs'])->name('home.pages.contactUs');
    Route::get('/pages/{slug}', [HomeController::class, 'showPage'])->name('home.pages.show');

    Route::get("faq",[HomeController::class,"faq"])->name("home.faq");

# contact us message
Route::post('/contact-us', [ContactUsController::class, 'store'])->name('contactUs.store');
});
# subscription packages
Route::post('/subscribe-to-package', [SubscriptionsController::class, 'subscribe'])->name('website.subscriptions.subscribe');
Route::get('/subscribe-to-package', [SubscriptionsController::class, 'index'])->name('website.subscriptions.index');

# Subscribed Users
Route::post('/subscribers', [SubscribersController::class, 'store'])->name('subscribe.store');


# authenticated routes
Route::group(['prefix' => '', 'middleware' => ['customer', 'verified', 'isBanned']], function () {
    # customer routes
    Route::get('/customer-order-history', [CustomerController::class, 'orderHistory'])->name('customers.orderHistory');
    Route::get('/customer-address', [CustomerController::class, 'address'])->name('customers.address');
    Route::get('/customer-profile', [CustomerController::class, 'profile'])->name('customers.profile');
    Route::post('/customer-profile', [CustomerController::class, 'updateProfile'])->name('customers.updateProfile');
});

# media files routes
Route::group(['prefix' => '', 'middleware' => ['auth']], function () {
    Route::get('/media-manager/get-files', [MediaManagerController::class, 'index'])->name('uppy.index');
    Route::get('/media-manager/get-selected-files', [MediaManagerController::class, 'selectedFiles'])->name('uppy.selectedFiles');
    Route::post('/media-manager/add-files', [MediaManagerController::class, 'store'])->name('uppy.store');
    Route::get('/media-manager/delete-files/{id}', [MediaManagerController::class, 'delete'])->name('uppy.delete');
});

# payment gateways
Route::group(['prefix' => ''], function () {
    # paypal
    Route::post('/paypal/success', [PaypalController::class, 'success'])->name('paypal.success');
    Route::get('/paypal/cancel', [PaypalController::class, 'cancel'])->name('paypal.cancel');
    Route::post('/paypal/create-paypal-order', [PaypalController::class, 'createOrder'])->name('create.payPal.order');
    Route::post('/paypal/capture-paypal-order', [PaypalController::class, 'capturePayPalOrder'])->name('capture.payPal.order');
    # stripe
    Route::any('/stripe/create-session', [StripePaymentController::class, 'checkoutSession'])->name('stripe.checkoutSession');
    Route::get('/stripe/success', [StripePaymentController::class, 'success'])->name('stripe.success');
    Route::get('/stripe/cancel', [StripePaymentController::class, 'cancel'])->name('stripe.cancel');

    Route::get('/stripe/subscribe', [StripeWithAutoRecurringPaymentController::class, 'subscribe'])->name('stripe.subscribe');
    Route::get('/stripe/subscribePay', [StripeWithAutoRecurringPaymentController::class, 'subscribePay'])->name('stripe.stripeSubscribePay');
    Route::get('/stripe/prepaidPay', [StripeWithAutoRecurringPaymentController::class, 'prepaidPay'])->name('stripe.stripePrepaidPay');

    # paytm
    Route::any('/paytm/callback', [PaytmPaymentController::class, 'callback'])->name('paytm.callback');

    # razorpay
    Route::post('razorpay/payment', [RazorpayController::class, 'payment'])->name('razorpay.payment');

    # iyzico
    Route::any('/iyzico/payment/callback', [IyZicoController::class, 'callback'])->name('iyzico.callback');

    # paystack
    Route::any('/paystack/payment/callback', [PaystackController::class, 'callback'])->name('paystack.callback');

    # flutterwave
    Route::any('/flutterwave/payment/callback', [FlutterwaveController::class, 'callback'])->name('flutterwave.callback');

    # duitku
    Route::any('/duitku/payment/callback', [DuitkuController::class, 'paymentCallback'])->name('duitku.callback');
    Route::any('/duitku/payment/submit', [DuitkuController::class, 'pay'])->name('duitku.pay');
    Route::any('/duitku/payment/return', [DuitkuController::class, 'myReturnCallback'])->name('duitku.return');

    # yookassa
    Route::get('/youkassa/finish', [YookassaPaymentController::class, 'finish'])->name('youkassa.finish');



    # molile
    Route::get('/molile/redirect', [MolilePaymentController::class, 'redirect'])->name('molile.redirect');

    # mercadopago
    Route::get('/mercadopago/redirect', [MercadopagoPaymentController::class, 'redirect'])->name('mercadopago.redirect');
    Route::get('/mercadopago/failed', [MercadopagoPaymentController::class, 'failed'])->name('mercadopago.failed');

    # midtrans
    Route::get('/midtrans/payment/callback', [MidtransController::class, 'callback'])->name('midtrans.callback');
    Route::get('/midtrans/payment/finish', [MidtransController::class, 'success'])->name('midtrans.success');
    Route::get('/midtrans/payment/unfinish', [MidtransController::class, 'failed'])->name('midtrans.failed');
    Route::get('/midtrans/payment/error', [MidtransController::class, 'failed'])->name('midtrans.error');
    Route::post('/midtrans/payment/payment-notification', [MidtransController::class, 'paymentNotification'])->name('midtrans.payment-notification');
    Route::post('/midtrans/payment/pay-account-notification', [MidtransController::class, 'payAccountNotification'])->name('midtrans.pay-account-notification');
    Route::post('/midtrans/payment/recurring-notification', [MidtransController::class, 'recurringNotification'])->name('midtrans.recurring-notification');

    Route::get('test', [TestController::class, 'test']);
    Route::get('migrate', [TestController::class, 'migrate']);
    Route::get('version-change', [TestController::class, 'VersionChange']);
});

Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/paypal', [PaypalController::class, 'handleWebhook']);
    Route::post('/stripe', [StripeWithAutoRecurringPaymentController::class, 'handleWebhook']);
});
Route::get('/pages/{slug}', [HomeController::class, 'showPage'])->name('home.pages.show');
Route::get('privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('terms-of-conditions', [HomeController::class, 'termsCondition'])->name('terms-of-conditions');
Route::post('file-upload', [SummerNoteController::class, 'summernoteFileUpload'])->name('file-upload');