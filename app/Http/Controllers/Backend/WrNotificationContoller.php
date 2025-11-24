<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WrNotification;
use Illuminate\Http\Request;

class WrNotificationContoller extends Controller
{
    //
    public function index(Request $request)
    {
        $user = auth()->user();
        $notifications =   WrNotification::when(isAdmin(), function($q){
            $q->where('user_role', 'admin');
        })->when(isCustomer(), function($q) use($user){
            $q->where('user_role', 'customer')->where('user_id', $user->id);
        })->orderBy('id', 'DESC')->paginate(paginationNumber());
        $this->readNotification($request);
        if($request->id){
            $url = WrNotification::where('id', $request->id)->value('url');
            if($url){
                return redirect('/'.$url);
            }
        }
        return view('backend.pages.notification.index',compact('notifications'));
    }
    public function readNotification($request)
    {
        $user = auth()->user();
        if($request->id && $request->is_read) {
            WrNotification::where('id', $request->id)->when(isAdmin(), function($q){
                $q->where('user_role', 'admin');
            })->when(isCustomer(), function($q) use($user){
                $q->where('user_role', 'customer')->where('user_id', $user->id);
            })->update(['is_read'=> $request->is_read]);
        }
       
    }
    public function delete(Request $request)
    {
        $user = auth()->user();
        $notification = WrNotification::where('id', $request->id)->when(isAdmin(), function($q){
            $q->where('user_role', 'admin');
        })->when(isCustomer(), function($q) use($user){
            $q->where('user_role', 'customer')->where('user_id', $user->id);
        })->first();
        if($notification){
            $notification->delete();
            flash(localize('Notification deleted successfully'))->success();
        }else{
            flash(localize('Notification Not Found'))->warning();
        }
        
        return redirect()->route('admin.notifications.index');
    }
    public function deleteAll()
    {
        $user = auth()->user();
        $notification = WrNotification::when(isAdmin(), function($q){
            $q->where('user_role', 'admin');
        })->when(isCustomer(), function($q) use($user){
            $q->where('user_role', 'customer')->orWhere('user_id', $user->id);
        })->delete();
  
        flash(localize('Notification deleted successfully'))->success();
        return redirect()->route('admin.notifications.index');
    }
    public function readAll()
    {
        WrNotification::when(isAdmin(), function($q){
            $q->where('user_role', 'admin');
        })->when(isCustomer(), function($q) {
            $q->where('user_role', 'customer')->where('user_id', userId());
        })->update(['is_read'=>1]);

        flash(localize('All Notification Read Successfully'))->success();
        return redirect()->route('admin.notifications.index');
    }
}
