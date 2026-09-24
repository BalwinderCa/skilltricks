# Leader edits and executive view (Features spec, Phase 5)

Leaders can refine the goals they hold as priorities shift, and everyone up the
reporting line to the strategy's author is alerted. Leaders also get one page
that shows how every published strategy in their organization is going. Builds
on Phases 1–4.

Designed without live Q&A at the user's request; the calls are recorded
below.

## Who is a leader

The org-chart rule proposed in the client doc and already implemented as
`OrganizationService::canPublish()`: the organization owner, a department head,
or anyone with a direct report. Phase 5 adds `isLeader()` as a clearer name for
the same check. `canPublish()` stays, delegating to it.

## Decisions

- **Leaders edit the wording directly.** The client doc says "chat box". The
  leader types the new goal wording plus an optional reason, rather than
  instructing an AI. That is deterministic, and there is no AI cost or failure
  mode. An AI-assisted rewrite can be added on top later.
- **Which goals:** a leader can edit the goals on their own "Your goals" card,
  meaning the published goals for their role. Everyone else stays read-only.
- **Edits are allowed after publishing.** This is the one deliberate exception
  to "published is final": the spec asks for it by name.
- **History:** every edit is a `goal_revisions` row (who, when, old, new,
  reason). The goal's OI `revised_*` fields are **not** touched. They mean
  "the author calibrated this in Review in Detail", and a leader's edit
  isn't that. The author sees leader edits on the executive page, which the
  Publish card links to. After publishing, the author's Action Table can no
  longer write a goal's wording back.
- **Starting points after an edit:** the goal's `starting_options` are cleared,
  so the next "Act on it" generates options for the new wording. Existing
  commitments are kept, since they are history, but the card marks a
  commitment made before the latest edit as "made before this goal changed".
- **Alerts:** in-app only, through the existing `saveNotification()` bell.
  - **Recipients:** the editor's manager, that manager's manager and so on up
    the chain, within the same organization. The walk stops at a loop or
    after 20 steps. The strategy's author is also alerted. Duplicates and the
    editor are removed.
  - **Content:** each alert links to the strategy's executive page.
- **Executive view:** `GET dashboard/strategies` and
  `GET dashboard/strategies/{chat}`, for leaders only. Anyone else gets 403,
  and a strategy outside the viewer's organization, or not published, gets
  404.
  - **List** (published strategies in the organization, newest first): company
    goal, strategy path, publisher and date, overall alignment, drift status,
    number of obstacles, and number of "Not viable" responses.
  - **Detail:**
    - alignment per department;
    - per goal: role, wording, people holding the role, committed count,
      decision breakdown, and latest drift;
    - obstacles, newest first;
    - goal revisions, newest first.
- **Drift status** reuses the OI engine's audit trail rather than recomputing
  it. For each goal, the latest `drift_events.drift_type` counts. The
  strategy shows "On track" when every goal's latest event is `None`, "Drift
  on N goals" otherwise, and "No drift recorded" when there are no events. The
  engine only records events once a goal has drifted. Events are
  recorded when the author views OI progress, so this status is as fresh as
  that.
- **Navigation:** leaders see an "Executive view" link on the "Your goals"
  card, and the Publish card of a published strategy links to its page.

## Shape

```
goal_revisions(id,
  expected_state_id → expected_states (cascade),
  user_id,
  old_text text, new_text text, reason text nullable,
  created_at)
```

## Code

- `OrganizationService::isLeader(User): bool`; `canPublish` delegates to it.
- `OrganizationService::managerChain(User): Collection<User>` walks up the
  chain within the organization, cycle-safe, capped at 20.
- `App\Services\GoalRevisions::revise(ExpectedState $goal, User $leader, string
  $text, ?string $reason): void`. It writes the revision, updates the goal's
  wording, clears `starting_options`, and sends the alerts.
- `MyGoalController::revise` (POST `goal_id`, `text` 1–500, `reason` ≤ 500)
  returns 404 for an unseen goal and 403 for a non-leader. Route
  `my-goals.revise`.
- `App\Services\StrategyOverview`:
  - `list(User): Collection`;
  - `detail(User, int $chatId): ?array`, returning null outside the viewer's
    organization or when not published.
- `App\Http\Controllers\Backend\StrategyOverviewController` has `index` and
  `show`; views are `backend/pages/strategies/index.blade.php` and
  `show.blade.php`. Routes `strategies.index` and `strategies.show`.

## Errors

| Case | Response |
| --- | --- |
| Non-leader opens the executive view or submits a revision | 403 |
| Strategy not published or in another organization | 404 |
| Revision of a goal the leader cannot see | 404 |
| Empty or over-long wording | validation redirect with errors (shown under the form) |

## Testing

- `isLeader` matches the existing `canPublish` cases, and `managerChain`
  stops at loops and at the organization boundary.
- A leader's revision updates the wording, writes history, leaves the OI
  `revised_*` fields alone, clears the options, and alerts the manager chain plus the author
  exactly once each, never the editor.
- A non-leader gets 403 on revise, and an unseen goal gets 404.
- The executive list shows only published strategies in the viewer's
  organization, with alignment, drift status and counts.
- The detail page shows per-goal decision counts, obstacles and revisions. It
  returns 404 for a draft or another organization's strategy, and 403 for a
  non-leader.
- Drift status: "No drift recorded", "On track", and "Drift on N goals" from
  seeded `drift_events`.
- The dashboard shows the revise form and the "Executive view" link to
  leaders only.
