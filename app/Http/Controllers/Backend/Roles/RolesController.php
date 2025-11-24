<?php

namespace App\Http\Controllers\Backend\Roles;

use App\Models\User;
use App\Models\SpatieRole;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequestForm;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;


class RolesController extends Controller
{
    # construct
    public function __construct()
    {
        $this->middleware(['permission:roles_and_permissions'])->only('index');
        $this->middleware(['permission:add_roles_and_permissions'])->only(['create', 'store']);
        $this->middleware(['permission:edit_roles_and_permissions'])->only(['edit', 'update']);
        $this->middleware(['permission:delete_roles_and_permissions'])->only(['delete']);
    }

    # role list
    public function index(Request $request)
    {
        $searchKey = null;

        $roles = SpatieRole::oldest()->isActive()->when($request->search, function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->search . '%');
        })->paginate(paginationNumber());

        return view('backend.pages.roles.index', compact('roles', 'searchKey'));
    }

    # return view of create form
    public function create()
    {
        $permission_groups = Permission::all()->groupBy('group_name');
     
        return view('backend.pages.roles.create', compact('permission_groups'));
    }

    # role store
    public function store(RoleRequestForm $request)
    {
        $role = SpatieRole::create(['name' => $request->name, 'created_by' => auth()->user()->id]);
        $role->givePermissionTo($request->permissions);
        flash(localize('New Role has been added successfully'))->success();
        return redirect()->route('admin.roles.index');
    }

    # edit role
    public function edit(Request $request, $id)
    {
        $role = SpatieRole::findOrFail($id);
        $permission_groups = Permission::all()->groupBy('group_name');
        return view('backend.pages.roles.edit', compact('role', 'permission_groups'));
    }

    # update role
    public function update(RoleRequestForm $request)
    {
        $role = SpatieRole::findOrFail($request->id);
        $role->name = $request->name;
        $role->updated_by = auth()->user()->id;
        $role->syncPermissions($request->permissions);
        $role->save();
        flash(localize('Role has been updated successfully'))->success();
        return back();
    }

    # delete role
    public function delete($id)
    {
        $user_count = User::where('role_id', $id)->count();
        $message = 'Role has been deleted successfully';
        if ($user_count > 0) {
            $message = $user_count . " users already Exit.You Can't Delete it.";
            flash(localize($message))->warning();
            return redirect()->route('admin.roles.index');
        }
        $role = SpatieRole::where('id', $id)->first();

        if ($user_count == 0 && $role->created_by == auth()->user()->id) {
            $role->delete();
        } elseif ($user_count == 0 && auth()->user()->id == 1) {
            $role->delete();
        } else {
            $message = "Sorry You are Not Creator";
        }
        flash(localize($message))->success();
        return redirect()->route('admin.roles.index');
    }
    // status update
    public function updateStatus(Request $request)
    {
        $user = auth()->user();
        $role = SpatieRole::when($user->role_id != 1, function ($q) use ($user) {
            $q->where('created_by', $user->id);
        })->where('id', $request->id)->first();

        if ($role) {
            $role->is_active = $request->is_active;
            $role->save();
            return 1;
        }
        return 0;
    }
    public function restorePermission()
    {
        try {
            $user = auth()->user();
            if($user->user_type == 'admin'){
                Artisan::call('db:seed --class=PermissionsTableSeeder');
             
            }
            flash(localize('Permissions data restore successfully'))->success();
            return redirect()->route('admin.roles.index');
        } catch (\Throwable $th) {
            flash($th->getMessage())->error();
            return redirect()->route('admin.roles.index');
            
        }
    }
}
