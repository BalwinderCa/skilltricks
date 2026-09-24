# Impact ranking and action weights (Features spec, Phase 7 — Notion Epic 1)

Notion Epic 1 asks for high-leverage actions "mathematically ranked by their
potential impact on realizing the parent intent", and for leadership to be able
to "adjust action weights … before locking the draft". Notion's own developer
directive forbids changing the wizard's prompt chains, so all of this happens in
the Publish card, after the wizard, before publishing.

Built end to end at the user's request; the decisions are recorded here.

## Decisions

- **Ranking:** "Rank by impact" in the draft Publish card asks the AI to score
  every role goal from 1 (marginal) to 10 (decisive), with a one-sentence
  reason, relative to each other.
  - The prompt uses the company goal, strategy path, scenario and the goals
    only, plus organization context. No private documents.
  - Scores outside 1–10 and unknown goal ids are ignored.
  - Re-ranking replaces scores and reasons but keeps any weight a leader set.
- **Weights:** 1–5 per goal. The default is ceil(score ÷ 2), set when the goal
  is first scored. The author can change a weight from a dropdown until the
  strategy is published; after that weights are locked (409), like role links.
- **Ranked display:** the card lists goals by impact, highest first, once any
  are scored, with "8/10" and the reason on hover. The executive detail shows
  each goal's impact score and weight.
- **What weights change:** the Phase 6 drift index becomes a weighted average
  (Σ drift × weight ÷ Σ weight, with an unweighted goal counting as 1). Alignment
  stays people-based and unweighted.

## Shape

```
expected_states + impact_score tinyint null, impact_reason string(300) null, weight tinyint null
```

## Endpoints

| Route | Request | Responses |
| --- | --- | --- |
| `POST users-new-chat-rank-goals` | `chat_id` | author only; 409 if published; 422 with no goals; 502 on AI failure |
| `POST users-new-chat-goal-weight` | `chat_id`, `goal_id`, `weight` 1–5 | author only; 422 for another strategy's goal; 409 if published |
