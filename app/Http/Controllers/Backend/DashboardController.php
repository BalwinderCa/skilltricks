<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Concerns\ResolvesOwnedOrganization;
use App\Http\Controllers\Controller;
use App\Jobs\User\EmailConfirmationJob;
use App\Mail\User\TeamInvitationMail;
use App\Models\Department;
use App\Models\Document;
use App\Models\Organization;
use App\Models\OrgRole;
use App\Models\Project;
use App\Models\SearchUserChat;
use App\Models\SystemSetting;
use App\Models\Template;
use App\Models\User;
use App\Services\MyGoals;
use App\Services\OrganizationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    use ResolvesOwnedOrganization;

    /** Roster page size. */
    private const MEMBERS_PER_PAGE = 10;

    // admin dashboard

    public function index(Request $request)
    {

        // total words chart

        $totalWordsChart = $this->totalWordsChart($request->timeline);

        $totalWordsData = $totalWordsChart[0];

        $timelineText = $totalWordsChart[1];

        // top 5 template words

        $totalTemplateWordsData = $this->topFiveTemplateChart();

        // give permission to the Super admin

        $user = auth()->user();

        $org = $user->organization;

        // Org-wide totals for the dashboard cards. Scoped to fellow members
        // rather than the whole install, since the panel above them describes
        // this organization.
        $memberIds = $org ? $org->members()->pluck('id') : collect();

        $view = view('backend.pages.dashboard', [

            'user' => $user,

            'org' => $org,

            'activeContext' => $org ? $org->activeContext : null,

            'orgMemberCount' => $memberIds->count(),

            'orgDocumentCount' => $memberIds->isEmpty() ? 0 : Document::whereIn('user_id', $memberIds)->count(),

            // Only chats that actually hold a conversation. Visiting "New Chat"
            // creates an empty SearchUserChat row and redirects to it, so a
            // plain row count reports clicks rather than chats.
            'orgChatCount' => $memberIds->isEmpty() ? 0 : SearchUserChat::whereIn('user_id', $memberIds)->whereNotNull('response')->count(),

            'latestChatId' => SearchUserChat::where('user_id', $user->id)->whereNotNull('response')->latest('id')->value('id'),

            'totalWordsData' => $totalWordsData,

            'timelineText' => $timelineText,

            'totalTemplateWordsData' => $totalTemplateWordsData,

            'profileCompletion' => $this->profileCompletion($user),

            // Features spec, phase 3: this user's goals from published strategies.
            'myGoals' => app(MyGoals::class)->for($user),

            'isLeader' => app(OrganizationService::class)->isLeader($user),

        ]);

        if (isAdmin() && $user->hasRole('Super Admin')) {

            return $view;

        } elseif (isAdmin()) {

            $user->assignRole('Super Admin');

        }

        // Nothing gates the dashboard any more. Signup lands here, and the
        // profile-completion card carries the remaining work — seniority
        // included, since it comes from the Role field on the profile form.
        return $view;

    }

    /**
     * How much of the profile + company form is filled in, as a percentage and
     * the list of what is still missing.
     *
     * @return array{percent: int, missing: array<int, string>}
     */
    private function profileCompletion(User $user): array
    {
        $labels = [
            'name' => 'Name',
            'phone' => 'Phone',
            'company_name' => 'Company name',
            'company_address' => 'Company address',
            'number_employess' => 'Number of employees',
            'company_category' => 'Company category',
            'about_company' => 'About the company',
        ];

        $missing = [];

        foreach ($labels as $field => $label) {
            if (trim((string) $user->$field) === '') {
                $missing[] = $label;
            }
        }

        $filled = count($labels) - count($missing);

        return [
            'percent' => (int) round($filled / count($labels) * 100),
            'missing' => $missing,
        ];
    }

    // admin profile

    public function profile()
    {

        $user = auth()->user();

        return view('backend.pages.profile', compact('user'));

    }

    // admin profile

    public function updateProfile(Request $request)
    {

        $user = auth()->user();

        // The profile page posts one section at a time, so write only the fields
        // the submitted form actually carried. Assigning all of them
        // unconditionally would let the Basic Information form -- which has no
        // company inputs -- null out every company column on save.
        $fields = [
            'name',
            'phone',
            'avatar',
            'company_name',
            'company_address',
            'number_employess',
            'company_category',
            'about_company',
        ];

        foreach ($fields as $field) {

            // has(), not filled(): a field that was submitted empty is the user
            // clearing it, and must still be written.
            if ($request->has($field)) {

                $user->$field = $field === 'phone' ? validatePhone($request->phone) : $request->$field;

            }

        }

        if ($request->has('password') && $request->password != '') {

            if ($request->password != $request->password_confirmation) {

                flash(localize('Password confirmation does not match'))->error();

                return back();

            }

            $user->password = Hash::make($request->password);

        }

        $user->save();

        flash(localize('Profile has been updated'))->success();

        return back();

    }

    // organization page: who is in this organization, their role and department

    public function organization(Request $request)
    {
        $user = auth()->user();
        $org = $user->organization;

        // The sidebar's department links land here filtered. An id from another
        // organization simply matches nobody, since the query is org-scoped.
        $departmentId = $request->integer('department') ?: null;

        $activeDepartment = $departmentId && $org ? $org->departments()->find($departmentId) : null;

        $roster = $org
            ? $org->members()->with(['department', 'orgRole'])
                ->where('id', '!=', (int) $org->owner_user_id)
                ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                ->orderBy('name')
            : null;

        // The chart needs every member at once, so it is only built when its tab
        // is the one being looked at.
        $view = $request->query('view') === 'chart' ? 'chart' : 'members';

        // Roster only. The active context is the dashboard's job — this page
        // answers "who is here", not "what are we working from".
        return view('backend.pages.organization', [
            'view' => $view,
            'chart' => $view === 'chart' && $org ? $this->orgChart($org) : null,
            'user' => $user,
            'org' => $org,
            'isOwner' => $org && (int) $org->owner_user_id === (int) $user->id,
            // The owner is kept off the roster for now (the "!= owner" above).
            // Cast, not a null check: an unowned organization gives 0, which
            // matches no user, so every member still lists. The view's owner
            // branches and the server's "the owner cannot be removed" guard both
            // stay — this is a display filter, not a rule.
            'orgMembers' => $roster
                ? $roster->paginate(self::MEMBERS_PER_PAGE)
                : new LengthAwarePaginator([], 0, self::MEMBERS_PER_PAGE),
            'departments' => $org ? $org->departments()->orderBy('name')->get() : collect(),
            // The dialog's Role select and the CSV help text both read this.
            'orgRoles' => $org ? $org->roles()->orderBy('name')->get() : collect(),
            'activeDepartment' => $activeDepartment,
            // Candidates for head: everyone in that department, not just the
            // page being shown. The owner is not among them — they head the
            // chart already.
            'departmentMembers' => $activeDepartment
                ? $org->members()->where('department_id', $activeDepartment->id)
                    ->where('id', '!=', (int) $org->owner_user_id)
                    ->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * The org chart, derived rather than stored: owner at the top, one branch
     * per department, and inside a branch the highest-ranked member leads.
     *
     * Nothing here is a new fact — it is the roles and departments that are
     * already on the roster, drawn. That means no second place to keep in sync,
     * and it also means a tie between two equally ranked people in the same
     * department is settled by name, not by anyone's intent. An explicit
     * "head of department" belongs on the departments table if that matters.
     *
     * @return array{root: ?User, branches: array<int, array{department: ?Department, head: ?User, chosen: bool, count: int, nodes: array<int, array{user: User, children: array<int, mixed>}>}>}
     */
    private function orgChart(Organization $org): array
    {
        // sort_order first, so a card dragged into place stays there; rank and
        // name still decide among everyone who has never been moved (all zero).
        $members = $org->members()->with(['department', 'orgRole'])
            ->orderBy('sort_order')->orderByDesc('hierarchy_rank')->orderBy('name')->get();

        // The owner heads the chart whether or not they hold the highest rank —
        // they own the organization. Failing that, the most senior member does.
        $root = $members->firstWhere('id', $org->owner_user_id) ?: $members->first();

        $rest = $members->reject(fn ($m) => $root && (int) $m->id === (int) $root->id);

        $grouped = $rest->groupBy('department_id');

        // Every department gets a column, including the ones nobody is in yet —
        // otherwise a department you just created has nothing to drag people
        // onto, and the only way into it would be the Members tab.
        $branches = $org->departments->map(function ($department) use ($grouped) {
            $people = $grouped->get($department->id, collect());

            // The chosen head, but only while they are still in this department
            // — a stale pick falls back to seniority rather than emptying the
            // branch. $people is already sorted rank-first.
            $head = $department->head_user_id
                ? $people->firstWhere('id', $department->head_user_id)
                : null;

            $head = $head ?: $people->first();

            return [
                'department' => $department,
                'head' => $head,
                'chosen' => $head && (int) $department->head_user_id === (int) $head->id,
                'count' => $people->count(),
                'nodes' => $this->reportingTree($people, $head),
            ];
        });

        // Anyone with no department at all keeps their own column.
        $ungrouped = $grouped->get(null, collect());

        if ($ungrouped->isNotEmpty()) {
            $head = $ungrouped->first();

            $branches->push([
                'department' => null,
                'head' => $head,
                'chosen' => false,
                'count' => $ungrouped->count(),
                'nodes' => $this->reportingTree($ungrouped, $head),
            ]);
        }

        $branches = $branches
            // An explicit instanceof rather than ?->: the relation is nullable at
            // runtime for anyone without a department, but not in the type.
            ->sortBy(fn ($branch) => $branch['department'] instanceof Department
                ? $branch['department']->name
                : "\u{FFFF}") // no department sorts last
            ->values()
            ->all();

        return ['root' => $root, 'branches' => $branches];
    }

    /**
     * Nest a department's people by who they report to.
     *
     * Only managers inside the same column can nest someone: a report whose
     * manager sits in another department (or has left) is drawn at the top
     * level rather than vanishing into a branch that is not on screen.
     *
     * @param  Collection<int, User>  $people
     * @return array<int, array{user: User, children: array<int, mixed>}>
     */
    private function reportingTree($people, ?User $head): array
    {
        $byManager = $people->groupBy('manager_id');
        $present = $people->keyBy('id');

        $build = function (User $person) use (&$build, $byManager) {
            return [
                'user' => $person,
                'children' => $byManager->get($person->id, collect())
                    ->map(fn ($child) => $build($child))
                    ->values()
                    ->all(),
            ];
        };

        $roots = $people->filter(function ($person) use ($present, $head) {
            // The head anchors the column even when they report to someone;
            // otherwise they would be drawn twice.
            if ($head && (int) $person->id === (int) $head->id) {
                return true;
            }

            return ! $person->manager_id || ! $present->has($person->manager_id);
        });

        // A cycle written straight into the database would recurse forever.
        // Nothing this app writes can make one — moveMember() refuses — but the
        // column is drawn from data, and data can be edited elsewhere.
        $seen = [];

        return $roots->reject(function ($person) use (&$seen) {
            if (isset($seen[$person->id])) {
                return true;
            }

            $seen[$person->id] = true;

            return false;
        })
            // The head leads the column, whatever their rank: that is what
            // choosing one means.
            ->sortByDesc(fn ($person) => $head && (int) $person->id === (int) $head->id ? 1 : 0)
            ->map(fn ($person) => $build($person))
            ->values()
            ->all();
    }

    // the CSV shape the bulk importer expects, filled in with the caller's own domain

    public function sampleTeamCsv()
    {
        $org = auth()->user()->organization;

        // Free-domain organizations are keyed on the whole address, which is not
        // a domain anyone can be at — fall back to a placeholder there.
        $domain = $org && ! str_contains((string) $org->domain, '@') ? $org->domain : 'example.com';

        $csv = "name,email,department\n"
            ."Jane Doe,jane@{$domain},Marketing\n"
            ."John Smith,john@{$domain},Engineering\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="team-sample.csv"',
        ]);
    }

    /**
     * Bulk-add members from the sample CSV.
     *
     * Owner-only, like every other write on this page. Accounts are created
     * unverified with an unusable password and a reset link mailed out — the
     * owner vouching for an address is not the same as the address answering,
     * so nothing here marks an email verified.
     */
    public function importMembers(Request $request)
    {
        $request->validate([
            'members' => 'required|file|mimes:csv,txt|max:512',
            'send_invites' => 'nullable|boolean',
        ]);

        $org = $this->ownedOrganization();
        $invite = $request->boolean('send_invites');

        $rows = array_map('str_getcsv', file(
            $request->file('members')->getRealPath(),
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        ));

        // Header row, if the file kept the sample's.
        if (isset($rows[0][0]) && strtolower(trim((string) $rows[0][0])) === 'name') {
            array_shift($rows);
        }

        // ponytail: 200 rows a go. Anything larger wants a queued job, not a
        // longer request timeout.
        $rows = array_slice($rows, 0, 200);

        // No role column: an imported member arrives without one and the owner
        // assigns it from the roster. Rank still has to be something, so they
        // start at the bottom of the ladder.
        $floorRank = min(OrganizationService::VALID_RANKS);

        $added = 0;
        $skipped = 0;
        $invited = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $email = strtolower(trim((string) ($row[1] ?? '')));
            $department = trim((string) ($row[2] ?? ''));

            if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            // withTrashed: a soft-deleted account still holds the address, and
            // creating a second row for it would break every lookup by email.
            if (User::withTrashed()->where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            $member = $this->createMember(
                $org, $name, $email, null, $floorRank, $this->departmentIdFor($org, $department)
            );

            if ($invite) {
                $invited += $this->sendInvitation($member) ? 1 : 0;
            }

            $added++;
        }

        // localize() takes a language, not replacements — the counts are appended.
        flash(localize('Team import complete.')." Added: {$added}. Skipped: {$skipped}."
            .($invite ? ' '.localize('Invitations sent:')." {$invited}." : ''))->success();

        return back();
    }

    /**
     * Create one member of an organization.
     *
     * Shared by the CSV import and the Add dialog so the invite path — unusable
     * password, unverified address, best-effort reset link — cannot drift apart
     * between them.
     */
    private function createMember(Organization $org, string $name, string $email, ?OrgRole $role, int $rank, ?int $departmentId): User
    {
        // No password anyone can use and no mail: an account only becomes
        // reachable through sendInvitation(), which the caller decides on.
        //
        // A member may arrive without a role (the CSV import does not carry one),
        // so rank is passed rather than read off the role. Callers that do have a
        // role pass its level, which is what keeps the two in step.
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'organization_id' => $org->id,
            'org_role_id' => $role?->id,
            'hierarchy_rank' => $rank,
            'department_id' => $departmentId,
        ]);
    }

    /**
     * A role belonging to this organization, or null.
     *
     * Scoped exactly like ownDepartmentId(): an id from another organization is
     * simply not found, so one tenant cannot assign another's role.
     */
    private function ownRole(Organization $org, mixed $roleId): ?OrgRole
    {
        if (! $roleId) {
            return null;
        }

        return OrgRole::where('organization_id', $org->id)->find((int) $roleId);
    }

    /**
     * Issue a temporary password and email it.
     *
     * Marks the address verified: the temporary password exists nowhere but in
     * that mailbox, so signing in with it demonstrates control of the address
     * the same way clicking a verification link does — and without this they
     * would be bounced to the verification notice instead of the password form.
     *
     * must_change_password blocks the dashboard until they pick their own, so
     * the temporary one is a single-use way in, not a credential that lingers.
     *
     * Returns false when the mail fails, so a caller can report what actually
     * went out instead of claiming a send that did not happen.
     */
    private function sendInvitation(User $member): bool
    {
        // No symbols: this gets retyped by hand from an email.
        $temporary = Str::password(12, true, true, false);

        $member->forceFill([
            'password' => Hash::make($temporary),
            'must_change_password' => true,
            'email_verified_at' => $member->email_verified_at ?: now(),
            'email_or_otp_verified' => 1,
        ])->save();

        try {
            Mail::to($member->email)->send(new TeamInvitationMail($member, $temporary));

            return true;
        } catch (\Throwable $e) {
            Log::warning("Invitation email failed for {$member->email}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * A member of the caller's own organization, or a 404.
     *
     * Scoped to the organization, not merely found by id: without the scope an
     * owner could edit or evict anyone on the install by posting their user id.
     */
    private function memberOfOwnedOrganization(Organization $org, int $userId): User
    {
        $member = User::where('id', $userId)->where('organization_id', $org->id)->first();

        if (! $member) {
            abort(404);
        }

        return $member;
    }

    /**
     * Resolve a department name from a CSV row to a row in this organization,
     * creating it when it is new.
     *
     * The import stays as easy to write as it was — you type a department name
     * — without letting a typo silently drop the value on the floor.
     */
    private function departmentIdFor(Organization $org, string $name): ?int
    {
        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $existing = Department::where('organization_id', $org->id)->where('name', $name)->first();

        if ($existing) {
            return (int) $existing->id;
        }

        return (int) Department::create([
            'organization_id' => $org->id,
            'name' => $name,
            'color' => Department::nextColorFor((int) $org->id),
        ])->id;
    }

    /**
     * A department id, but only if it belongs to this organization.
     *
     * The select is populated from the caller's own departments; this is the
     * server refusing to take anyone else's id from a hand-made request.
     */
    private function ownDepartmentId(Organization $org, mixed $departmentId): ?int
    {
        if (! $departmentId) {
            return null;
        }

        $department = Department::where('organization_id', $org->id)->find((int) $departmentId);

        return $department ? (int) $department->id : null;
    }

    // create a department from the sidebar

    public function storeDepartment(Request $request)
    {
        $org = $this->ownedOrganization();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|in:'.implode(',', Department::PALETTE),
        ]);

        $name = trim($validated['name']);

        if (Department::where('organization_id', $org->id)->where('name', $name)->exists()) {
            flash(localize('That department already exists.'))->error();

            return back();
        }

        Department::create([
            'organization_id' => $org->id,
            'name' => $name,
            'color' => $validated['color'] ?? Department::nextColorFor((int) $org->id),
        ]);

        flash(localize('Department added.'))->success();

        return back();
    }

    /**
     * Move a member between departments — the chart's drag and drop.
     *
     * Dropping on a branch files them under it; dropping on its head card does
     * that and hands them the branch as well. Both are the same two columns the
     * Members tab writes, so the chart and the roster cannot disagree.
     */
    public function moveMember(Request $request)
    {
        $org = $this->ownedOrganization();

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'department_id' => 'nullable|integer',
            'as_head' => 'nullable|boolean',
            // Dropping onto a person nests under them; dropping onto a band
            // sends this empty, which lifts them back to the top level.
            'manager_id' => 'nullable|integer',
            // Dropping into the gap between two cards places them there, taking
            // the manager and department of the sibling they land next to.
            'before_id' => 'nullable|integer',
            'after_id' => 'nullable|integer',
        ]);

        $member = $this->memberOfOwnedOrganization($org, (int) $validated['user_id']);

        // ?? null throughout: a nullable rule leaves the key out entirely when
        // the request omits it, and a drop onto a person sends no department.
        $departmentId = $validated['department_id'] ?? null;

        $department = $departmentId
            ? Department::where('organization_id', $org->id)->find((int) $departmentId)
            : null;

        if ($departmentId && ! $department) {
            abort(404);
        }

        // Leaving a department they led: clear the pick rather than leave it
        // dangling. The chart tolerates a stale head, but the roster's picker
        // would show a name that is no longer there.
        if ($member->department_id && (int) $member->department_id !== (int) ($department->id ?? 0)) {
            Department::where('organization_id', $org->id)
                ->where('id', $member->department_id)
                ->where('head_user_id', $member->id)
                ->update(['head_user_id' => null]);
        }

        // A gap names a sibling, and a sibling carries the parent with it:
        // landing next to someone means joining their row, wherever that is.
        $anchorId = $validated['before_id'] ?? $validated['after_id'] ?? null;
        $anchor = null;

        if ($anchorId) {
            $anchor = User::where('id', (int) $anchorId)
                ->where('organization_id', $org->id)
                ->first();

            if (! $anchor) {
                abort(404);
            }

            if ((int) $anchor->id === (int) $member->id) {
                return $this->moveRefused($request, localize('Someone cannot be placed next to themselves.'));
            }

            $validated['manager_id'] = $anchor->manager_id;
            $department = $anchor->department;
        }

        $manager = null;

        if ($validated['manager_id'] ?? null) {
            $manager = User::where('id', (int) $validated['manager_id'])
                ->where('organization_id', $org->id)
                ->first();

            if (! $manager) {
                abort(404);
            }

            if ((int) $manager->id === (int) $member->id) {
                return $this->moveRefused($request, localize('Someone cannot report to themselves.'));
            }

            // Their own report, or their report's report: accepting this would
            // detach the whole subtree from the chart and make the drawing
            // recurse forever.
            if ($this->reportsTo($manager, $member)) {
                return $this->moveRefused($request, localize('That would make a reporting loop.'));
            }

            // A report sits inside their manager's column, so the drop carries
            // the department with it.
            $department = $manager->department;
        }

        $member->forceFill([
            'department_id' => $department?->id,
            'manager_id' => $manager?->id,
        ])->save();

        // Their reports do not follow them into another department: lift them
        // to that column's top level rather than drawing lines across columns.
        if ($member->wasChanged('department_id')) {
            User::where('manager_id', $member->id)
                ->where('organization_id', $org->id)
                ->where('department_id', '!=', $member->department_id)
                ->update(['manager_id' => null]);
        }

        $this->placeAmongSiblings($org, $member, $anchor, isset($validated['after_id']) && $validated['after_id']);

        if ($department && $request->boolean('as_head')) {
            $department->forceFill(['head_user_id' => $member->id])->save();
        }

        if ($anchor) {
            $message = localize('Placed next to').' '.$anchor->name;
        } elseif ($manager) {
            $message = localize('Now reports to').' '.$manager->name;
        } elseif ($department) {
            $message = localize('Moved to').' '.$department->name
                .($request->boolean('as_head') ? ' ('.localize('as head').')' : '');
        } else {
            $message = localize('Removed from their department.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'html' => $this->chartHtml($org),
            ]);
        }

        flash($message)->success();

        return back();
    }

    /**
     * Put a member in order among the people they now share a parent with.
     *
     * The whole row is renumbered 0..n rather than squeezing a fractional value
     * between two neighbours: the groups here are a department's worth of
     * people, and a plain integer sequence never runs out of room between two
     * cards the way halved gaps eventually do.
     */
    private function placeAmongSiblings(Organization $org, User $member, ?User $anchor, bool $after): void
    {
        $siblings = User::where('organization_id', $org->id)
            ->where('manager_id', $member->manager_id)
            ->where('department_id', $member->department_id)
            ->whereKeyNot($member->id)
            ->orderBy('sort_order')->orderByDesc('hierarchy_rank')->orderBy('name')
            ->get();

        $ordered = $siblings->values()->all();

        if ($anchor) {
            $at = null;

            foreach ($ordered as $index => $sibling) {
                if ((int) $sibling->id === (int) $anchor->id) {
                    $at = $after ? $index + 1 : $index;
                    break;
                }
            }

            // The anchor moved out from under us between render and drop; the
            // end of the row is the honest place for it.
            array_splice($ordered, $at ?? count($ordered), 0, [$member]);
        } else {
            // A plain nest or column move lands at the end, where a new arrival
            // belongs until someone says otherwise.
            $ordered[] = $member;
        }

        foreach ($ordered as $index => $person) {
            if ((int) $person->sort_order !== $index) {
                $person->forceFill(['sort_order' => $index])->save();
            }
        }
    }

    /** Is $person somewhere below $ancestor in the reporting tree? */
    private function reportsTo(User $person, User $ancestor): bool
    {
        $seen = [];
        $current = $person;

        while ($current && $current->manager_id) {
            // Guards against a loop that is already in the data.
            if (isset($seen[$current->id])) {
                return false;
            }

            $seen[$current->id] = true;

            if ((int) $current->manager_id === (int) $ancestor->id) {
                return true;
            }

            $current = User::find($current->manager_id);
        }

        return false;
    }

    /** A refused move, answered the way the caller asked. */
    private function moveRefused(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        flash($message)->error();

        return back();
    }

    /**
     * The chart, re-derived and drawn, for a caller that already has the page.
     *
     * Rendering server-side keeps one definition of who leads what: the drag
     * and drop asks for a new picture rather than reimplementing the rules in
     * JavaScript.
     */
    private function chartHtml(Organization $org): string
    {
        return view('backend.pages.partials.org-chart-body', [
            'chart' => $this->orgChart($org),
            // Only an owner can reach the endpoints that ask for this, and the
            // cards carry owner-only edit hooks.
            'isOwner' => true,
        ])->render();
    }

    /**
     * Choose which member leads a department.
     *
     * Constrained to that department's own members: the head is drawn inside
     * the branch on the chart, so someone from elsewhere would either appear
     * twice or disappear from their own.
     */
    public function updateDepartmentHead(Request $request)
    {
        $org = $this->ownedOrganization();

        $validated = $request->validate([
            'department_id' => 'required|integer',
            'head_user_id' => 'nullable|integer',
        ]);

        $department = Department::where('organization_id', $org->id)->find((int) $validated['department_id']);

        if (! $department) {
            abort(404);
        }

        $headId = $validated['head_user_id'] ?? null;

        if ($headId) {
            $head = User::where('id', (int) $headId)
                ->where('organization_id', $org->id)
                ->where('department_id', $department->id)
                ->first();

            if (! $head) {
                flash(localize('That person is not in this department.'))->error();

                return back();
            }

            $headId = (int) $head->id;
        }

        $department->forceFill(['head_user_id' => $headId ?: null])->save();

        flash($headId ? localize('Department head updated.') : localize('Department head cleared.'))->success();

        return back();
    }

    /**
     * Delete a department.
     *
     * Members are not deleted with it — they simply stop having one, which is
     * the same state as never having been given one.
     */
    public function destroyDepartment(Request $request)
    {
        $org = $this->ownedOrganization();

        $validated = $request->validate(['department_id' => 'required|integer']);

        $department = Department::where('organization_id', $org->id)->find((int) $validated['department_id']);

        if (! $department) {
            abort(404);
        }

        User::where('department_id', $department->id)->update(['department_id' => null]);
        $department->delete();

        flash(localize('Department removed.'))->success();

        return back();
    }

    // add one member from the Teams dialog

    public function storeMember(Request $request)
    {
        $org = $this->ownedOrganization();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'org_role_id' => 'required|integer',
            'department_id' => 'nullable|integer',
            'send_invite' => 'nullable|boolean',
        ]);

        $email = strtolower(trim($validated['email']));

        // withTrashed, like the import: a soft-deleted account still holds the
        // address, so a second row for it would break every lookup by email.
        if (User::withTrashed()->where('email', $email)->exists()) {
            flash(localize('That email address already has an account.'))->error();

            return back();
        }

        $role = $this->ownRole($org, $validated['org_role_id']);

        if (! $role) {
            flash(localize('Pick a role from your organization.'))->error();

            return back();
        }

        $member = $this->createMember(
            // Roles carry no seniority, so a new member starts at the bottom of
            // the ladder however they were added -- dialog or CSV alike.
            $org, $validated['name'], $email, $role, min(OrganizationService::VALID_RANKS),
            $this->ownDepartmentId($org, $validated['department_id'] ?? null)
        );

        if (! $request->boolean('send_invite')) {
            flash(localize('Team member added. They cannot sign in until you send them an invitation.'))->success();

            return back();
        }

        flash($this->sendInvitation($member)
            ? localize('Team member added and invited. They have been emailed a temporary password.')
            : localize('Team member added, but the invitation email could not be sent. Try inviting them again from the roster.'))->success();

        return back();
    }

    // edit one member from the Teams dialog

    public function updateMember(Request $request, OrganizationService $organizations)
    {
        $org = $this->ownedOrganization();

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'org_role_id' => 'required|integer',
            'department_id' => 'nullable|integer',
        ]);

        $member = $this->memberOfOwnedOrganization($org, (int) $validated['user_id']);

        $email = strtolower(trim($validated['email']));
        $emailChanged = $email !== strtolower((string) $member->email);

        // withTrashed and excluding this row: a soft-deleted account still holds
        // its address, so moving a member onto one would break lookups by email.
        if ($emailChanged && User::withTrashed()->where('email', $email)->whereKeyNot($member->id)->exists()) {
            flash(localize('That email address already has an account.'))->error();

            return back();
        }

        $member->forceFill([
            'name' => $validated['name'],
            'email' => $email,
            'department_id' => $this->ownDepartmentId($org, $validated['department_id'] ?? null),
        ] + ($emailChanged ? [
            // Nobody has proven the new address — the owner typed it. Carrying the
            // old verification over would mark an unproven mailbox as verified.
            'email_verified_at' => null,
            'email_or_otp_verified' => 0,
        ] : []))->save();

        if ($emailChanged) {
            // Best-effort: a dead SMTP server must not undo the edit. They can
            // resend it themselves from the verification notice.
            try {
                EmailConfirmationJob::dispatchSync($member);
            } catch (\Throwable $e) {
                Log::warning("Verification email failed for {$email}: {$e->getMessage()}");
            }
        }

        $role = $this->ownRole($org, $validated['org_role_id']);

        if (! $role) {
            flash(localize('Pick a role from your organization.'))->error();

            return back();
        }

        // Role only. It carries no level, so changing it cannot move the
        // member's rank -- and rank is what the context-governance election
        // reads, so nothing here needs re-electing.
        $member->forceFill(['org_role_id' => $role->id])->save();

        flash(localize('Team member updated.').($emailChanged
            ? ' '.localize('The new address has to be verified before they can sign in again.')
            : ''))->success();

        return back();
    }

    /**
     * Invite the checked members — the catch-up for anyone added without the
     * "send invitations" box ticked.
     *
     * Re-issuing is deliberate: it mints a fresh temporary password, so a lost
     * or stale invitation is fixed by sending another rather than by digging the
     * old one out.
     */
    public function inviteMembers(Request $request)
    {
        $org = $this->ownedOrganization();

        $request->validate([
            'user_ids' => 'required|array|max:200',
            'user_ids.*' => 'integer',
        ]);

        $ids = array_unique(array_filter(array_map('intval', $request->input('user_ids', []))));

        $sent = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $member = User::where('id', $id)->where('organization_id', $org->id)->first();

            // No address, no invitation: registration allows phone-only accounts.
            if (! $member || trim((string) $member->email) === '') {
                $skipped++;

                continue;
            }

            $this->sendInvitation($member) ? $sent++ : $skipped++;
        }

        if ($sent === 0) {
            flash(localize('No invitations were sent.'))->error();

            return back();
        }

        // localize() takes a language, not replacements — the counts are appended.
        flash(localize('Invitations sent:')." {$sent}."
            .($skipped > 0 ? ' '.localize('Skipped:')." {$skipped}." : ''))->success();

        return back();
    }

    /**
     * Take a member off the roster.
     *
     * The service owns the detach: it has to clear the membership and re-elect
     * the active context under one lock, in that order, or an evicted member's
     * declaration keeps governing the organization.
     */
    public function removeMember(Request $request, OrganizationService $organizations)
    {
        $org = $this->ownedOrganization();

        $request->validate([
            'user_id' => 'nullable|integer',
            'user_ids' => 'nullable|array|max:200',
            'user_ids.*' => 'integer',
        ]);

        // One row's Delete button names a single member; the checkboxes name a
        // set. A row button wins, so a stale tick elsewhere in the table cannot
        // widen a single delete into a bulk one.
        $ids = $request->filled('user_id')
            ? [(int) $request->input('user_id')]
            : array_map('intval', (array) $request->input('user_ids', []));

        $ids = array_unique(array_filter($ids));

        if ($ids === []) {
            flash(localize('Select at least one member to remove.'))->error();

            return back();
        }

        $removed = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $member = User::where('id', $id)->where('organization_id', $org->id)->first();

            if (! $member) {
                $skipped++;

                continue;
            }

            try {
                $organizations->detachMember(auth()->user(), $member);
                $removed++;
            } catch (\RuntimeException $e) {
                // The owner's own row, which cannot be evicted.
                $skipped++;
            }
        }

        if ($removed === 0) {
            if ($request->expectsJson()) {
                abort(422, 'Nobody was removed.');
            }

            flash(localize('Nobody was removed.'))->error();

            return back();
        }

        // localize() takes a language, not replacements — the counts are appended.
        $message = localize('Removed from the organization:')." {$removed}."
            .($skipped > 0 ? ' '.localize('Skipped:')." {$skipped}." : '');

        // The chart's remove zone posts here too, and wants the redrawn chart
        // back rather than a redirect.
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'html' => $this->chartHtml($org->fresh()),
            ]);
        }

        flash($message)->success();

        return back();
    }

    /**
     * The blocking password form an invited member lands on.
     *
     * The sidebar is hidden while must_change_password is set (see the layout),
     * and the middleware sends every other dashboard route back here, so this is
     * the only page they can reach until they choose a password.
     */
    public function showPasswordChange()
    {
        return view('backend.pages.set-password');
    }

    public function storePasswordChange(Request $request)
    {
        $request->validate([
            // Same floor registration uses, so an invited member is not held to
            // a rule the sign-up form does not apply.
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = auth()->user();

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
            'must_change_password' => false,
        ])->save();

        flash(localize('Password set. Welcome aboard.'))->success();

        return redirect()->route('writebot.dashboard');
    }

    // total words chart

    private function totalWordsChart($time)
    {

        $timeline = 7; // 7, 30 or 90 days

        $timelineText = localize('Last 7 days');

        if ((int) $time > 7) {

            $timeline = (int) $time;

            if ($timeline == 30) {

                $timelineText = localize('Last 30 days');

            } else {

                $timelineText = localize('Last 3 months');

            }

        }

        $projects = Project::where('content_type', 'content')->where('created_at', '>=', Carbon::now()->subDays($timeline));

        if (isCustomer()) {

            $projects = $projects->where('user_id', auth()->user()->id);

        }

        $projectQueries = $projects->oldest();

        // Fetch once instead of re-querying the whole table on every loop iteration below.
        $projectCollection = $projectQueries->get();

        $totalWordsTimelineInString = '';

        $totalWordsAmountInString = '';

        for ($i = $timeline; $i >= 0; $i--) {

            $totalWordsAmount = 0;

            foreach ($projectCollection as $project) {

                if (date('Y-m-d', strtotime($i.' days ago')) == date('Y-m-d', strtotime($project->created_at))) {

                    $totalWordsAmount += $project->words;

                }

            }

            if ($i == 0) {

                $totalWordsTimelineInString .= json_encode(date('Y-m-d', strtotime($i.' days ago')));

                $totalWordsAmountInString .= json_encode($totalWordsAmount);

            } else {

                $totalWordsTimelineInString .= json_encode(date('Y-m-d', strtotime($i.' days ago'))).',';

                $totalWordsAmountInString .= json_encode($totalWordsAmount).',';

            }

        }

        $totalWordsData = new SystemSetting; // to create temp instance.

        $totalWordsData->labels = $totalWordsTimelineInString;

        $totalWordsData->words = $totalWordsAmountInString;

        $totalWordsData->totalWords = $projectQueries->sum('words');

        return [$totalWordsData, $timelineText];

    }

    // top 5 template chart

    private function topFiveTemplateChart()
    {

        $templates = Template::orderBy('total_words_generated', 'DESC')->take(5);

        $totalTemplateWordsCount = $templates->sum('total_words_generated');

        $templatesLabelsInString = '';

        $templateSeries = [];

        foreach ($templates->get() as $key => $template) {

            $templatesLabelsInString .= json_encode($template->collectLocalization('name'));

            if ($key + 1 != 5) {

                $templatesLabelsInString .= ',';

            }

            array_push($templateSeries, (float) $template->total_words_generated);

        }

        $totalTemplateWordsData = new SystemSetting; // to create temp instance.

        $totalTemplateWordsData->totalTemplateWordsCount = $totalTemplateWordsCount;

        $totalTemplateWordsData->series = json_encode($templateSeries);

        $totalTemplateWordsData->labels = $templatesLabelsInString;

        return $totalTemplateWordsData;

    }
}
