<?php

namespace App\Http\Controllers\Backend\Folders;

use App\Models\Folder;
use App\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\FolderRequestForm;

class FoldersController extends Controller
{
    # folders list
    public function index(Request $request)
    {
        $searchKey = null;
        $folders = Folder::latest();

        if (isCustomer()) {
            $folders = $folders->where('user_id', auth()->user()->id);
        } else {
            if (!auth()->user()->can('folders')) {
                abort(403);
            }
        }

        if ($request->search != null) {
            $folders = $folders->where('name', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        $folders = $folders->paginate(paginationNumber());
        return view('backend.pages.folders.index', compact('folders', 'searchKey'));
    }

    # folder store
    public function store(FolderRequestForm $request)
    {
        $folder = new Folder;
        $folder->user_id = auth()->user()->id;
        $folder->name = $request->name;
        $folder->slug = preg_replace('/\s+/', '-', trim($request->name)) . '-' . Str::random(5);
        $folder->save();

        flash(localize('Folder has been inserted successfully'))->success();
        return redirect()->route('folders.index');
    }

    # show projects of folder
    public function show(Request $request, $slug)
    {
        if (!auth()->user()->can('folders') && auth()->user()->user_type != "customer") {
            abort(403);
        }

        $folder = Folder::where('slug', $slug)->first();

        $searchKey = null;
        $content_type = null;

        $projects = Project::latest();
        $projects = $projects->where('folder_id', $folder->id)->where('user_id', auth()->user()->id);

        if ($request->search != null) {
            $projects = $projects->where('title', 'like', '%' . $request->search . '%');
            $searchKey = $request->search;
        }

        if ($request->content_type != null) {
            $projects = $projects->where('content_type', $request->content_type);
            $content_type    = $request->content_type;
        }

        $projects = $projects->paginate(paginationNumber());
        return view('backend.pages.folders.show', compact('projects', 'content_type', 'searchKey', 'folder'));
    }

    # edit folder
    public function edit($slug)
    {
        if (!auth()->user()->can('folders') && auth()->user()->user_type != "customer") {
            abort(403);
        }

        $folder = Folder::where('slug', $slug)->first();
        return view('backend.pages.folders.edit', compact('folder'));
    }

    # update folder
    public function update(FolderRequestForm $request)
    {
        $folder = Folder::findOrFail($request->id);
        $folder->name = $request->name;
        $folder->slug = preg_replace('/\s+/', '-', trim($request->name)) . '-' . Str::random(5);
        $folder->save();
        flash(localize('Folder has been updated successfully'))->success();
        return redirect()->route('folders.index');
    }

    # delete folder
    public function delete($id)
    {
        $folder = Folder::findOrFail($id);
        Project::where('folder_id', $folder->id)->delete();
        $folder->delete();
        flash(localize('Folder has been deleted successfully'))->success();
        return back();
    }
}
