<?php

namespace App\Http\Controllers\Backend\Pages;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequestForm;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageLocalization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PagesController extends Controller
{

    # construct
    public function __construct()
    {
        $this->middleware(['permission:pages'])->only('index');
        $this->middleware(['permission:add_pages'])->only(['create', 'store']);
        $this->middleware(['permission:edit_pages'])->only(['edit', 'update']);
        $this->middleware(['permission:delete_pages'])->only(['delete']);
    }

    # page list
    public function index(Request $request)
    {
        $searchKey = null;
        $pages = Page::oldest();
        if ($request->search != null) {
            $pages = $pages->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $pages = $pages->paginate(paginationNumber());
        return view('backend.pages.pages.index', compact('pages', 'searchKey'));
    }

    # return view of create form
    public function create()
    {
        return view('backend.pages.pages.create');
    }

    # page store
    public function store(PageRequestForm $request)
    {
        $page = Page::create($this->formattedParams($request));

        $pageLocalization           = PageLocalization::firstOrNew(['lang_key' => env('DEFAULT_LANGUAGE'), 'page_id' => $page->id]);
        $pageLocalization->title    = $request->title;
        $pageLocalization->content  = $request->content;
        $pageLocalization->save();

        flash(localize('Page has been created successfully'))->success();
        return redirect()->route('admin.pages.index');
    }

    # edit page
    public function edit(Request $request, $id)
    {
        try {
            $lang_key = $request->lang_key;
            $language = Language::isActive()->where('code', $lang_key)->first();
            if (!$language) {
                flash(localize('Language you are trying to translate is not available or not active'))->error();
                return redirect()->route('admin.pages.index');
            }
            $page = Page::findOrFail($id);
            return view('backend.pages.pages.edit', compact('page', 'lang_key'));
           
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    # update page
    public function update(PageRequestForm $request)
    {
        $page = Page::findOrFail($request->id);

        if ($request->lang_key == env("DEFAULT_LANGUAGE")) {
            $page->update($this->formattedParams($request, $page));
        }

        $pageLocalization = PageLocalization::firstOrNew(['lang_key' => $request->lang_key, 'page_id' => $page->id]);
        $pageLocalization->title    = $request->title;
        $pageLocalization->content  = $request->content;
        $pageLocalization->save();

        $page->save();
        $pageLocalization->save();
        flash(localize('Page has been updated successfully'))->success();
        return back();
    }

    # delete page
    public function delete($id)
    {
        $page = Page::where('is_system', 0)->orWhereNull('is_system')->where('id', $id)->first();     
        $page->delete();
        flash(localize('Page has been deleted successfully'))->success();
        return back();
    }
    public function termsCondition()
    {
        $page = Page::where('slug', 'terms-conditions')->first();
        if(!$page){
            $request = (object)[
                'title'            => 'Terms Conditions',
                'slug'             => 'terms-conditions',
                'meta_title'       => 'writebot',
                'meta_description' => 'writebot',
                'content'          => 'Welcome to ThemeTags!',
                'meta_image'       => null,
                'is_system'        => 1                 
            ];
            $this->storePage($request);
        }
        $pages = Page::oldest();
        $pages = $pages->onlySystem()->paginate(paginationNumber());
        return view('backend.pages.pages.index', compact('pages', 'searchKey'));
    }
    public function privacyPolicy()
    {
        $page = Page::where('slug', 'privacy-policy')->first();
        if(!$page){
            $request = (object)[
                'title'            => 'Privacy Policy',
                'slug'             => 'privacy-policy',
                'meta_title'       => 'writebot',
                'meta_description' => 'writebot',
                'content'          => 'Welcome to ThemeTags!',
                'meta_image'       => null,
                'is_system'        => 1                
            ];
            $this->storePage($request);
        }
        $pages = Page::oldest();
        $pages = $pages->onlySystem()->paginate(paginationNumber());
        return view('backend.pages.pages.index', compact('pages', 'searchKey'));
    }
    private function storePage($request, $modelId = null)
    {
        if($modelId){
            $page = Page::findOrFail($modelId);
        }else{
            $page = new Page;
        }
        $page->title            = $request->title;
        $page->slug             = convertToSlug($request->title);
        $page->content          = $request->content;
        $page->meta_title       = $request->meta_title;
        $page->meta_description = $request->meta_description;
        $page->meta_image       = $request->meta_image;
        $page->is_system        = $request->is_system ?? 0;
        $page->save();

        $pageLocalization           = PageLocalization::firstOrNew(['lang_key' => env('DEFAULT_LANGUAGE'), 'page_id' => $page->id]);
        $pageLocalization->title    = $request->title;
        $pageLocalization->content  = $request->content;
        $pageLocalization->save();
    }
    private function formattedParams($request, $model = null):array
    {
        $params = [
            'title'            => $request->title,
            
            'content'          => $request->content,
            'meta_title'       => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_image'       => $request->meta_image,
        ];
        if($model){
            if($model->is_system != 1) {
                $params['slug'] = convertToSlug($request->title);
            }
        }else{
            $params['slug'] = convertToSlug($request->title);
        }
        return  $params;
    }
}
