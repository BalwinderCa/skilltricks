<?php

namespace App\Http\Controllers\Backend\faq;

use App\Models\Faq;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\FaqLocalization;
use App\Models\GeneralSetupLocalization;
use DB;

class FaqsController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:faqs'])->only([
            'index', 'store', 'edit', 'update', 'delete',
            'chat_rolecategories', 'chatrolecategories_update', 'chat_rolecategories_store',
            'chatrolecategory_updatestatus', 'chat_rolecategories_edit',
            'chat_categories', 'chat_categories_edit', 'chat_categories_store',
            'chatcategories_update', 'chatcategory_updatestatus',
            'chat_subcategory', 'get_parent_categories', 'chat_subcategories_store',
            'chatsubcategories_update', 'chatsubcategory_updatestatus', 'chat_subcategories_edit',
            'subcategory_menu', 'getSubcategories', 'subcategory_menu_edit', 'subcategory_menu_store',
            'subcategory_menu_update', 'subcategory_menu_question', 'subcategorymenu_questionupdate_status',
            'subcategory_menu_question_edit', 'sub_question_update',
        ]);
    }

    # get all faqs
    public function index(Request $request)
    {
        $searchKey = null;
        $faqs = Faq::oldest();
        if ($request->search != null) {
            $faqs = $faqs->where('question', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $faqs = $faqs->paginate(paginationNumber());
        return view('backend.pages.faqs.index', compact('faqs', 'searchKey'));
    }

    # faq store
    public function store(Request $request)
    {
        $faq = new Faq;
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();

        flash(localize('FAQ has been added successfully'))->success();
        return redirect()->route('admin.faqs.index');
    }

    # edit faq
    public function edit(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);
        $lang_key = $request->lang_key ?? config('custom.default_language');
        return view('backend.pages.faqs.edit', compact('faq', 'lang_key'));
    }

    # update Faq
    public function update(Request $request)
    {
        if (checkLanguage($request->language_key)) {
            $faq = Faq::findOrFail($request->id);
            $faq->question = $request->question;
            $faq->answer = $request->answer;
            $faq->save();
        }
        if ($request->filled('language_key')) {
            $this->storeLocalizationData($request);
        }
        flash(localize('Faq has been updated successfully'))->success();
        return back();
    }

    # delete Faq
    public function delete($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
        flash(localize('Faq has been deleted successfully'))->success();
        return back();
    }

 
    public function chat_rolecategories(Request $request)
    {
        $searchKey = null;
        $chatrolecategoriesdatt = DB::table('chat_role_categories');

        if ($request->search != null) {
            $chatrolecategoriesdatt = $chatrolecategoriesdatt->where('name', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $chatrolecategoriesdatt = $chatrolecategoriesdatt->paginate(10);

        return view('backend.pages.categories.chat-role-categories', compact('chatrolecategoriesdatt', 'searchKey'));
    }


      public function chatrolecategories_update(Request $request,$id)
    {

        $exists = DB::table('chat_role_categories')
                ->where('name', $request->name)
                ->where('id', '!=', $id)
                ->exists();

        if ($exists) {
            flash(localize('Role Category name already exists'))->warning();
            return redirect()->back()->withInput(); // send back form with old data
        }

        $dataupdate = [
           'name' => $request->name,
           'status' => $request->status,
        ];
        
        DB::table('chat_role_categories')->where('id',$request->id)->update($dataupdate);

        flash(localize('Role Categories has been updated successfully'))->success();
        return redirect('dashboard/chat-role-categories');
    }


     public function chat_rolecategories_store(Request $request)
    {

        $check = DB::table('chat_role_categories')->where('name',$request->name)->first();
      
      if(!empty($check)){
          
          flash(localize('Role Category name already exists'))->warning();
          return back();

      }else{
        $data = [
           'name' => $request->name,
           'status' => $request->status,
        ];
        
        DB::table('chat_role_categories')->insert($data);

        flash(localize('Role Categories has been added successfully'))->success();
        return back();
      }
    }

    public function chatrolecategory_updatestatus(Request $request)
    {
        $datastatus = [
           'status' => $request->status,
        ];
        
        DB::table('chat_role_categories')->where('id',$request->id)->update($datastatus);

        return response()->json([
            'success' => true,
            'message' => localize('Role Categories Status has been updated successfully')
        ]);
    }

    public function chat_rolecategories_edit($id)
    {   
        $chatrolecategoriesedit = DB::table('chat_role_categories')->where('id',$id)->first();
        return view('backend.pages.categories.chat-role-categories-edit',compact('chatrolecategoriesedit'));
    }

 
    public function chat_categories(Request $request)
    {
        $searchKey = null;
        $chatcategoriesdatt = DB::table('chat_categories');

        if ($request->search != null) {
            $chatcategoriesdatt = $chatcategoriesdatt->where('name', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $chatcategoriesdatt = $chatcategoriesdatt->paginate(10);

        $rolesdata = DB::table('chat_role_categories')->where('status', 1)->get();

        return view('backend.pages.categories.chat-categories', compact('chatcategoriesdatt', 'searchKey','rolesdata'));
    }


 
    public function chat_categories_edit($id)
    {   
        $rolesdataedit = DB::table('chat_role_categories')->where('status', 1)->get();
        $chatcategoriesedit = DB::table('chat_categories')->where('id',$id)->first();
        return view('backend.pages.categories.chat-categories-edit',compact('chatcategoriesedit',
            'rolesdataedit'));
    }

 
    public function chat_categories_store(Request $request)
    {

        $check = DB::table('chat_categories')->where('name',$request->name)->where('role_name',$request->role_name)->first();
      
      if(!empty($check)){
          
          flash(localize('Category name already exists'))->warning();
          return back();

      }else{
        $data = [
           'name' => $request->name,
           'role_name' => $request->role_name,
           'status' => $request->status,
        ];
        
        DB::table('chat_categories')->insert($data);

        flash(localize('Categories has been added successfully'))->success();
        return back();
      }
    }

 
    public function chatcategories_update(Request $request,$id)
    {

        $exists = DB::table('chat_categories')
                ->where('name', $request->name)
                ->where('role_name', $request->role_name)
                ->where('id', '!=', $id)
                ->exists();

        if ($exists) {
            flash(localize('Category name already exists'))->warning();
            return redirect()->back()->withInput(); // send back form with old data
        }

        $dataupdate = [
           'name' => $request->name,
           'role_name' => $request->role_name,
           'status' => $request->status,
        ];
        
        DB::table('chat_categories')->where('id',$request->id)->update($dataupdate);

        flash(localize('Categories has been updated successfully'))->success();
        return redirect('dashboard/chat-categories');
    }

 
    public function chatcategory_updatestatus(Request $request)
    {
        $datastatus = [
           'status' => $request->status,
        ];
        
        DB::table('chat_categories')->where('id',$request->id)->update($datastatus);

        return response()->json([
            'success' => true,
            'message' => localize('Categories Status has been updated successfully')
        ]);
    }
 
    public function chat_subcategory(Request $request)
    {
        $searchKey = null;
        $chatcategoriessub = DB::table('chat_subcategories');

        if ($request->search != null) {
            $chatcategoriessub = $chatcategoriessub->where('sub_category', 'like', '%' . $request->search . '%');
            $chatcategoriessub = $chatcategoriessub->where('parent_category', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $chatcategoriessub = $chatcategoriessub->paginate(10);

        $chatcategoriesall = DB::table('chat_categories')->where('status',1)->get();

        $rolesdata = DB::table('chat_role_categories')->where('status',1)->get();

        return view('backend.pages.categories.chat-subcategory',compact('chatcategoriessub','searchKey','chatcategoriesall','rolesdata'));
    }

 
    public function get_parent_categories(Request $request)
    {
        $roleId = $request->role_id;

        $categories = DB::table('chat_categories')->where('role_name', $roleId)->where('status',1)->get();

        return response()->json([
            'data' => $categories
        ]);
    }


     public function chat_subcategories_store(Request $request)
    {

        $check = DB::table('chat_subcategories')->where('role_name',$request->role_name)->where('parent_category',$request->parent_category)->where('sub_category',$request->sub_category)->first();
      
      if(!empty($check)){
          
          flash(localize('SubCategory name already exists'))->warning();
          return back();

      }else{
        $data = [
           'role_name' => $request->role_name,
           'parent_category' => $request->parent_category,
           'sub_category' => $request->sub_category,
           'status' => $request->status,
        ];
        
        DB::table('chat_subcategories')->insert($data);

        flash(localize('Sub Categories has been added successfully'))->success();
        return back();
      }
    }


    public function chatsubcategories_update(Request $request,$id)
    {

        $exists = DB::table('chat_subcategories')
                ->where('role_name', $request->role_name)
                ->where('parent_category', $request->parent_category)
                ->where('sub_category', $request->sub_category)
                ->where('id', '!=', $id)
                ->exists();

        if ($exists) {
            flash(localize('SubCategory name already exists'))->warning();
            return redirect()->back()->withInput(); // send back form with old data
        }

        $dataupdate = [
           'role_name' => $request->role_name,
           'parent_category' => $request->parent_category,
           'sub_category' => $request->sub_category,
           'status' => $request->status,
        ];
        
        DB::table('chat_subcategories')->where('id',$request->id)->update($dataupdate);

        flash(localize('SubCategories has been updated successfully'))->success();
        return redirect('dashboard/chat-subcategory');
    }


    public function chatsubcategory_updatestatus(Request $request)
    {
        $datastatus = [
           'status' => $request->status,
        ];
        
        DB::table('chat_subcategories')->where('id',$request->id)->update($datastatus);

        return response()->json([
            'success' => true,
            'message' => localize('Sub Categories Status has been updated successfully')
        ]);
    }


    public function chat_subcategories_edit($id)
    {
        $rolesdataedit = DB::table('chat_role_categories')->where('status',1)->get();
        $chatsubcategoriesedit = DB::table('chat_subcategories')->where('id',$id)->first();
        $chatcategoriesalllist = DB::table('chat_categories')->where('id',$chatsubcategoriesedit->parent_category)->where('status',1)->get();
        return view('backend.pages.categories.chat-subcategories-edit',compact('chatsubcategoriesedit','chatcategoriesalllist','rolesdataedit'));
    }


    public function subcategory_menu(Request $request)
    { 
        $searchKey = null;
        $subcategorymenudata = DB::table('subcategory_menu');

        if ($request->search != null) {
            $subcategorymenudata = $subcategorymenudata->where('categories', 'like', '%' . $request->search . '%');
            $subcategorymenudata = $subcategorymenudata->where('subcategories', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $subcategorymenudata = $subcategorymenudata->paginate(10);

        $chatcategoriesall = DB::table('chat_categories')->where('status',1)->get();
        $rolesdata = DB::table('chat_role_categories')->where('status',1)->get();

        return view('backend.pages.categories.subcategory-menu',compact('subcategorymenudata','searchKey','chatcategoriesall','rolesdata'));
    }


   


    public function getSubcategories(Request $request)
    {
        $subcategories = DB::table('chat_subcategories')->where('parent_category',$request->category_id)->where('status',1)->get();
        return response()->json($subcategories);
    }


    public function subcategory_menu_edit(Request $request,$id)
    {
        $subcategorymenuedit = DB::table('subcategory_menu')->where('id',$id)->first();
        $rolesdataedit = DB::table('chat_role_categories')->where('status',1)->get();
        $chatcategoriesalllist = DB::table('chat_categories')->where('id',$subcategorymenuedit->categories)->where('status',1)->get();
        return view('backend.pages.categories.subcategory-menu-edit',compact('subcategorymenuedit','rolesdataedit','chatcategoriesalllist'));
    }


    public function subcategory_menu_store(Request $request)
    {
        $subcategoryMenuId = DB::table('subcategory_menu')->insertGetId([
            'role' => $request->role,
            'categories' => $request->category,
            'subcategories' => $request->subcategories,
        ]);

        foreach ($request->question as $value) {
            DB::table('subcategory_menu_question')->insert([
                'subcategorymenu_id' => $subcategoryMenuId,
                'question' => $value,
            ]);
        }

        flash(localize('Question has been added successfully'))->success();
        return back();

    }


    public function subcategory_menu_update(Request $request,$id)
    {
        $subcategoryMenuId = DB::table('subcategory_menu')->where('id',$id)->update([
            'role' => $request->role,
            'categories' => $request->category,
            'subcategories' => $request->subcategories,
        ]);

    if(!empty($request->question)){

        $questions = array_filter($request->question, function ($q) {
            return !is_null($q) && $q !== '';
        });
      if (!empty($questions)) {
        foreach ($request->question as $value) {
            DB::table('subcategory_menu_question')->insert([
                'subcategorymenu_id' => $id,
                'question' => $value,
            ]);
        }
      }
    }
        flash(localize('Question has been updated successfully'))->success();

        return redirect('dashboard/subcategory-menu');

    }


     public function subcategory_menu_question(Request $request,$id)
    { 
        $searchKey = null;
        $subcategquestionedit = DB::table('subcategory_menu_question')->where('subcategorymenu_id',$id);

        if ($request->search != null) {
            $subcategquestionedit = $subcategquestionedit->where('question', 'like', '%' . $request->search . '%');;
            $searchKey = $request->search;
        }

        $subcategquestionedit = $subcategquestionedit->paginate(10);

        return view('backend.pages.categories.subcategory-menu-question',compact('subcategquestionedit','searchKey'));
    }



      public function subcategorymenu_questionupdate_status(Request $request)
    {
        $datastatus = [
           'status' => $request->status,
        ];
        
        DB::table('subcategory_menu_question')->where('id',$request->id)->update($datastatus);

        return response()->json([
            'success' => true,
            'message' => localize('Question Status has been updated successfully')
        ]);
    }

    

     public function subcategory_menu_question_edit(Request $request,$id)
    { 
        $subcategquestionidedit = DB::table('subcategory_menu_question')->where('id',$id)->first();
        return view('backend.pages.categories.subcategory-menu-question-edit',compact('subcategquestionidedit'));
    }


  public function sub_question_update(Request $request,$id)
    {
        $questionupdate = DB::table('subcategory_menu_question')->where('id',$id)->update([
            'question' => $request->question,
            'status' => $request->status,
        ]);

        $checkid = DB::table('subcategory_menu_question')->where('id',$id)->first();

        flash(localize('Question has been updated successfully'))->success();

        return redirect('dashboard/subcategory-menu-question/'.$checkid->subcategorymenu_id);

    }




    private function storeLocalizationData($request)
    {
        $lang_key = $request->language_key ?? App::getLocale();

        FaqLocalization::updateOrCreate([
            'lang_key' => $lang_key,
            'faq_id' => $request->id
        ], [
            'question' => $request->question,
            'answer' => $request->answer
        ]);
    }



}
