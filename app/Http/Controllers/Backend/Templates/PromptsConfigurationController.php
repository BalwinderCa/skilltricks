<?php

namespace App\Http\Controllers\Backend\Templates;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Localization;
use Illuminate\Http\Request;

class PromptsConfigurationController extends Controller
{
    # view of update prompts localization
    public function index()
    {
        if (!auth()->user()->can('prompts')) {
            abort(403);
        }
        $languages = Language::where('id', '!=', 1)->isActive()->oldest()->get();
        return view('backend.pages.templates.prompts', [
            'languages' => $languages
        ]);
    }

    # show prompt update modal
    public function show(Request $request)
    {
        $langKey = $request->lang_key;
        $string_key = $request->prompt;
        $t_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($request->prompt)));

        $language = Language::where('code', $langKey)->first();
        $prompt =   Localization::where('lang_key', $langKey)->where('t_key', $t_key)->first();

        if (empty($prompt)) {
            newLocalization($langKey, $t_key, $request->prompt);
            $prompt =   Localization::where('lang_key', $langKey)->where('t_key', $t_key)->first();
        }

        return [
            'status' => 200,
            'success' => true,
            'prompt'   => view('backend.pages.templates.inc.promptsModalContent', compact('prompt', 'string_key', 'language'))->render()
        ];
    }

    # update prompt
    public function store(Request $request)
    {
        $prompt =   Localization::where('id', $request->id)->first();
        $prompt->t_value = $request->prompt_value;
        $prompt->save();
    }
}
