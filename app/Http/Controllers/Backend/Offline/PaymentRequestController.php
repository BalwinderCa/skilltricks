<?php

namespace App\Http\Controllers\Backend\Offline;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\SubscriptionHistory;
use App\Http\Controllers\Controller;
use App\Models\OfflinePaymentMethod;
use App\Traits\SubscriptionHistoryTrait;

class PaymentRequestController extends Controller
{
    use SubscriptionHistoryTrait;
    # construct
    public function __construct()
    {
        $this->middleware(['permission:all_payment_request'])->only('index');
        $this->middleware(['permission:approve_payment_request'])->only(['approve']);
        $this->middleware(['permission:reject_payment_request'])->only(['reject']);
        $this->middleware(['permission:add_note_payment_request'])->only(['re-submit']);
    }

    //index all payment request
    public function index(Request $request)
    {
        $payment_requests = SubscriptionHistory::with('user', 'offlinePaymentMethod', 'subscriptionPackage')->where('payment_method', 'offline')->where('payment_status', '!=', 1)
            // ->when($request->search, function ($q) use ($request) {
            //     $q->where('title', 'like', '%' . $request->search . '%');
            // })
            ->when($request->status, function ($q) use ($request) {
                $q->where('payment_status', $request->status);
            })
            ->paginate(paginationNumber());
        $status = $request->status;
        return view('backend.pages.paymentRequest.index', compact('payment_requests', 'status'));
    }
    // view payment request 
    public function view($id)
    {
        $offlinePaymentMethods = OfflinePaymentMethod::where('is_active', 1)->get();
        $history = SubscriptionHistory::where('id', $id)->where('payment_method', 'offline')->first();
        if (!$history) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('subscriptions.histories.index');
        }
        $package = $history->subscriptionPackage;
        return view('backend.pages.paymentRequest.view', compact('history', 'package', 'offlinePaymentMethods'));
    }
    // approve offline payment request
    public function approve($id)
    {

        try {
            $history = SubscriptionHistory::findOrFail($id);
            $oldPackageHistory = activePackageHistory($history->user_id);
            $forcefully_active = $history->forcefully_active ? true : false;
            $this->changeStatus($id, 1);

            if (!$oldPackageHistory || $forcefully_active == true) {

                $this->paymentApprove($id);
            } else {

                $carry_forward = getSetting('carry_forward') && getSetting('carry_forward') == 1 ? true : false;

                if ($oldPackageHistory->subscriptionPackage->package_type == $history->subscriptionPackage->package_type) {
                    $carry_forward = true;
                }
                if ($carry_forward) {
                    $history->is_carried_over = 1;

                    if ($oldPackageHistory) {
                        $carriedWords = $oldPackageHistory->this_month_available_words;
                        $carriedImages = $oldPackageHistory->this_month_available_images;
                        $carriedS2t = $oldPackageHistory->this_month_available_s2t;
                    }

                    $history->carried_over_words                = $carriedWords;
                    $history->carried_over_images               = $carriedImages;
                    $history->carried_over_speech_to_text       = $carriedS2t;

                    $history->save();
                }
            }
            # save notification
            saveNotification('Admin Approved Payment', null, 'customer', $history->user_id, null, null, null);
            //  send mail
            $customer = User::where('id', $history->user_id)->first();
            if ($customer) {
                $data = [];
                $data['name'] = $customer->name;
                $data['email'] = $customer->email;
                $data['phone'] = $customer->phone;
                if ($history) {
                    $subscriptionHistory = $history;
                    if ($subscriptionHistory) {
                        $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter'
                            ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                        $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) . '/' . $package;
                        $data['price'] = $subscriptionHistory->price;
                        $data['start_date'] = $subscriptionHistory->start_date;
                        $data['end_date'] = $subscriptionHistory->end_date;
                        $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                    }
                }
            }
            if ($customer->email) {
                sendMail($customer->email,  $customer->name, 'offline-payment-request-approve', $data);
            }
            // end sendmail
            flash(localize('Status Change successfully'))->success();
            return redirect()->route('admin.payment-request.index');
        } catch (\Throwable $th) {

            flash(localize('Operation Failed'))->error();
            return redirect()->route('admin.payment-request.index');
        }
    }
    // reject offline payment request
    public function reject($id)
    {
        try {
            // $this->changeStatus($id, 3);
            $history = SubscriptionHistory::where('id', $id)
                ->when(isCustomer(), function ($q) {
                    $q->where('user_id', auth()->user()->id);
                })->where('payment_method', 'offline')->where('payment_status', '!=', 1)->first();

            # save notification
            saveNotification('Admin Rejected Package', null, 'customer', $history->user_id, null, null, null);
            //  send mail
            $customer = User::where('id', $history->user_id)->first();
            if ($customer) {
                $data = [];
                $data['name'] = $customer->name;
                $data['email'] = $customer->email;
                $data['phone'] = $customer->phone;
                if ($history) {
                    $subscriptionHistory = $history;
                    if ($subscriptionHistory) {
                        $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter'
                            ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                        $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) . '/' . $package;
                        $data['price'] = $subscriptionHistory->price;
                        $data['start_date'] = $subscriptionHistory->start_date;
                        $data['end_date'] = $subscriptionHistory->end_date;
                        $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                    }
                }
            }
            if ($customer->email) {
                sendMail($customer->email,  $customer->name, 'offline-payment-request-rejected', $data);
            }
            if ($history) {
                $history->delete();
            }
            // end sendmail
            flash(localize('Status Change successfully'))->success();
            return redirect()->route('admin.payment-request.index');
        } catch (\Throwable $th) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('admin.payment-request.index');
        }
    }
    // re-submit offline payment request
    public function reSubmit($id)
    {
        try {
            $this->changeStatus($id, 4);

            $history = SubscriptionHistory::findOrFail($id);
            $customer = User::where('id', $history->user_id)->first();
            //  send mail

            if ($customer) {
                $data = [];
                $data['name'] = $customer->name;
                $data['email'] = $customer->email;
                $data['phone'] = $customer->phone;
                if ($history) {
                    $subscriptionHistory = $history;
                    if ($subscriptionHistory) {
                        $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter'
                            ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                        $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) . '/' . $package;
                        $data['price'] = $subscriptionHistory->price;
                        $data['start_date'] = $subscriptionHistory->start_date;
                        $data['end_date'] = $subscriptionHistory->end_date;
                        $data['note'] = $subscriptionHistory->feedback_note;
                        $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                    }
                }
            }
            if ($customer->email) {
                sendMail($customer->email,  $customer->name, 'offline-payment-request-add-note', $data);
            }      
            // end sendmail
            flash(localize('Status Change successfully'))->success();
            return redirect()->route('admin.payment-request.index');
        } catch (\Throwable $th) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('admin.payment-request.index');
        }
    }
    // delete offline payment request
    public function delete($id)
    {

        try {
            $history = SubscriptionHistory::where('id', $id)->when(isCustomer(), function ($q) {
                $q->where('user_id', auth()->user()->id);
            })->where('payment_method', 'offline')->where('payment_status', '!=', 1)->first();
            $user_id = $history->user_id;
           
            # save notification
            saveNotification('Admin Rejected Payment', null, 'customer', $user_id, null, null, null);
            $customer = User::where('id', $history->user_id)->first();
            if ($customer) {
                $data = [];
                $data['name'] = $customer->name;
                $data['email'] = $customer->email;
                $data['phone'] = $customer->phone;
                if ($history) {
                    $subscriptionHistory = $history;
                    if ($subscriptionHistory) {
                        $package = $subscriptionHistory->subscriptionPackage->package_type == 'starter'
                            ? localize('Monthly') : localize($subscriptionHistory->subscriptionPackage->package_type);
                        $data['package'] = html_entity_decode($subscriptionHistory->subscriptionPackage->title) . '/' . $package;
                        $data['price'] = $subscriptionHistory->price;
                        $data['start_date'] = $subscriptionHistory->start_date;
                        $data['end_date'] = $subscriptionHistory->end_date;
                        $data['method'] = $subscriptionHistory->offline_payment_id ? $subscriptionHistory->offlinePaymentMethod->name : $subscriptionHistory->payment_method;
                    }
                }
            }
            if ($customer->email) {
                sendMail($customer->email,  $customer->name, 'offline-payment-request-rejected', $data);
            }
            if ($history) {
                $history->delete();
            }
            flash(localize('Payment Delete successfully'))->success();

            return redirect()->route('subscriptions.histories.index');
        } catch (\Throwable $th) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('subscriptions.histories.index');
        }
    }
    private function changeStatus($history_id, $payment_status)
    {
        //  1== paid, 2=pending, 3= reject, 4=incomplete
        $history = SubscriptionHistory::where('id', $history_id)->when(isCustomer(), function ($q) {
            $q->where('user_id', auth()->user()->id);
        })->where('payment_method', 'offline')->where('payment_status', '!=', 1)->first();
        if ($history) {
            $history->update(['payment_status' => $payment_status]);
        }
        if ($payment_status == 4) {
            # save notification
            saveNotification('Incomplete Payment Request', null, 'customer', $history->user_id, null, null, null);
        }
    }
    public function feedbackNote(Request $request)
    {
        try {
            $history_id = $request->history_id;
            $history = SubscriptionHistory::where('id', $history_id)
                ->when(isCustomer(), function ($q) {
                    $q->where('user_id', auth()->user()->id);
                })->where('payment_method', 'offline')->where('payment_status', '!=', 1)->first();
            if ($history) {
                $history->feedback_note = $request->note;
                $history->payment_status = 4;
                $history->save();
            }
            $msg = localize('Incomplete Payment Request');
            # save notification
            saveNotification($msg, 'dashboard/re-submit-request/' . $history_id, 'customer', $history->user_id, null, null, null);

            flash(localize('Note Added successfully'))->success();
            return redirect()->route('admin.payment-request.index');
        } catch (\Throwable $th) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('admin.payment-request.index');
        }
    }
}
