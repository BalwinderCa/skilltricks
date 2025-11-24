<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;

class ConfigurationsController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:affiliate_configurations'])->only('index');
    }

    # affiliate configurations
    public function index()
    {
        return view('backend.pages.affiliate.configurations');
    }
}
