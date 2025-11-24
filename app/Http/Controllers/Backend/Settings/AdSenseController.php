<?php

namespace App\Http\Controllers\Backend\Settings;

use App\Models\AdSense;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdSenseRequestForm;

class AdSenseController extends Controller
{
    //
    public function index()
    {
        $ads = AdSense::when(getSetting('dashboard_adSense'), function ($q) {
            $q->where('is_dashboard', 1);
        })->paginate(paginationNumber());
        return view('backend.pages.systemSettings.ads.index', compact('ads'));
    }
    public function edit($id)
    {
        $ads = AdSense::where('id', $id)->first();
        return view('backend.pages.systemSettings.ads.edit-adsense', compact('ads'));
    }
    public function update(AdSenseRequestForm $request)
    {
        try {
            $ads = AdSense::where('slug', $request->slug)->where('id', $request->id)->first();
            if ($ads) {
                $ads->code = $request->code;
                $ads->is_active = $request->status == 1 ? true : false;
                $ads->save();
            }
            cacheClear();
            flash(localize('Updated Successfully'))->success();
            return redirect()->route('admin.settings.adsense');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->back();
        }
    }
}
