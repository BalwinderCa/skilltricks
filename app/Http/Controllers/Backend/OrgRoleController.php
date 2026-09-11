<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesOwnedOrganization;
use App\Http\Controllers\Controller;
use App\Models\OrgRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Roles an organization owner defines for their own organization.
 *
 * Its own controller rather than more DashboardController, which is already past
 * 1,300 lines. Every action resolves the organization through the shared trait,
 * so all of them are owner-only by construction.
 */
class OrgRoleController extends Controller
{
    use ResolvesOwnedOrganization;

    public function index()
    {
        $org = $this->ownedOrganization();

        return view('backend.pages.org-roles', [
            'org' => $org,
            'roles' => $org->roles()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $org = $this->ownedOrganization();

        $validated = $this->validateRole($request, $org->id);

        OrgRole::create([
            'organization_id' => $org->id,
            'name' => $validated['name'],
            'can_read' => $request->boolean('can_read'),
            'can_write' => $request->boolean('can_write'),
        ]);

        flash(localize('Role added.'))->success();

        return back();
    }

    public function update(Request $request)
    {
        $org = $this->ownedOrganization();
        $role = $this->ownRole($request->input('role_id'));

        $validated = $this->validateRole($request, $org->id, $role->id);

        $role->update(['name' => $validated['name']]);

        flash(localize('Role updated.'))->success();

        return back();
    }

    public function destroy(Request $request)
    {
        $this->ownedOrganization();
        $role = $this->ownRole($request->input('role_id'));

        // Refused rather than cascaded: clearing the holders would silently drop
        // their rank, and rank decides which context version governs the whole
        // organization. Moving them is the owner's decision to make explicitly.
        if ($role->members()->exists()) {
            flash(localize('That role is still assigned to someone. Move those members to another role first.'))->error();

            return back();
        }

        $role->delete();

        flash(localize('Role deleted.'))->success();

        return back();
    }

    /**
     * Flip one permission. The roles page posts here as the box is ticked.
     */
    public function togglePermission(Request $request)
    {
        $this->ownedOrganization();
        $role = $this->ownRole($request->input('role_id'));

        $validated = $request->validate([
            'permission' => ['required', Rule::in(array_keys(OrgRole::PERMISSIONS))],
            'value' => 'required|boolean',
        ]);

        $role->update([
            OrgRole::PERMISSIONS[$validated['permission']] => $request->boolean('value'),
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * A role belonging to the caller's organization, or a 404.
     *
     * Scoped the way ownDepartmentId() scopes departments: an id from another
     * organization simply is not found, so one tenant cannot name another's row.
     */
    private function ownRole(mixed $roleId): OrgRole
    {
        return OrgRole::where('organization_id', $this->ownedOrganization()->id)
            ->findOrFail((int) $roleId);
    }

    /** @return array{name: string} */
    private function validateRole(Request $request, int $orgId, ?int $ignoreId = null): array
    {
        return $request->validate([
            // Unique per organization, not globally: two tenants may both want
            // an "Editor", and inside one they would be indistinguishable.
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('org_roles', 'name')
                    ->where(fn ($q) => $q->where('organization_id', $orgId))
                    ->ignore($ignoreId),
            ],
        ]);
    }
}
