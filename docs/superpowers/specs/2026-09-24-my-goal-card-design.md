# "My goal" dashboard card (Features spec, Phase 3)

When someone logs in, their dashboard shows their part of every published
strategy in their organization, and they can respond to it and report
obstacles. Builds on Phase 1 (publish gate) and Phase 2 (role goal links).

Designed without a live Q&A, because the user asked for all phases to be built
end to end. Each call below is recorded as a decision. They follow the client
doc's Phase 3 "Done when" checks.

## Who sees what

A goal (`expected_states` row) appears on a user's dashboard when all of these
hold:

- its strategy is `published`;
- the strategy's `organization_id` equals the user's `organization_id`;
- the goal's `org_role_id` equals the user's `org_role_id` and is not null.

The author sees their own goals like anyone else. Drafts never appear. A user
with no organization or no org role sees nothing, plus a one-line hint.

## Decisions

- **The "company goal" is the strategy's first question.** That is the oldest
  `search_user_chat_data.search` for the chat, falling back to the leadership
  brief's first line. The chat row has no goal column.
- **The "role goal" shown is `expected_states.recommended_action`.** That is
  the one-sentence action the role was given. The longer goal paragraph only
  exists inside AI message text and is not stored per role.
- **Team resources:** the strategy's resource row for the user's department.
  If there is none, the "Whole organization" row. If there is neither, the
  line is omitted.
- **Dependencies** reuse `expected_states.depends_on_id`. "Waiting on" is the
  goal this one depends on. "Waiting on you" is the goals that depend on this
  one. Each is shown with its org role name.
- **Per-person decision.** Act on it / Review in detail / Not viable is stored
  per person, so it is separate from the author's per-role OI decision. The
  author's Action Table and OI flow are untouched.
- **Everyone who sees a goal can report obstacles.** The spec's split between
  team members (feedback) and leaders (who can also edit) only adds the leader
  edit in Phase 5. In Phase 3 nobody can change a goal's text.
- **The author sees obstacles** in the Publish card of the published strategy:
  who reported each one, when, and on which goal. Phase 5's executive view
  aggregates them.
- **Plain HTML forms with redirect-back**, not a JS widget. The dashboard is
  server-rendered, and forms need no new JS.

## Shape

```
goal_responses(id,
  expected_state_id → expected_states (cascade),
  user_id,
  decision  string  -- act_on_it | review_in_detail | not_viable
  decided_at timestamp,
  timestamps)
  unique(expected_state_id, user_id)

goal_obstacles(id,
  expected_state_id → expected_states (cascade),
  user_id,
  body text,
  created_at)
```

Phase 4 adds the starting-point columns to `goal_responses`.

## Code

- `App\Services\MyGoals::for(User $user): Collection` returns one card per
  visible goal, newest strategy first. Each card holds the goal, strategy,
  company goal, org role name, team resource row, "waiting on" goal,
  "waiting on you" goals, the user's response, and the user's own obstacles.
- `App\Services\MyGoals::visibleGoal(User $user, int $goalId): ?ExpectedState`
  applies the same visibility rule to a single goal. Every write checks it.
- `App\Http\Controllers\Backend\MyGoalController` has two actions:
  - `decide` (POST `goal_id`, `decision` in the three values) upserts the
    user's `goal_responses` row;
  - `reportObstacle` (POST `goal_id`, `body` 1–2000 chars) creates a
    `goal_obstacles` row.
  Both redirect back with a flash message and return 404 for a goal the user
  cannot see.
- Routes `my-goals.decide` and `my-goals.obstacle` sit in the authenticated
  dashboard group.
- `resources/views/backend/pages/goals/my-goals.blade.php` is included at the
  top of the dashboard. `DashboardController::index` passes
  `myGoals = app(MyGoals::class)->for($user)`.
- The Publish card payload gains `obstacles:
  [{goal_id, role, user, body, at}]` when the strategy is published. The card
  lists them under "Who gets which goal".

## Errors

| Case | Response |
| --- | --- |
| Goal not visible to the user (draft, other org, other role, unknown id) | 404 |
| Invalid decision or empty/over-long obstacle | validation redirect back with errors |

## Testing

`tests/Feature/MyGoalCardTest.php`:

- A member whose role matches sees the published goal, including the company
  goal, the action, their department's resources, and dependencies.
- Draft strategies, other organizations and other roles are not shown.
- A user with no org role sees the hint and no cards.
- `decide` stores and then changes one row per person, and refuses a goal the
  user cannot see (404).
- `reportObstacle` stores who and what, validates length, and refuses an
  unseen goal.
- The author's Publish card payload lists obstacles once published, and not
  while in draft.

## Out of scope

Where to Begin and commitments (Phase 4); leader edits, alerts and the
executive view (Phase 5).
