<?php

namespace App\Http\Controllers\Backend\AI;

use App\Http\Controllers\Controller;
use App\Models\AiChatCategory;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Str;

class AiChatExpertController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_chat') == '0') {
            flash(localize('AI chat is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # experts index
    public function index(Request $request)
    {
        $searchKey = null;
        $user = auth()->user();
        if ($user->user_type == "customer") {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_chat == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_chat')) {
                abort(403);
            }
        }
        $chatExperts = AiChatCategory::oldest();

        if ($request->search != null) {
            $chatExperts = $chatExperts->where(function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')->orWhere('description', 'like', '%' . $request->search . '%')->orWhere('role', 'like', '%' . $request->search . '%');
            });

            $searchKey = $request->search;
        }

        if ($user->user_type != "customer") {
            $chatExperts     = $chatExperts->get();
        } else {
            $chatExperts     = $chatExperts->where('is_active', 1)->get();
        }
        return view('backend.pages.aiChat.experts', compact('chatExperts', 'searchKey'));
    }

    # return view of create form
    public function create()
    {
        if (isCustomer()) {
            abort(403);
        }
        return view('backend.pages.aiChat.experts.create');
    }

    # store
    public function store(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $expert                         = new AiChatCategory;
        $expert->name                   = $request->name;
        $expert->short_name             = $request->short_name;
        $expert->slug                   = preg_replace('/\s+/', '-', trim($request->name));
        $expert->description            = $request->description;
        $expert->role                   = $request->role;
        $expert->assists_with           = $request->assists_with;
        $expert->chat_training_data     = $request->chat_training_data ? json_encode($request->chat_training_data): null;
        $expert->avatar                 = $request->image;
        $expert->save();
        flash(localize('Expert has been added successfully'))->success();
        return redirect()->route('chat.experts');
    }

    # return view of edit form
    public function edit($slug)
    {
        if (isCustomer()) {
            abort(403);
        }

        $expert = AiChatCategory::where('slug', $slug)->first();
        return view('backend.pages.aiChat.experts.edit', compact('expert'));
    }


    # update
    public function update(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $expert                 = AiChatCategory::find((int) $request->id);
        $expert->name           = $request->name;
        $expert->short_name     = $request->short_name;
        $expert->slug           = preg_replace('/\s+/', '-', trim($request->name));
        $expert->description    = $request->description;
        $expert->role           = $request->role;
        $expert->assists_with   = $request->assists_with;
        $expert->chat_training_data     = $request->chat_training_data ? json_encode($request->chat_training_data): null;

        if ($request->image) {
            $expert->avatar         = $request->image;
        }
        $expert->save();
        flash(localize('Expert has been updated successfully'))->success();
        return redirect()->route('chat.experts');
    }

    # updateStatus
    public function updateStatus(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $expert = AiChatCategory::where('id', $request->id)->first();
        $expert->is_active = !$expert->is_active;
        $expert->save();
    }
}
