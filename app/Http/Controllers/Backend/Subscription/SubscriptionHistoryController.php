<?php

namespace App\Http\Controllers\Backend\Subscription;

use PDF;
use App\Models\Language;
use Illuminate\Http\Request;
use App\Models\SubscriptionHistory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;

class SubscriptionHistoryController extends Controller
{
    public function index()
    {
    }
    public function invoice($history_id)
    {
        try {
            $history = SubscriptionHistory::where('id', $history_id)->first();
            return view('backend.pages.subscriptions.inc.invoice', compact('history'));
        } catch (\Throwable $th) {
            dd($th->getMessage());
            //throw $th;
        }
    }
    # download invoice
    public function downloadInvoice($history_id)
    {
        if (session()->has('locale')) {
            $language_code = session()->get('locale', Config::get('app.locale'));
        } else {
            $language_code = env('DEFAULT_LANGUAGE');
        }

        if (session()->has('currency_code')) {
            $currency_code = session()->get('currency_code', Config::get('app.currency_code'));
        } else {
            $currency_code = env('DEFAULT_CURRENCY');
        }

        if (Language::where('code', $language_code)->first()->is_rtl == 1) {
            $direction = 'rtl';
            $default_text_align = 'right';
            $reverse_text_align = 'left';
        } else {
            $direction = 'ltr';
            $default_text_align = 'left';
            $reverse_text_align = 'right';
        }

        $currency_code = env('INVOICE_LANG');

        $font_family = env('INVOICE_FONT');

        if ($currency_code == 'BDT' || $currency_code == 'bdt' || $language_code == 'bd' || $language_code == 'bn') {
            # bengali font
            $font_family = "'Hind Siliguri','sans-serif'";
        } elseif ($currency_code == 'KHR' || $language_code == 'kh') {
            # khmer font
            $font_family = "'Khmeros','sans-serif'";
        } elseif ($currency_code == 'AMD') {
            # Armenia font
            $font_family = "'arnamu','sans-serif'";
        } elseif ($currency_code == 'AED' || $currency_code == 'EGP' || $language_code == 'sa' || $currency_code == 'IQD' || $language_code == 'ir') {
            # middle east/arabic font
            $font_family = "'XBRiyaz','sans-serif'";
        } else {
            # general for all
            $font_family = "'Roboto','sans-serif'";
        }

        $history = SubscriptionHistory::where('id', $history_id)->first();

        return PDF::loadView('backend.pages.subscriptions.inc.download-invoice', [
            'history' => $history,
            'font_family' => $font_family,
            'direction' => $direction,
            'default_text_align' => $default_text_align,
            'reverse_text_align' => $reverse_text_align
        ], [], [])->download($history->id . $history->start_date . '.pdf');
    }
}
