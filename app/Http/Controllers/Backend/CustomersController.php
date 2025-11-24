<?php

namespace App\Http\Controllers\Backend;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Exports\CustomersExport;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;
use App\Models\OfflinePaymentMethod;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\SubscriptionHistoryTrait;
use App\Http\Requests\CustomerRequestForm;

class CustomersController extends Controller
{

    use SubscriptionHistoryTrait;
    # construct
    public function __construct()
    {
        $this->middleware(['permission:customers'])->only('index');
        // $this->middleware(['permission:add_customers'])->only(['create', 'store']);
        $this->middleware(['permission:ban_customers'])->only(['updateBanStatus']);
    }

    # customer list
    public function index(Request $request)
    {
        $searchKey = null;
        $is_banned = null;
        $package_id   = $request->package_id;
        $user_ids  = $package_id 
            ? SubscriptionHistory::where('subscription_status', 1)->where('subscription_package_id', $package_id)->pluck('user_id')->toArray() : [];
        
        $customers = User::with('currentPackage')->where('user_type', 'customer')
        ->when($package_id, function($q) use($user_ids){
            $q->whereIn('id', $user_ids);
        })->latest();
        if ($request->search != null) {
            $customers = $customers->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        if ($request->is_banned != null) {
            $customers = $customers->where('is_banned', $request->is_banned);
            $is_banned    = $request->is_banned;
        }

        $customers = $customers->paginate(paginationNumber());
        $packages = SubscriptionPackage::where('is_active', 1)->get(['title', 'id', 'package_type']);
        return view('backend.pages.customers.index', compact('customers', 'searchKey', 'is_banned', 'packages', 'package_id'));
    }

    #create customer
    public function create()
    {
        $packages = SubscriptionPackage::where('is_active', 1)->get(['id', 'title', 'package_type', 'price', 'discount', 'discount_status', 'discount_price']); 
        
        $payment_methods = OfflinePaymentMethod::where('is_active', 1)->get();
        return view('backend.pages.customers.create', compact('packages', 'payment_methods'));
    }

    #store customer
    public function store(CustomerRequestForm $request)
    {
        DB::beginTransaction();
        try {
            $customer = new User();
            $customer->user_type = 'customer';
            $customer->name = $request->name;
            $customer->phone = validatePhone($request->phone);
            $customer->email = $request->email;
            $customer->password = Hash::make($request->password);
            $customer->created_by = auth()->user()->id;
            $customer->email_or_otp_verified = 1;
            $customer->email_verified_at = Carbon::now();
            $customer->save();
            if ($request->package) {
                $request = $request->merge([
                    'package_id' => $request->package,
                    'user_id' => $customer->id,
                    'amount' => $request->paid_amount,
                    'admin_customer' => true,
                ]);

                if ($request->payment_method == "offline") {
                    $request = $request->merge([
                        'offline_payment_method' => $request->offline_payment_method_id
                    ]);
                }

                $history_id = $this->subscriptionHistoryStore($request);
                $this->paymentApprove($history_id);
            }
            DB::commit();
            // send mail
            if($customer){
                $data = [];
                $data['name'] = $customer->name;
                $data['email'] = $customer->email;
                $data['phone'] = $customer->phone;
                $data['password'] = $request->password;
                if($history_id) {
                    $subscriptionHistory = SubscriptionHistory::where('subscription_status', 1)->where('id', $history_id)->first();
                    if($subscriptionHistory) {
                        $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter' 
                        ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                        $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) .'/'.$package;
                        $data['price'] = $subscriptionHistory->price;
                        $data['start_date'] = $subscriptionHistory->start_date;
                        $data['end_date'] = $subscriptionHistory->end_date;
                        $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                    }
                }
            }
            if($customer->email) {
                sendMail($customer->email,  $customer->name, 'add-new-customer-welcome-email', $data);
            }
            // end send mail
            flash(localize('Customer has been created successfully'))->success();
            return redirect()->route('admin.customers.index');
        } catch (\Throwable $th) {
      
            DB::rollback();
       
            flash(localize('Customer Created Failed'))->error();
            return redirect()->route('admin.customers.index');
        }
    }

    # update status 
    public function updateBanStatus(Request $request)
    {
        $user = User::findOrFail($request->id);
        $user->is_banned = $request->status;
        if ($user->save()) {
            return 1;
        }
        return 0;
    }

    # delete customer  
    public function delete($id)
    {
        try {
            User::find($id)->delete();
        } catch (\Throwable $th) {
            abort(404);
        }
        flash(localize('Customer has been deleted successfully'))->success();
        return redirect()->route('admin.customers.index');
    }


    # edit customer
    public function edit($id)
    {
        $user  = User::findOrFail($id);
        return view('backend.pages.customers.edit', compact('user'));
    }

    # update customer 
    public function update(Request $request)
    {
        $exit_email = User::where('email', $request->email)->where('id', '!=', $request->id)->first();
        if ($exit_email) {
            flash(localize('This Email address already exit'))->warning();
            return redirect()->back();
        }
        $user             = User::findOrFail($request->id);
        $user->name       = $request->name;
        $user->email      = $request->email;
        $user->phone      = validatePhone($request->phone);

        if (strlen($request->password) > 0) {
            $user->password = Hash::make($request->password);
        }
        $user->updated_by        = auth()->user()->id;
        $user->save();

        flash(localize('Customer has been updated successfully'))->success();
        return redirect()->route('admin.customers.index');
    }

    #assignPackage customer
    public function assignPackage($id)
    {
        $user  = User::findOrFail($id);
        $packages = SubscriptionPackage::where('is_active', 1)->where('package_type', '!=', "starter")->get(['id', 'title', 'package_type', 'price']);
        $payment_methods = OfflinePaymentMethod::where('is_active', 1)->get();
        return view('backend.pages.customers.assign', compact('packages', 'payment_methods', 'user'));
    }

    #assignPackageStore customer
    public function assignPackageStore(Request $request)
    {
        if ($this->limitPackagePurchase($request->id) == false) {
            flash(localize("Operation Failed. User Can Not Have More Than 2 packages"))->warning();
            return back();
        }

        DB::beginTransaction();
        try {
            $customer = User::findOrFail((int) $request->id);
            if ($request->package) {
                $request = $request->merge([
                    'package_id' => $request->package,
                    'user_id' => $customer->id,
                    'amount' => $request->paid_amount,
                    'admin_customer' => true,
                ]);

                if ($request->payment_method == "offline") {
                    $request = $request->merge([
                        'offline_payment_method' => $request->offline_payment_method_id
                    ]);
                }
                $history_id = $this->subscriptionHistoryStore($request);
                if(!$history_id){
                    flash(localize('Package Purchased , wait for payment Aprroval'))->warning();
                    return redirect()->route('admin.customers.index');
                }
                $carry = false;
                if ($request->carry_forward && $request->carry_forward == "on") {
                    $carry = true;
                }

                $active_now = false;
                if ($request->active_now && $request->active_now == "on") {
                    $active_now = true;
                }
                $this->paymentApprove($history_id, $carry, $active_now);
                $msg = localize('Admin Assign Package For You');
                # save notification
                saveNotification($msg, 'dashboard/subscriptions', 'customer',$customer->id, null, null, null);
                if($customer){
                    $data = [];
                    $data['name'] = $customer->name;
                    $data['email'] = $customer->email;
                    $data['phone'] = $customer->phone;
                    if($history_id) {
                        $subscriptionHistory = SubscriptionHistory::where('id', $history_id)->first();
                        if($subscriptionHistory) {
                            $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter' 
                            ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                            $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) .'/'.$package;
                            $data['price'] = $subscriptionHistory->price;
                            $data['start_date'] = $subscriptionHistory->start_date;
                            $data['end_date'] = $subscriptionHistory->end_date;
                            $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                        }
                    }
                }
                if($customer->email) {
                    sendMail($customer->email,  $customer->name, 'admin-assign-package', $data);
                }
            }
            DB::commit();

            flash(localize('Package has been assigned successfully'))->success();
            return redirect()->route('admin.customers.index');
        } catch (\Throwable $th) {
            DB::rollback();
            flash(localize('Package assigned Failed'))->error();
            return redirect()->route('admin.customers.index');
        }
    }
    // active package
    public function activePackage(Request $request)
    {
        try {
            $history_id = $request->package_history_id;
            $user = auth()->user();
            if ($user->user_type != 'customer') {
                $history = SubscriptionHistory::where('id', $history_id)->first();
            } else {
                $history = SubscriptionHistory::where('user_id', auth()->user()->id)->where('id', $history_id)->first();
            }
            
            if ($history->payment_status != 1) {
                flash(localize('Operation Failed'))->error();
                return redirect()->route('subscriptions.histories.index');
            }
            $activePackage  = activePackageHistory($history->user_id);
            if (!$activePackage || !$history) {
                flash(localize('Operation Failed'))->error();
                return redirect()->route('subscriptions.histories.index');
            }

            SubscriptionHistory::where('user_id', $history->user_id)->where('subscription_package_id', '!=', $history->subscription_package_id)->where('subscription_status', 1)->update(['subscription_status' => 2]);
            // start date and end date
            $today = date('Y-m-d');
            $end_date = null;
            if ($history->subscriptionPackage->package_type == 'monthly') {
                $end_date = date('Y-m-d', strtotime($today . ' + 1 months'));
            } elseif ($history->subscriptionPackage->package_type == 'yearly') {
                $end_date = date('Y-m-d', strtotime($today . ' + 1 years'));
            }

            $carriedWords = 0;
            $carriedImages = 0;
            $carriedS2t = 0;

            if ($history->is_carried_over == 1 && $history->subscriptionPackage->package_type == $activePackage->subscriptionPackage->package_type) {
                $carriedWords = $activePackage->this_month_available_words;
                $carriedImages = $activePackage->this_month_available_images;
                $carriedS2t = $activePackage->this_month_available_s2t;
            }

            $activePackage->subscription_status = 2;
            $activePackage->end_date = date('Y-m-d');
            $activePackage->save();

            $history->subscription_status = 1;
            $history->start_date = date('Y-m-d', strtotime($today));
            $history->end_date = $end_date;

            $history->this_month_available_words   = (int) $history->new_word_balance + $carriedWords;
            $history->this_month_available_images  = (int) $history->new_image_balance + $carriedImages;
            $history->this_month_available_s2t     = (int) $history->new_s2t_balance + $carriedS2t;


            $history->carried_over_words                = $carriedWords;
            $history->carried_over_images               = $carriedImages;
            $history->carried_over_speech_to_text       = $carriedS2t;


            $history->active_by = $user->id;
            $history->save();

            flash(localize('Package Active successfully'))->success();
            return redirect()->route('subscriptions.histories.index');
        } catch (\Throwable $th) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('subscriptions.histories.index');
        }
    }
    #export customer
    public function exports()
    {
        return Excel::download(new CustomersExport, 'customers.xlsx');
    }
}
