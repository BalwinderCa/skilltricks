<?php

namespace App\Services;

use App\Models\ExpectedState;
use App\Models\OrgRole;
use App\Models\SearchUserChat;
use App\Models\User;

/**
 * Ties a strategy's role goals to the organization's own roles: by name when
 * the model wrote a role that exists, by hand when the executive picks one.
 */
class RoleGoalLinker
{
    /** Link unlinked goals whose role text names one of the author's organization's roles. */
    public function autoLink(SearchUserChat $chat): void
    {
        $orgId = User::whereKey($chat->user_id)->value('organization_id');
        if (! $orgId) {
            return;
        }

        $roles = OrgRole::where('organization_id', $orgId)->get(['id', 'name'])
            ->keyBy(fn (OrgRole $r) => self::normalise($r->name));

        ExpectedState::where('search_user_chat_id', $chat->id)->whereNull('org_role_id')->get()
            ->each(function (ExpectedState $goal) use ($roles) {
                $role = $roles->get(self::normalise((string) $goal->role));
                if ($role) {
                    $goal->update(['org_role_id' => $role->id]);
                }
            });
    }

    /** Link a goal to a role, only when that role belongs to the author's organization. */
    public function assign(ExpectedState $goal, int $orgRoleId, User $author): bool
    {
        if (! $author->organization_id
            || ! OrgRole::whereKey($orgRoleId)->where('organization_id', $author->organization_id)->exists()) {
            return false;
        }

        $goal->update(['org_role_id' => $orgRoleId]);

        return true;
    }

    /**
     * Compare names the way the prompt shows them: DocumentContextService's
     * sanitiser collapses whitespace and "---" runs, so the model can only
     * echo that form back.
     */
    public static function normalise(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s*-{3,}\s*/', ' ', preg_replace('/\s+/', ' ', $name))));
    }
}
