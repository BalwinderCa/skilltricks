<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Str;

class AffiliateOverviewController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if ($user->referral_code == null) {
            $user->referral_code = substr(auth()->user()->id . Str::random(10), 0, 10);
            $user->save();
        }
        return view('backend.pages.affiliate.overview', compact('user'));
    }
}
