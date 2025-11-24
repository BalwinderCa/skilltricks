<?php

namespace App\Http\Controllers\Backend\Settings;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplateStoreRequestForm;
use League\CommonMark\Normalizer\SlugNormalizer;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::all();
        return view('backend.pages.systemSettings.emailTemplate.index', compact('templates'));
    }
    public function update(Request $request)
    {
        try {
            $id = $request->id;
            if (!empty($id)) {

                $template = EmailTemplate::where('id', $id)->first();
                if (!$template) {
                    $template = new EmailTemplate();
                    $template->created_by = auth()->user()->id;
                }
                $template->subject = $request->subject;
                $template->code = $request->content;
                $template->is_active = $request->is_active;
                $template->updated_by = auth()->user()->id;
                $template->save();
                return response()->json(['status' => 200, 'message' => localize('Update Successfully')]);
            }
            return response()->json(['status' => 422, 'message' => localize('Id not found')]);
        } catch (\Throwable $th) {
            return response()->json(['status' => 400, 'message' => $th->getMessage()]);
        }
    }
}
