<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEarning;
use Illuminate\Http\Request;

class EarningHistoriesController extends Controller
{
    # get earning histories
    public function index(Request $request)
    {
        $user = auth()->user();
        $earningHistories = AffiliateEarning::query();
        if ($user->user_type == "customer") {
            $earningHistories = $earningHistories->where('referred_by', $user->id)->latest();
        } else {
            if (!auth()->user()->can('affiliate_earning_histories')) {
                abort(403);
            }
            $earningHistories = $earningHistories->latest();
        }
        $earningHistories = $earningHistories->paginate(paginationNumber());
        return view('backend.pages.affiliate.earnings', compact('earningHistories'));
    }
}
