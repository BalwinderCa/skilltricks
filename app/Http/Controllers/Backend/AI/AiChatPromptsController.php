<?php

namespace App\Http\Controllers\Backend\AI;

use App\Http\Controllers\Controller;
use App\Models\AiChatPrompt;
use App\Models\AiChatPromptGroup;
use Illuminate\Http\Request;
use Str;
class AiChatPromptsController extends Controller
{
    public function __construct()
    {
        if (getSetting('enable_ai_chat') == '0') {
            flash(localize('AI chat propmt is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }

    # index
    public function index(Request $request)
    {
        $searchKey = null;
        $user = auth()->user();
        if ($user->user_type == "customer") {
                abort(403);
        } else {
            if (!auth()->user()->can('ai_chat')) {
                abort(403);
            }
        }
        $promptGroups       = AiChatPromptGroup::oldest();
        $promptGroups       = $promptGroups->get();
        $prompts            = AiChatPrompt::latest();

        if ($request->search != null) {
            $prompts = $prompts->where('title', 'like', '%' . $request->search . '%')->orWhere('prompt', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $prompts = $prompts->get();
        return view('backend.pages.aiChat.prompts.index', compact('promptGroups', 'prompts','searchKey'));
    }

    # return view of create form
    public function create()
    {
        if (isCustomer()) {
            abort(403);
        }
        $promptGroups       = AiChatPromptGroup::oldest();
        $promptGroups       = $promptGroups->get();
        return view('backend.pages.aiChat.prompts.create',compact('promptGroups'));
    }

    # store
    public function store(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $prompt                             = new AiChatPrompt;
        $prompt->title                      = $request->title;
        $prompt->ai_chat_prompt_group_id    = $request->ai_chat_prompt_group_id;
        $prompt->prompt                     = $request->prompt;
        $prompt->created_by                 = auth()->user()->id;
        $prompt->save();

        flash(localize('Prompt has been added successfully'))->success();
        return redirect()->route('chat.prompts');
    }

    # return view of edit form
    public function edit($id)
    {
        if (isCustomer()) {
            abort(403);
        }
        $promptGroups       = AiChatPromptGroup::oldest();
        $promptGroups       = $promptGroups->get();
        $prompt = AiChatPrompt::where('id', $id)->first();
        return view('backend.pages.aiChat.prompts.edit', compact('prompt','promptGroups'));
    }

    # update
    public function update(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $prompt                             = AiChatPrompt::find((int) $request->id);
        $prompt->title                      = $request->title;
        $prompt->ai_chat_prompt_group_id    = $request->ai_chat_prompt_group_id;
        $prompt->prompt                     = $request->prompt;
        $prompt->updated_by                 = auth()->user()->id;
        $prompt->save();
        flash(localize('Prompt has been updated successfully'))->success();
        return redirect()->route('chat.prompts');
    }

    # delete
    public function delete($id)
    {
        if (isCustomer()) {
            abort(403);
        }
        $prompt = AiChatPrompt::where('id', $id)->first();
        $prompt->delete();
        return redirect()->route('chat.prompts');
    }

    # storePromptGroup
    public function storePromptGroup(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $promptGroup                             = new AiChatPromptGroup;
        $promptGroup->name                       = $request->name;
        $promptGroup->slug                       = preg_replace('/\s+/', '-', trim($request->name)) . '-' . Str::random(5);
        $promptGroup->save();

        flash(localize('Prompt group has been added successfully'))->success();
        return back();
    }

    # return view of editPromptGroup form
    public function editPromptGroup(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $existingGroup = AiChatPromptGroup::where('id', $request->id)->first();
        return [
            'status'             => 200,
            'formData'      => view('backend.pages.aiChat.prompts.inc.group-form', compact('existingGroup'))->render()
        ];
    }

    # updatePromptGroup
    public function updatePromptGroup(Request $request)
    {
        if (isCustomer()) {
            abort(403);
        }
        $promptGroup                        = AiChatPromptGroup::find((int) $request->id);
        $promptGroup->name                  = $request->name;
        $promptGroup->slug                  = preg_replace('/\s+/', '-', trim($request->name)) . '-' . Str::random(5);
        $promptGroup->save();
        flash(localize('Prompt group has been updated successfully'))->success();
        return redirect()->route('chat.prompts');
    }

    # deletePromptGroup
    public function deletePromptGroup($id)
    {
        if (isCustomer()) {
            abort(403);
        }
        $promptGroup = AiChatPromptGroup::where('id', $id)->first();
        $prompts = AiChatPrompt::where('ai_chat_prompt_group_id', $promptGroup->id)->get();
        foreach ($prompts as $key => $prompt) {
            $prompt->delete();
        }
        $promptGroup->delete();
        return redirect()->route('chat.prompts');
    }
}
