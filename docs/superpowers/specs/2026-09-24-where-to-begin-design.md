# Where to Begin and personal commitments (Features spec, Phase 4)

Each person who acts on a goal picks a concrete first step from AI-suggested
options. That pick is their commitment, and commitments add up to an
alignment rate per department. Builds on Phases 1–3.

Designed without live Q&A at the user's request; the calls are recorded
below.

## Decisions

- **Options are generated once per goal and shared.** They depend on the
  goal and role, not the person. They are stored on
  `expected_states.starting_options`, generated the first time anyone chooses
  "Act on it", and never regenerated automatically, so everyone chooses from
  the same list.
- **Generation happens when someone chooses "Act on it"**, inside that form
  post. It is one AI call of a few seconds. If it fails, the decision is still
  saved, a warning is flashed, and a "Suggest starting points" button retries.
- **Committing a starting point implies "Act on it".** It sets the decision to
  `act_on_it` if it wasn't already.
- **Changes are kept.** Changing your pick appends `{from, to, at}` to
  `goal_responses.starting_history`.
- **Alignment rate** is worked out per strategy, per department:
  - *people* = members of the strategy's organization whose `org_role_id`
    matches one of the strategy's linked goals;
  - *committed* = those who picked a starting point on their own role's goal;
  - *rate* = committed ÷ people, as a whole percent, or null when there are
    no people.
  - Members with no department are grouped as "No department".
- **Where alignment shows:** in the published Publish card (for the author),
  and in Phase 5's executive view. The existing OI drift engine is not
  changed. That is a ruling against the doc's "feed into existing progress and
  drift views": the OI engine tracks the author's per-role expected/observed
  states, not per-person commitments.
- **Carried from the Phase 3 review:** the obstacle input gets `required`, so
  an empty report no longer submits silently.

## Shape

```
expected_states
  + starting_options  json, nullable   -- ["option", ...] (2–4 items)

goal_responses
  + starting_point    string(255), nullable
  + committed_at      timestamp, nullable
  + starting_history  json, nullable   -- [{from, to, at}, ...]
```

## Prompt

The system message is `DocumentContextService::buildSystemMessage($member,
'You are StrategiStudio\'s execution guide. Return ONLY valid JSON. No
markdown, no code fences, no commentary.')`. The user prompt:

```
Company objective: "<company goal>"
Selected strategy path: "<selected_strategy>"
User role: "<org role name>"
User assigned goal: "<recommended_action>"

Based strictly on the assigned goal, generate 3 or 4 concise, highly
actionable starting options for "Where to begin", tailored to this role.
Output exactly: {"options":["...","..."]}
Each option under 15 words.
```

Values are flattened to one line. The reply is accepted when `options`
contains at least 2 non-empty strings. At most 4 are kept, each trimmed and
capped at 150 characters.

## Code

- `App\Services\StartingPoints`:
  - `ensureOptions(ExpectedState $goal, User $member): bool` returns true when
    the goal already has options or generation succeeded.
  - `commit(ExpectedState $goal, User $member, int $index): bool` returns
    false for an index outside the options.
- `App\Services\Alignment::forStrategy(SearchUserChat $chat): array` returns
  `{overall: {people, committed, rate}, departments: [{name, people,
  committed, rate}]}`, departments ordered by name.
- `MyGoals::companyGoal()` becomes public, because the prompt needs it.
- `MyGoalController` changes:
  - `decide` calls `ensureOptions` after an `act_on_it`;
  - new `suggestStart` (POST `goal_id`);
  - new `commit` (POST `goal_id`, `option` as an integer index).
  All three use Phase 3's visibility rule and return 404 otherwise.
- Routes: `my-goals.suggest` and `my-goals.commit`.
- The card shows the options as radio buttons, with the current pick checked
  and a "Commit" button, once the decision is `act_on_it`. When there are no
  options yet it shows "Suggest starting points" instead.
- The Publish card payload gains `alignment` when the strategy is published,
  shown as a small table.

## Errors

| Case | Response |
| --- | --- |
| Goal not visible | 404 |
| `option` missing, not an integer, or out of range | validation error redirect / flash error |
| AI failure | decision saved, flash warning, no options |

## Testing

`tests/Feature/WhereToBeginTest.php`, with the AI mocked:

- Act on it generates and stores options.
- A second person choosing Act on it on the same goal makes no AI call.
- AI failure keeps the decision, stores no options, and `suggestStart` retries
  successfully.
- Commit stores the pick, sets `act_on_it`, and records history on change but
  not on re-committing the same option.
- Out-of-range option and unseen goal are refused.
- Alignment counts people and commitments per department (including "No
  department"), ignores another role's commitments and other organizations,
  and gives a null rate for zero people.
- The published payload carries `alignment`; a draft's is null.
- The dashboard shows options after Act on it, and the obstacle input is
  `required`.

## Out of scope

Leader edits, alerts, and the executive view (Phase 5).
