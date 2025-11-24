<?php

namespace App\Http\Controllers\Backend\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerSettingsController extends Controller
{
    public function index()
    {
        return view('backend.pages.customers.customer_settings');
    }
    public function store()
    {
    }
}
