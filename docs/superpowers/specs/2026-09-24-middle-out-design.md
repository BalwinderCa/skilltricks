# Middle-out initiatives and the upstream approval gate (Features spec, Phase 9 — Notion Illustration 2)

Notion, "Illustration 2 – Mid Management": "A functional leader inputs an
operational initiative. The platform automatically scans active C-Suite goals,
measures semantic correlation, links the initiative to the matching corporate
priority, routes it through an upstream budget approval gate, and rolls the
outcomes back up into executive scorecards."

Built end to end at the user's request; the decisions are recorded here.

## Decisions

- **Which initiatives need approval.** A leader who is not the organization
  owner must link their initiative to a corporate priority, and get it
  approved, when the organization already has at least one C-suite priority.
  - A **C-suite priority** is a published strategy authored by the
    organization owner and not itself linked to a parent.
  - The owner always publishes directly. So does anyone in an organization
    with no C-suite priority yet, because there is nothing to roll up into.
    Phase 1 behaviour is unchanged in those cases.
- **Semantic correlation.** "Find the matching priority" asks the AI to score
  every candidate priority 0–100 against the initiative, with a one-sentence
  reason.
  - The AI sees each candidate's company goal and strategy path, and the
    initiative's company goal, path and role goals. Organization context only.
  - The highest-scoring valid candidate is linked, and its score and reason
    are stored.
  - The leader can override the choice from a dropdown; a manual pick stores
    no score.
  - The link is editable only while the initiative is a draft.
- **The approval gate.** Publishing a linked initiative sets its status to
  `pending_approval` and records the requester and time. It alerts the
  priority's author through `StrategyAlerts`.
  - Approvers are the priority's author or the organization owner.
  - **Approve:** the initiative becomes `published`, with `published_at` set to
    now and the requester kept as publisher. The decision is recorded, the
    requester is alerted, and the initiative's goals now reach people's
    dashboards.
  - **Reject:** the initiative returns to `draft` with a required note, and the
    requester is alerted. The card shows the note until the next submission.
  - Publishing still needs resources and linked goals, and those checks run at
    request time.
- **Locked while pending.** A strategy that is published *or* pending approval
  can't have its resources, role links, rankings or weights edited. The
  "published meanwhile" message becomes "published or sent for approval".
  Pending strategies are invisible to members, and have no drift index or
  alerts: every Phase 3–6 rule already keys on `published`.
- **Roll-up into the executive scorecards:**
  - The executive list shows "Supports: <priority>" under each initiative, and
    "N supporting initiatives" under each priority.
  - A priority's detail page has a "Supporting initiatives" section with each
    child's company goal, owner, alignment status badge, drift index and
    alignment.
  - The executive index has an "Awaiting your approval" section for approvers,
    with Approve and Reject (note) buttons.

## Shape

```
search_user_chat + parent_chat_id bigint null (index), correlation_score tinyint null,
                   correlation_reason string(300) null, approval_requested_at timestamp null,
                   approval_decided_by bigint null, approval_decided_at timestamp null,
                   approval_note text null
status values: draft | pending_approval | published
```

## Endpoints

| Route | Request | Responses |
| --- | --- | --- |
| `POST users-new-chat-match` | `chat_id` | throttled; author only; 409 if locked; 422 if there are no candidates; 502 on AI failure |
| `POST users-new-chat-parent` | `chat_id`, `parent_chat_id` | author only; 409 if locked; 422 if not a candidate |
| `POST strategies.approve` | `{chat}` | approvers only (403); 404 unless pending in the viewer's organization |
| `POST strategies.reject` | `{chat}`, `note` | same as approve |
