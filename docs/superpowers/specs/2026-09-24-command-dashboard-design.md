# Executive command dashboard (Features spec, Phase 6 — Notion Epic 4)

Adds execution telemetry and Notion's "Live Strategic Drift Engine" on top of
Phases 1–5:

- people post progress and flag bottlenecks on their goals;
- each published strategy gets a drift index (5% / 15%) with automatic nudges
  and a red-alert card offering AI-suggested recourse;
- predicted completion and outcome;
- the command tiles: teams involved, alignment effort saved, and drift index;
- departmental deliverables and the upstream-blocker alert;
- alerts go out by email as well as in-app.

Built end to end at the user's request, without live Q&A; the decisions are
recorded below.

## Decisions

- **Progress updates.** On the "Your goals" card, anyone who chose "Act on it"
  can post a status (Not started, In progress, Completed, or Blocked, which
  flags a bottleneck), a progress % (0–100) and an optional note. The latest
  values are kept on their `goal_responses` row, and every update is kept in
  `goal_progress_updates`. "Completed" counts as 100%.
- **Notion's drift formula, per goal:**
  - Expected baseline % = the elapsed share of the goal's window, from the
    strategy's `published_at` to the goal's `target_date`.
  - Observed % = the average progress % of the goal's holders who have
    reported (0 if nobody has).
  - Drift % = max(0, (Expected − Observed) ÷ Expected × 100).
  - Goals with no target date are not measured.
  - **A goal only counts once 10% of its window has passed.** Without this
    grace period, the formula turns every strategy red on day one (Expected 2%,
    Observed 0 gives 100% drift).
- **Drift index per strategy:** the average drift of its measured goals.
  Green below 5%, yellow 5–15%, red above 15% (Notion's thresholds).
- **Threshold actions**, fired once each time the level changes:
  - **Yellow:** a nudge to everyone holding the worst-drifting goal (Notion:
    "flags specific bottleneck role and sends automated nudge").
  - **Red:** an alert to the strategy's author, plus a red-alert card on the
    executive page. The card has an AI-suggested recourse button (on demand,
    to control cost). The recourse prompt uses organization context only,
    never anyone's private documents.
  - Returning to green re-arms the alerts.
- **When drift is recalculated:** after every progress update (commitments
  don't change it, because drift is progress-based),
  whenever the executive view loads, and daily by `strategies:evaluate-drift`
  (registered in `routes/console.php`; the server needs `schedule:run` in cron
  for the daily run).
- **Predicted outcome, calculated from pace, not by the AI:**
  - Projected completion = published_at + elapsed time × 100 ÷ observed %.
    Null until someone reports progress.
  - Projected outcome = numeric target value × min(1, observed ÷ expected),
    shown as "Target 25 vs projected 20 at current pace". Only for numeric
    targets, and only after the 10% grace period.
  - The strategy's projected completion is the latest of its goals'.
- **Alignment effort saved.** Notion's formula, using assumptions the owner can
  edit, stored in `organizations.command_settings`:

  | Assumption | Default |
  | --- | --- |
  | Blended hourly rate | 120 |
  | Manual alignment hours per participant | 6 |
  | OI minutes per participant | 30 |
  | Cost per 1,000 AI tokens | 0.02 |

  - Participants = the author plus everyone holding one of the strategy's
    goals.
  - Manual cost = participants × hours × rate.
  - OI cost = participants × minutes ÷ 60 × rate + tokens ÷ 1000 × cost, where
    tokens = the strategy's `total_tokens`.
  - Savings = manual − OI; hours saved = participants × (hours − minutes ÷ 60).
  - Always labelled "estimate".
- **Teams involved** = departments with at least one goal holder, out of the
  organization's departments. **Active contributors** = distinct people who
  have responded or posted progress.
- **Deliverables:** one line per goal, showing role, latest note, status, and
  "N days behind baseline" = (expected − observed) ÷ 100 × window length in
  days.
- **Upstream blockers:** a goal that someone flagged Blocked and that other
  goals depend on. The alert names them, otherwise it reads "Zero upstream
  blockers detected across teams."
- **Two separate status measures:** the Phase 5 alignment badge now also counts
  a goal someone flagged Blocked as a critical bottleneck. The alignment badge
  ("Status") and the drift index are different measures and are labelled that
  way.
- **Email:** a new `StrategyAlerts` service sends every alert in-app and by
  email (the `EmailManager` mailable, view `emails.strategy-alert`), including
  Phase 5's goal-revision alerts. A failed email is logged and never blocks
  anything.

## Shape

```
goal_responses        + progress_status string(20) null, progress_pct tinyint null,
                        progress_note text null, progress_at timestamp null
goal_progress_updates   (id, expected_state_id → cascade, user_id, status, pct, note, created_at)
search_user_chat      + drift_index decimal(6,2) null, drift_level string(10) null,
                        drift_alerted_level string(10) null, drift_checked_at timestamp null,
                        recourse json null
organizations         + command_settings json null
```

## Endpoints

| Route | Who | Does |
| --- | --- | --- |
| `POST my-goals.progress` (`goal_id`, `status`, `pct`, `note`) | goal holders | 404 if the goal isn't visible to them |
| `POST strategies.recourse` (`chat`) | leaders | generates and caches recourse options |
| `POST strategies.settings` | organization owner | saves the four assumptions |

## Testing

Feature tests for:

- progress recording and history;
- drift math with Carbon time travel: grace period, levels, a goal with no
  target date, and Completed counting as 100;
- index persistence and level changes;
- a nudge on yellow to the bottleneck role's holders, and an alert on red to
  the author; one alert per level, re-armed after green; email sent through
  `Mail::fake`;
- the recourse endpoint (mocked AI, leaders only, cached);
- the settings endpoint (owner only, validated);
- savings and teams math;
- deliverables and the blocker alert on the page;
- the scheduled command.
