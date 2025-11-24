<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePayment;
use Illuminate\Http\Request;

class AffiliatePaymentsController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $paymentHistories = AffiliatePayment::where('status', '!=', 'requested');
        if ($user->user_type == "customer") {
            $paymentHistories = $paymentHistories->where('user_id', $user->id)->latest();
        } else {
            if (!auth()->user()->can('affiliate_payment_histories')) {
                abort(403);
            }
            $paymentHistories = $paymentHistories->latest();
        }
        $paymentHistories = $paymentHistories->paginate(paginationNumber());
        return view('backend.pages.affiliate.paymentHistories', compact('paymentHistories'));
    }
}
