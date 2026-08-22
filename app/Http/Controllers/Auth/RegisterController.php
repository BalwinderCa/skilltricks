<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistration\UserRegistrationStoreReqeust;
use App\Jobs\User\EmailConfirmationJob;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\OrganizationService;
use App\Services\UserService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*

    |--------------------------------------------------------------------------

    | Register Controller

    |--------------------------------------------------------------------------

    |

    | This controller handles the registration of new users as well as their

    | validation and creation. By default this controller uses a trait to

    | provide this functionality without requiring any additional code.

    |

    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.

     *

     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.

     *

     * @return void
     */
    public function __construct()
    {

        $this->middleware('guest');

    }

    // registration form validation

    protected function validator(array $data)
    {

        return Validator::make($data, [

            'name' => 'required|string|max:255',

            'password' => 'required|string|min:6|confirmed',

        ]);

    }

    // make new registration here

    protected function create(array $data)
    {

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

            $user = User::create($data);

            return $user;

        }

        return null;

    }

    // register new customer here

    public function register(
        UserRegistrationStoreReqeust $request,
        UserService $userService,
        OrganizationService $organizationService
    ) {

        try {

            DB::beginTransaction();

            if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {

                $isEmailExists = $userService->findByColumnsWithValues(['email', '=', $request->email]);

                if (! empty($isEmailExists)) {

                    flash(localize('Email or Phone already exists.'))->error();

                    return back()->withInput();

                }

            }

            $phone = $request->phone;

            if (! empty($phone)) {

                $isPhoneExists = $userService->findByColumnsWithValues(['phone', '=', $phone]);

                if (! empty($isPhoneExists)) {

                    flash(localize('An user already exists with this phone number.'))->error();

                    return back()->withInput();

                }

            }

            $data = $request->validated();

            $data['password'] = bcrypt($request->password);

            $data['company'] = $request->company;

            $data['referred_by'] = $userService->setReferredBy();

            $data['phone'] = validatePhone($request->phone);

            // Store User

            $user = $userService->storeUser($data);

            // Organization membership is settled at registration from the verified
            // email domain; hierarchy rank is set later, on interview confirmation.
            $organizationService->attachUser($user, $organizationService->resolveForUser($user));

            // Store User as subscriber

            ! empty($user->email) ? $userService->storeUserAsSubscriber(['email' => $user->email]) : false;

            // Make sure login

            $this->guard()->login($user);

            // When Registration verifications with settings is disabled means update the registered user as verified with current date time

            $registrationVerificationWith = getSetting('registration_verification_with');

            $isRegistrationSettingDisable = $registrationVerificationWith == appStatic()::REGISTRATION_WITH_DISABLE;

            if ($isRegistrationSettingDisable) {

                $userService->updateUserAsVerified($user);

                flash(localize('Registration successful.'))->success();

            }

            //  system notification

            saveNotification('New User Register', 'dashboard/customers', 'admin');

            // When registered not with registration with disable means send a verification mail to the user

            if (! $isRegistrationSettingDisable) {

                // OLD

                // $user->sendVerificationNotification();

                commonLog("Email Verification sending for User ID: {$request->user()->id}", []);

                // ponytail: email is best-effort — a mail/SMTP failure must never roll back a valid registration
                try {
                    EmailConfirmationJob::dispatchSync($request->user());
                } catch (\Throwable $mailError) {
                    Log::warning("Verification email failed for User ID {$user->id}: {$mailError->getMessage()}");
                }

                flash(localize('Registration successful. Please verify your email.'))->success();

            }

            $this->registered($request, $user) ?: redirect($this->redirectPath());

            DB::commit();

            return redirect()->route('writebot.dashboard');

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::info('Failed to registration & Incoming Payloads are '.json_encode($request->all()));

            flash($e->getMessage())->error();

            return back()->withInput();

        }

    }

    // action after registration

    protected function registered(Request $request, $user)
    {

        // subscription

        $starter = SubscriptionPackage::isActive()

            ->where('id', 1)

            ->first();

        if (! is_null($starter)) {

            $user->subscription_package_id = $starter->id;

            $user->save();

            $start_date = date('Y-m-d');

            $end_date = null;

            if ($starter->duration) {

                $end_date = date('Y-m-d', strtotime($start_date.$starter->duration.' days'));

            }

            $user->subscription_package_id = $starter->id;

            $user->save();

            $start_date = date('Y-m-d');

            $end_date = null;

            if ($starter->duration) {

                $end_date = date('Y-m-d', strtotime($start_date.$starter->duration.' days'));

            }

            // Subscription History Store

            (new UserService)->storeUserSubscription($starter, $user, $start_date, $end_date);

        }

        // send welcome email if enabled

        if (getSetting('welcome_email') == 1 && activeEmailTemplate('welcome-email') == true) {

            try {

                $user->registrationNotification();

            } catch (\Throwable $th) {

                // ponytail: welcome email is best-effort — don't fail registration if mail is down
                Log::warning("Welcome email failed for User ID {$user->id}: {$th->getMessage()}");

            }

        }

        // redirect

        if ($user->email_or_otp_verified == 0) {

            if (getSetting('registration_verification_with') == 'email' && activeEmailTemplate('registration-verification') == true) {

                return redirect()->route('verification.notice');

            } else {

                return redirect()->route('verification.phone');

            }

        } elseif (session('link') != null) {

            $link = session('link');

            session()->forget('link');

            return redirect($link);

        } else {

            return redirect()->route('writebot.dashboard');

        }

    }
}
