<?php

namespace App\View\Components;

use App\Models\WrNotification;
use Illuminate\View\Component;

class NavbarNotification extends Component
{

    public function render()
    {
        $user = auth()->user();

        $countMesage = WrNotification::where('is_read', '!=', 1)
        ->when(isAdmin(), function($q){
            $q->where('user_role', 'admin');
        })->when(isCustomer(), function($q) use($user){
            $q->where('user_role', 'customer')->where('user_id', $user->id);
        })->count();
       
        $notifications = WrNotification::where('is_read', 0)
        ->when(isAdmin(), function($q){
            $q->where('user_role', 'admin');
        })->when(isCustomer(), function($q) use($user){
            $q->where('user_role', 'customer')->where('user_id', $user->id);
        })->orderBy('id', 'DESC')->limit(5)->get();
 
        return view('components.navbar-notification', compact('notifications', 'countMesage'));
    }
}
