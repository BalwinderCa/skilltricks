<?php

namespace App\Http\Controllers\Backend\Offline;

use Illuminate\Http\Request;
use App\Models\SubscriptionHistory;
use App\Http\Controllers\Controller;
use App\Http\Requests\OfflinePaymentRequestForm;
use App\Models\OfflinePaymentMethod;

class CustomerReSubmitRequestController extends Controller
{
    //
    public function index($id)
    {
        $offlinePaymentMethods = OfflinePaymentMethod::where('is_active', 1)->get();
        $history = SubscriptionHistory::where('id', $id)->when(isCustomer(), function ($q) {
            $q->where('user_id', auth()->user()->id);
        })->where('payment_method', 'offline')->where('payment_status', 4)->whereNotNull('feedback_note')->first();
        if (!$history) {
            flash(localize('Operation Failed'))->error();
            return redirect()->route('subscriptions.histories.index');
        }
        $package = $history->subscriptionPackage;
        return view('backend.pages.paymentRequest.edit', compact('history', 'package', 'offlinePaymentMethods'));
    }
    public function store(OfflinePaymentRequestForm $request)
    {

        try {
            $history = SubscriptionHistory::where('id', $request->history_id)->when(isCustomer(), function ($q) {
                $q->where('user_id', auth()->user()->id);
            })->where('payment_method', 'offline')->where('payment_status', 4)->whereNotNull('feedback_note')->first();
            if ($history) {
                $path =  'public/uploads/offlinePayment/';
                $file = $request->file;

                // $history->price = $request->amount;
                $history->payment_status = 2;
                $history->note = $request->note;
                if ($file) {
                    $history->file = fileUpdate($history->file, $path, $file);
                }
                $history->offline_payment_id = $request->offline_payment_method;

                $history->payment_details = !is_null($request->payment_detail) ? json_encode($request->payment_detail) : null;
                $history->save();
            }
            $msg = localize('Added Feedback Note');
            # save notification
            saveNotification($msg, 'dashboard/payment-request/view/'. $history->id, 'admin', null, null, null, null);
            flash(localize(' successfully'))->success();
            return redirect()->route('subscriptions.histories.index');
        } catch (\Throwable $th) {

            flash(localize('Operation Failed'))->error();
            return redirect()->route('subscriptions.histories.index');
        }
    }
}
