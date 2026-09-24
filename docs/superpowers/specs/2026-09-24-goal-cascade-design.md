# Cascading goals to direct reports (Features spec, Phase 8 — Notion Epic 3)

Notion Epic 3: "Mid-level managers can trigger OI on their specific mandate to
generate sub-goals and action items for frontline leads (L3/L4 execution)". The
mock-ups show "[Suggest Line-Item Actions for … Leads] [Cascade Downstream]".

Built end to end at the user's request; the decisions are recorded here.

## Decisions

- **Who can cascade, and to whom:** anyone with direct reports (users whose
  `manager_id` is them, in the same organization) can cascade:
  - a published role goal they can see (their "Your goals" card), or
  - a sub-goal cascaded to them, which gives L3 and L4 levels.

  Assignees can only be the cascader's own direct reports.
- **Suggest, then send.** "Suggest line-item actions for my team" asks the AI
  for one sub-goal per direct report, with name, role and department in the
  prompt and organization context only. The results are saved as drafts,
  visible only to the cascader.
  - Before sending, the cascader can edit each draft's wording, remove drafts,
    or add one by hand (pick a report, type a sub-goal).
  - "Send to my team" marks the drafts sent and alerts each assignee through
    `StrategyAlerts` (in-app and email).
  - Asking again replaces the unsent drafts for that goal; sent sub-goals are
    never touched.
- **Assignees** see a "Cascaded to you" section on the dashboard. Each entry
  shows the company goal, the parent goal, who cascaded it, and the sub-goal.
  They post progress (the same four statuses and %) on it, and can cascade it
  further if they have reports.
- **Cascaders** see each sent sub-goal's status under the goal it came from.
- **Executive view:** each deliverable line adds "N sub-goals cascaded (M
  completed)", counted across all levels under that goal.
- **What sub-goal progress doesn't change:** the drift index. It stays
  goal-level (Phase 6), and sub-goals are finer-grained execution telemetry.
  Recorded as a decision.
- **The AI suggest call** is throttled (10 per minute).

## Shape

```
goal_cascades(id,
  expected_state_id → expected_states (cascade)   -- the root role goal
  parent_id → goal_cascades null (cascade)          -- set when cascading a sub-goal
  created_by, assignee_user_id,
  text text, sent_at timestamp null,
  status string(20) null, pct tinyint null, note text null, progress_at timestamp null,
  timestamps)
```

## Endpoints

All of these are `POST` form posts that redirect back.

| Route | Request | Rule |
| --- | --- | --- |
| `my-goals.cascade.suggest` | `goal_id` or `cascade_id` | |
| `my-goals.cascade.add` | `goal_id` or `cascade_id`, `assignee_id`, `text` | the assignee must be a direct report |
| `my-goals.cascade.send` | `goal_id` or `cascade_id`, `texts[id]`, `remove[]` | only the cascader's own drafts |
| `my-goals.cascade.progress` | `cascade_id`, `status`, `pct`, `note` | the assignee only |

A goal or sub-goal the user can't cascade from, or a sub-goal that isn't
theirs, returns 404. A user with no direct reports gets 403 on suggest, add
and send.
