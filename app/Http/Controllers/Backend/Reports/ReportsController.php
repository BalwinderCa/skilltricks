<?php

namespace App\Http\Controllers\Backend\Reports;

use App\Models\User;
use App\Models\Project;
use App\Models\Template;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TemplateUsage;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionPackage;
use App\Http\Controllers\Controller;


class ReportsController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:words_report'])->only('words');
        $this->middleware(['permission:codes_report'])->only('codes');
        $this->middleware(['permission:images_report'])->only('images');
        $this->middleware(['permission:s2t_report'])->only('s2t');
        $this->middleware(['permission:most_used_templates'])->only('mostUsed');
        $this->middleware(['permission:subscriptions_reports'])->only('subscriptions');
    }

    # words reports 
    public function words(Request $request)
    {
        try {
            $usage = TemplateUsage::latest()->whereNull('custom_template_id')
            ->when($request->user_id, function($q) use($request){
                $q->where('user_id', $request->user_id);
            })->when($request->template_id, function($q) use($request){
                $q->where('template_id', $request->template_id);
            });

            # conditional   
            if (Str::contains($request->date_range, 'to') && $request->date_range != null) {
                $date_var = explode(" to ", $request->date_range);
            } else {
                $date_var = [date("d-m-Y", strtotime('7 days ago')), date("d-m-Y", strtotime('today'))];
            }
            
            $usage = $usage->where('created_at', '>=', date("Y-m-d", strtotime($date_var[0])))->where('created_at', '<=',  date("Y-m-d", strtotime($date_var[1]) + 86400000));
    
            $totalWordsGenerated = $usage->sum('total_used_words');
            $usage = $usage->paginate(paginationNumber());
            $users = $this->users();
            $user_id = $request->user_id;
            $template_id = $request->template_id;
            $templates =  Template::all();
            return view('backend.pages.reports.words', compact('usage', 'date_var', 'totalWordsGenerated', 'users', 'user_id', 'template_id', 'templates'));
        } catch (\Throwable $th) {
            throw $th;
        }

    }

    # codes reports 
    public function codes(Request $request)
    {

        $usage = Project::latest()->where('content_type', 'code')->when($request->user_id, function($q) use($request){
            $q->where('user_id', $request->user_id);
        });

        # conditional   
        if (Str::contains($request->date_range, 'to') && $request->date_range != null) {
            $date_var = explode(" to ", $request->date_range);
        } else {
            $date_var = [date("d-m-Y", strtotime('7 days ago')), date("d-m-Y", strtotime('today'))];
        }

        $usage = $usage->where('created_at', '>=', date("Y-m-d", strtotime($date_var[0])))->where('created_at', '<=',  date("Y-m-d", strtotime($date_var[1]) + 86400000));

        $totalWordsGenerated = $usage->count();
        $usage = $usage->paginate(paginationNumber());
        $user_id = $request->user_id;
        $users = $this->users();
        return view('backend.pages.reports.codes', compact('usage', 'date_var', 'totalWordsGenerated', 'user_id', 'users'));
    }

    # images reports 
    public function images(Request $request)
    {
        $usage = Project::latest()->where('content_type', 'image')->when($request->user_id, function($q) use($request){
            $q->where('user_id', $request->user_id);
        })->when($request->engine, function($q) use($request){
            $q->where('engine', $request->engine);
        });

        # conditional   
        if (Str::contains($request->date_range, 'to') && $request->date_range != null) {
            $date_var = explode(" to ", $request->date_range);
        } else {
            $date_var = [date("d-m-Y", strtotime('7 days ago')), date("d-m-Y", strtotime('today'))];
        }

        $usage = $usage->where('created_at', '>=', date("Y-m-d", strtotime($date_var[0])))->where('created_at', '<=',  date("Y-m-d", strtotime($date_var[1]) + 86400000));

        $totalWordsGenerated = $usage->count();
        $usage = $usage->paginate(paginationNumber());
        $users = $this->users();
        $user_id = $request->user_id;
        $engine = $request->engine;
        $engines = ['OpenAI	', 'SD'];
        return view('backend.pages.reports.images', compact('usage', 'date_var', 'totalWordsGenerated', 'users', 'user_id', 'engine'));
    }

    # s2t reports 
    public function s2t(Request $request)
    {
        $usage = Project::latest()->where('content_type', 'speech_to_text')->when($request->user_id, function($q) use($request){
            $q->where('user_id', $request->user_id);
        });

        # conditional   
        if (Str::contains($request->date_range, 'to') && $request->date_range != null) {
            $date_var = explode(" to ", $request->date_range);
        } else {
            $date_var = [date("d-m-Y", strtotime('7 days ago')), date("d-m-Y", strtotime('today'))];
        }

        $usage = $usage->where('created_at', '>=', date("Y-m-d", strtotime($date_var[0])))->where('created_at', '<=',  date("Y-m-d", strtotime($date_var[1]) + 86400000));

        $totalWordsGenerated = $usage->count();
        $usage = $usage->paginate(paginationNumber());
        $users = $this->users();
        $user_id = $request->user_id;
    
        return view('backend.pages.reports.s2t', compact('usage', 'date_var', 'totalWordsGenerated',  'users', 'user_id'));
    }

    # most used templates reports 
    public function mostUsed(Request $request)
    {
        $searchKey  = null;
        $order = 'DESC';

        if ($request->order == "ASC") {
            $order = 'ASC';
        }

        $usage = Template::orderBy('total_words_generated', $order);

        if ($request->search != null) {
            $usage      = $usage->where('name', 'like', '%' . $request->search . '%');
            $searchKey  = $request->search;
        }

        $totalWordsGenerated = $usage->count();
        $usage = $usage->paginate(paginationNumber(30));
        return view('backend.pages.reports.mostUsedTemplates', compact('usage', 'order', 'searchKey'));
    }

    # subscriptions reports 
    public function subscriptions(Request $request)
    {

        $searchKey = null;
        $histories = SubscriptionHistory::latest()->when($request->user_id, function($q) use($request){
            $q->where('user_id', $request->user_id);
        })->when($request->package_id, function($q) use($request){
            $q->where('subscription_package_id', $request->package_id);
        });

        if ($request->search != null) {
            $userIds = User::where('name', 'like', '%' . $request->search . '%')->pluck('id');
            $histories = $histories->whereIn('user_id', $userIds);
            $searchKey = $request->search;
        }

        # conditional   
        if (Str::contains($request->date_range, 'to') && $request->date_range != null) {
            $date_var = explode(" to ", $request->date_range);
        } else {
            $date_var = [date("d-m-Y", strtotime('7 days ago')), date("d-m-Y", strtotime('today'))];
        }

        $histories = $histories->where('created_at', '>=', date("Y-m-d", strtotime($date_var[0])))->where('created_at', '<=',  date("Y-m-d", strtotime($date_var[1]) + 86400000));

        $totalPrice = $histories->sum('price');

        $histories = $histories->paginate(paginationNumber());

        $users = $this->users();
        $user_id = $request->user_id;
        $package_id = $request->package_id;
        $packages = SubscriptionPackage::get(['id', 'title']);

        return view('backend.pages.reports.subscriptions', compact('histories', 'searchKey', 'date_var', 'totalPrice', 'users', 'user_id', 'packages', 'package_id'));
    }
    # users 
    public function users()
    {
        return User::get(['id', 'name']);
    }
}
