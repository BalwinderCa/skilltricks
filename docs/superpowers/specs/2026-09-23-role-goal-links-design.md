# Role goals linked to real roles (Features spec, Phase 2)

Every role goal a strategy produces is tied to one of the organization's own
roles, so later phases know whose dashboard each goal belongs on. Builds on
Phase 1 (`docs/superpowers/specs/2026-09-23-publish-gate-design.md`, branch
`feat/publish-gate`).

## Problem

Role goals are free text. Three prompts in `AiChatController` tell the model to
use "only role titles from the documents": the wizard variant
(`generate_strategy_variant`) and the older markdown paths
(`users_new_chat_update_strategy`, `users_new_chat_update_scenario`). The
system message meanwhile lists the organization's `org_roles` as
authoritative, so the two instructions disagree. The goals land in
`expected_states.role` as a string with no link to `org_roles`, so nothing can
tell which employees a goal is for.

## Decisions

- **The AI uses only the organization's roles.** When the author's
  organization has roles, all three prompts list them and require those exact
  names. An organization that only has the six default rank-named roles gets
  goals for those roles; the owner can add real job titles on the Roles page.
- **The link is stored separately from the AI's text.** `expected_states.role`
  keeps what the model wrote; `expected_states.org_role_id` is the link.
  Anything shown to other people (Phase 3) uses the org role's name.
- **Linking is automatic by name, with a manual override.** Unlinked goals are
  matched by name when the Publish card loads. The executive can change any
  goal's role from a dropdown, and that choice is never overwritten.
- **The review lives in the Publish card.** It is a "Who gets which goal"
  section above the resources table. The Action Table and OI flow are not
  touched.
- **Publishing needs every goal linked.** A strategy with no goals can still be
  published, as in Phase 1.
- **Links are final once published.** The same rule as published resources.

## Shape

```
expected_states
  + org_role_id  → org_roles, nullable, indexed
```

No backfill migration. Existing strategies are linked lazily the first time
their Publish card loads.

## Prompts

`DocumentContextService::roleNamesFor($user): array` returns the author's
organization's role names, ordered by name. Each name goes through the
existing `sanitiseForPrompt()`, and the list is capped at `ORG_MAX_ROSTER_LINES`
(60), matching the roster block. It returns `[]` when the user has no
organization or the organization has no roles.

When the list is non-empty, each of the three prompts replaces its "role
titles from the documents" rule with:

```
Use ONLY these role titles, exactly as written, one goal per role you choose:
<Role A>, <Role B>, ...
```

The JSON example in the variant prompt changes its placeholder from
`"Role title from documents"` to `"Role title from the list above"`. When the
list is empty, all three prompts keep today's wording.

## Linking

`App\Services\RoleGoalLinker`:

- `autoLink(SearchUserChat $chat): void` loads the chat's `expected_states`
  with `org_role_id` null. It compares each one's `role` with the author's
  organization's role names, normalised (trim, collapse whitespace,
  lower-case), and sets `org_role_id` on a match. It only reads roles from the
  author's organization, and never touches a goal that already has a link.
- `assign(ExpectedState $goal, int $orgRoleId, User $author): bool` sets the
  link when the role belongs to the author's organization, and returns false
  otherwise.

## Flow in the Publish card

1. **Load.** `show` calls `autoLink`, then returns the Phase 1 payload plus:
   - `goals`: `[{id, role_text, action, org_role_id, org_role_name}]` in id
     order;
   - `roles`: `[{id, name, member_count}]`, the author's organization's roles
     ordered by name, where `member_count` counts users in that organization
     with that `org_role_id`.
   Every other endpoint returns the same payload.
2. **Review.** The "Who gets which goal" section shows each goal's action, the
   AI's role text in grey, and a role dropdown.
   - 🔴 An unlinked goal shows "No matching role — pick one".
   - 🟠 A goal linked to a role with `member_count = 0` shows "Nobody holds
     this role yet". This is a warning only.
3. **Assign.** Changing a dropdown calls `POST users-new-chat-goal-role`
   (`chat_id`, `goal_id`, `org_role_id`) straight away. The strategy is
   re-read under a row lock (the Phase 1 `publishedUnderLock` helper) and the
   endpoint returns 409 if it is published.
4. **Publish.** `publish` also returns 422 "Link every goal to a role before
   publishing." when any of the chat's goals has `org_role_id` null. The
   check runs inside the existing publish transaction.
5. **Published.** The section is read-only and shows the org role names.

## Code layout

- `database/migrations/2026_09_23_000100_add_org_role_id_to_expected_states.php`
- `app/Services/RoleGoalLinker.php`
- `app/Models/ExpectedState.php`: `org_role_id` fillable, plus an `orgRole()`
  relation
- `app/Services/AI/DocumentContextService.php`: `roleNamesFor()`
- `app/Http/Controllers/Backend/AI/AiChatController.php`: the three prompts
- `app/Http/Controllers/Backend/AI/StrategyPublishController.php`: payload,
  `assignRole`, the publish rule
- `routes/backend.php`: one route
- `resources/views/backend/pages/aiChat/inc/publish-gate.blade.php`: the
  section

## Errors

| Case | Response |
| --- | --- |
| Not the author | 403 |
| Goal from another strategy | 422 |
| Role from another organization | 422 |
| Changing a role after publishing | 409 |
| Publishing with an unlinked goal | 422 |

## Testing

Feature tests (`tests/Feature/RoleGoalLinksTest.php`), with the AI mocked:

- For each of the three prompts: when the organization has roles, the
  captured prompt lists them and no longer says "from the documents"; with no
  roles, the old wording stays.
- `autoLink` matches case- and whitespace-insensitively, ignores a same-named
  role in another organization, and leaves a manually assigned goal alone.
- `assignRole` saves a valid choice and refuses another strategy's goal (422),
  another organization's role (422), someone else's strategy (403), and a
  published strategy (409).
- `publish` refuses while a goal is unlinked (422), succeeds once all goals are
  linked, and succeeds with no goals.
- The payload reports `member_count` 0 for a role nobody holds.
- The chat page still renders the card.

## Out of scope

Showing goals to the people who hold the role (Phase 3), one goal going to
several roles, and editing goal text.
