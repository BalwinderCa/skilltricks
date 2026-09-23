# Publish gate (Features spec, Phase 1)

A strategy stays private to its author until a leader commits resources and
publishes it to their organization. This is the first of five phases in the
client's "Features" spec; later phases (role linking, "My goal" cards,
commitments, executive view) read what this phase publishes.

## Problem

Every strategy (`search_user_chat`) is private: each query in
`AiChatController` filters on `user_id`. The spec needs an executive to publish
a strategy so the rest of the organization can see it, and to commit budget,
people and tools before doing so. Nothing records organization, publish state,
or committed resources today. The only resource data is the per-role boolean
`expected_states.resources_committed`.

## Decisions

- **Extend the strategy record, don't copy it.** The spec proposes a new
  `initiatives` table; the wizard output is already saved on `search_user_chat`
  and `expected_states`, so Phase 1 adds columns there plus two small tables.
- **Publishers: the organization owner and org-chart leaders.** A leader is a
  department head (`departments.head_user_id`) or anyone with a direct report
  (`users.manager_id`). No seniority levels are involved.
- **Only the author publishes their own strategy.** A non-leader's strategy
  stays Draft; handing one to a leader is a later feature.
- **Published is final.** No unpublish. Resource amounts can still be amended
  after publishing, and every amendment is logged.
- **The existing flow is untouched.** Wizard steps, the Action Table, and the
  OI "Act on it" flow keep working on Draft strategies exactly as today.

## Shape

```
search_user_chat
  + organization_id  → organizations, nullable, indexed
  + status           string, default 'draft'   ('draft' | 'published')
  + published_by     → users, nullable
  + published_at     timestamp, nullable

strategy_resources(id,
  search_user_chat_id → search_user_chat (cascade),
  department_id       → departments, nullable (null = "Whole organization"),
  department_name     string   -- snapshot, survives a department rename/delete
  budget              decimal(12,2), nullable
  fte                 decimal(6,2), nullable
  tools               text, nullable
  notes               text, nullable
  ai_suggestion       json, nullable  -- the AI's original {budget, fte, tools, rationale}
  timestamps)

strategy_resource_changes(id,
  strategy_resource_id → strategy_resources (cascade),
  user_id              → users,
  field                string   -- budget | fte | tools | notes
  old_value            text, nullable
  new_value            text, nullable
  created_at)
```

`search_user_chat.id` differs in type between MySQL and SQLite; the foreign key
column copies the driver check already used in
`2026_06_22_000000_create_expected_states_table.php`.

The migration backfills `organization_id` from each author's
`users.organization_id`. Every existing row stays `draft`.

Budget is a plain number shown with the app's default currency symbol; no
per-organization currency.

## Who can publish

`OrganizationService::canPublish(User $user): bool` returns true when any of:

- the user is `organizations.owner_user_id` of their organization;
- a department in their organization has `head_user_id = user.id`;
- any user in their organization has `manager_id = user.id`.

A user with no organization cannot publish. The UI uses it to decide what to
show, and the publish endpoint enforces it.

## Flow

The card appears under the Leadership Alignment Brief, after Finish.

1. **Load.** `GET users-new-chat-resources/{chat}` returns status, publisher,
   rows, change history, and `can_publish`.
2. **Suggest.** When a Draft has no rows, the card calls
   `POST users-new-chat-resources-suggest`. The server builds the prompt from
   saved data only: `selected_strategy`, `selected_scenario`, the
   `expected_states` rows (role + action), `leadership_brief`, and the
   organization's departments. The model returns
   `{"rows":[{"department","budget","fte","tools","rationale"}]}`. Rows are
   matched to departments by name; unmatched names are dropped. An organization
   with no departments gets one "Whole organization" row. Existing Draft rows
   are replaced ("Regenerate suggestions" calls the same endpoint). If the chat
   has neither role goals nor a brief, it returns 422 "Finish the wizard first".
3. **Edit (Draft).** `POST users-new-chat-resources-save` replaces the chat's
   rows with the submitted set (add, edit, remove). Nothing is logged in Draft.
4. **Publish.** `POST users-new-chat-publish` checks ownership, `canPublish`,
   `status = draft`, and at least one row. In one transaction it sets `status`,
   `published_by`, `published_at`, and `organization_id` (re-read from the
   author, in case the backfill left it null). The card becomes read-only:
   "Published by <name> on <date>".
5. **Amend (Published).** The same save endpoint, but only the publisher may
   call it. It may edit existing rows but not add or remove them, and it writes
   one `strategy_resource_changes` row per changed field inside the same
   transaction. The card shows the change history under the table.

Non-leaders see the card with editable Draft rows and a disabled Publish button:
"Only department heads, managers and the organization owner can publish. This
strategy stays private to you."

## Code layout

- `app/Http/Controllers/Backend/AI/StrategyPublishController.php` holds the
  four actions (`show`, `suggest`, `save`, `publish`). It is kept out of
  `AiChatController`, which is already 2,391 lines.
- Routes sit next to the other `users-new-chat-*` routes in
  `routes/backend.php`.
- Models: `StrategyResource`, `StrategyResourceChange`; `SearchUserChat` gains
  the new fillable columns, `resources()`, `isPublished()`.
- UI goes in its own partial, `resources/views/backend/pages/aiChat/partials/publish-gate.blade.php`,
  holding the card's markup and script. It is included from
  `users-new-chat.blade.php` and mounted where the brief renders. Brand
  colours are teal `#36839b` and orange `#ec883f`; no purple.
- Ownership checks use the direct query
  `SearchUserChat::where('id', $id)->where('user_id', $user->id)->exists()`,
  not relation + `!==`, which 403s spuriously on MySQL.

## Errors

| Case | Response |
| --- | --- |
| Not the author | 403 |
| Publish by a non-leader | 403 |
| Publish when already published | 409 |
| Publish with no rows | 422 |
| Suggest before the wizard is finished | 422 |
| Suggest on a published strategy | 409 |
| Amend by anyone but the publisher, or adding/removing rows after publish | 403 / 422 |
| Negative budget or FTE | 422 (validation) |
| AI failure or unparseable JSON | 502 with a message; the card offers Retry and pre-fills one empty row per department for manual entry |

## Testing

Feature tests (`tests/Feature/PublishGateTest.php`) mock `AiProviderService`,
as `OnboardingAgentTest` does:

- suggest saves one row per matching department with `ai_suggestion`, drops
  unknown departments, and falls back to "Whole organization";
- suggest returns 422 on an unfinished chat;
- `canPublish`: owner, department head and manager-with-reports are true;
  a plain member and a user with no organization are false;
- publish: leader succeeds and stamps `published_by`/`published_at`; plain
  member 403; another user's chat 403; second publish 409; no rows 422;
- Draft save writes no change rows; Published amend writes one per changed
  field and refuses adding or removing rows;
- the migration leaves existing chats `draft` with `organization_id` filled.

SQLite tests miss MySQL-only type mismatches, so the flow is also checked by
hand on staging (as a leader and as a plain member) before it is called done.

## Out of scope

Linking role goals to real roles (Phase 2), showing published strategies to
anyone else (Phase 3), per-person commitments (Phase 4), leader edits, alerts
and the executive view (Phase 5), and unpublishing.
