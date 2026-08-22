# Dynamic AI Onboarding & Organizational Context Calibration — Design

**Date:** 2026-08-22
**Project:** SkillTricks / StrategiStudio Engine
**Status:** Approved for planning

## 1. Problem

Registration currently ends at a hard gate: a customer cannot reach any part of
StrategiStudio until eight profile fields are filled in
(`app/Http/Controllers/Backend/DashboardController.php:89`, mirrored in
`resources/views/backend/inc/userSidebarMenus.blade.php:12`). Failing the gate
redirects to `dashboard/profile`.

Two things are wrong with that gate:

1. It is tedious paperwork at the worst possible moment — before the user has
   seen any value.
2. **The data it collects is never used.** No prompt-building path reads
   `company_name`, `about_company`, `number_employess`, or `company_category`.
   `users_new_chat_ask()` assembles its system message from uploaded documents
   and per-request `additional_context` only
   (`AiChatController.php:716-726`). Users are gated behind a form that feeds
   nothing.

Separately, the platform has no concept of an organization. There is no
`organizations` table and no `organization_id` column anywhere. Organizational
identity today is the free-text string `users.company_name`. There is likewise
no hierarchy rank: `users.chat_role_categories` is a free-text value chosen
from a flat, unordered list in the `chat_role_categories` table.

The client requirement (§3 of the feature brief) is built entirely on an
"Organization ID" and a "User Hierarchy Rank" that do not exist yet.

## 2. Outcome

Replace the static gate with a 3-to-4 turn LLM interview that produces a
**versioned organizational context baseline**, scoped to a verified
organization, ranked by the declaring user's seniority — and then actually feed
that baseline into every StrategiStudio prompt.

## 3. Decisions

### 3.1 Organization identity: verified email domain

Two users belong to the same organization when they share an email domain.
`acme.com` is one organization. Users on free consumer domains each get a
private single-seat organization.

Rationale: a domain is a real trust boundary — you cannot join an organization
whose email you cannot receive. Matching on `company_name` was rejected: any
user could type another company's name and read or overwrite its active
strategic context.

Free-domain list (a config array, not a table — it changes rarely and is not
user data): `gmail.com`, `googlemail.com`, `outlook.com`, `hotmail.com`,
`live.com`, `yahoo.com`, `yahoo.co.uk`, `icloud.com`, `me.com`, `aol.com`,
`proton.me`, `protonmail.com`, `gmx.com`, `mail.com`, `yandex.com`,
`zoho.com`, `qq.com`, `163.com`. A user on any of these gets an organization
keyed to their full email address rather than the bare domain, so they are
isolated from each other.

### 3.2 Hierarchy rank: self-declared, audited, owner-correctable

The onboarding agent maps the user's stated role onto an integer ladder seeded
onto the **existing** `chat_role_categories` table:

| Band | Rank |
|---|---|
| Individual contributor / analyst | 10 |
| Manager / team lead | 20 |
| Director / senior manager | 30 |
| VP / head of function | 40 |
| C-Suite | 50 |
| Board | 60 |

Every rank claim is stored with its declaring user and timestamp in the version
record. The first user to register on a domain becomes that organization's
`owner_user_id` and can correct any member's rank from the profile page.

Rejected alternatives: an approval queue (blocks a real CEO's calibration
behind a junior's approval, and adds a pending state, an inbox, and
notifications); no guard at all (any employee typing "CEO" silently redefines
strategy context for the whole company with no trail); admin-assigned ranks
(does not scale to self-serve registration).

Within a domain-verified organization the residual risk is a colleague
overstating seniority — an internal trust problem, correctable by the owner,
with the full history preserved either way.

### 3.3 In-flight interview state lives in the session

Turns 1–3 are held in the Laravel session. A database row is written only on
confirmation. This removes a state machine, an abandoned-session cleanup job,
and a table.

This satisfies the brief's persistence rule, which concerns *context inputs
across hierarchy levels* — that is, confirmed baselines. An abandoned interview
has produced no baseline to lose.

### 3.4 When each field is assigned

`users.organization_id` is resolved and written **at registration**, from the
email domain. `users.hierarchy_rank` is written **on interview confirmation**.

Splitting them this way makes `owner_user_id` deterministic — the first user to
register on a domain owns it, regardless of who finishes calibrating first — and
leaves `hierarchy_rank` as the field the gate in §6.1 actually turns on.

## 4. Schema

Four schema migrations, following the existing `hasTable`/`hasColumn`-guarded
style used throughout `database/migrations/`. A fifth, data-only backfill
migration is described in §9.

### 4.1 `organizations`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `domain` | string, unique | `acme.com`, or a full address for free-domain users |
| `name` | string, nullable | seeded from the first member's `company_name` |
| `owner_user_id` | bigint, nullable, FK `users.id` | first registrant on the domain |
| `active_context_id` | bigint, nullable | points at the governing `org_context_versions` row |
| `created_at` / `updated_at` | timestamps | |

`active_context_id` carries no FK constraint, to avoid a circular dependency
between the two tables at migration time. It is set only to ids this
application writes.

### 4.2 `org_context_versions` — append-only

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `organization_id` | bigint, FK, indexed | |
| `user_id` | bigint, FK, indexed | who declared this context |
| `rank` | integer | the declaring user's rank at declaration time |
| `profile` | json | the structured extraction (see §6.4) |
| `transcript` | json | the full 4-turn exchange, verbatim |
| `created_at` | timestamp | |

**Rows in this table are never updated and never deleted.** No `updated_at`, no
soft deletes, no update path in the model. This is the brief's "Full Input
Persistence" rule expressed as a schema constraint rather than a convention.

This mirrors the `search_user_chat` / `search_user_chat_data` split the codebase
already uses: one active pointer, an immutable history behind it. Same pattern,
no new concept for the team to learn.

### 4.3 `users` — two columns

- `organization_id` — bigint, nullable, FK, indexed
- `hierarchy_rank` — integer, nullable

Both nullable: they are the new gate condition, and null means "not yet
calibrated".

### 4.4 `chat_role_categories.rank`

An integer column on the existing table, seeded per §3.2. Reusing this table
avoids introducing a second, competing notion of "role" alongside the one the
profile form already writes to `users.chat_role_categories`.

## 5. The cascade rule

On confirmation:

1. **Always** insert an `org_context_versions` row. Unconditionally, regardless
   of rank. This is the persistence guarantee.
2. Then compare against the currently active version's rank:
   - `rank >= active rank` → repoint `organizations.active_context_id` at the
     new row.
   - `rank < active rank` → store only. The active baseline is untouched.
3. If the organization has no active version yet, the new row becomes active.

Ties (`>=` rather than `>`) resolve in favour of the more recent declaration, so
a second VP refreshing a stale baseline works without an escalation path.

This is the brief's §3 — full persistence, upward review, top-down active
overwrite — in one comparison.

## 6. Interview flow

### 6.1 Gate change

`DashboardController.php:89` changes from the eight-field check to:

```php
$user->organization_id && $user->hierarchy_rank
```

redirecting to `dashboard/onboarding` instead of `dashboard/profile`. The same
substitution applies at `userSidebarMenus.blade.php:12`.

The profile page and its eight fields remain reachable and editable. Only its
role as the gate is removed.

### 6.2 Turn 1 — static seed, no LLM call

The universal prompt from the brief's Rule 3 is a constant. It is rendered
directly; no round trip is paid for it.

> "To help SkillTricks anchor its intelligence in your daily reality: What is
> your current role, and what specific team or area of the organization do you
> directly drive or influence?"

### 6.3 Turns 2–3 — one `AiProviderService::generate()` call each

System prompt is the brief's §4 agent instructions verbatim, plus the behavioural
rules (one question per response, never a list, adapt to prior answers).

When the organization already has an active context, that context is included
in the system prompt with an instruction to have the user *review and refine*
it rather than start blank. This is the brief's "Upward Review" — a later,
higher-ranked user sees the existing draft.

### 6.4 Turn 4 — one JSON-mode call

`generate($system, $prompt, 1200, 0.4, jsonMode: true)` returning:

```json
{
  "role": "string",
  "rank": 10|20|30|40|50|60,
  "scale": "string",
  "governance": "string",
  "frictions": ["string"],
  "summary_bullets": ["string"]
}
```

Rendered as the confirmation card with the primary action
**[Confirm & Begin Strategic Mapping]**.

`AiProviderService::parseJson()` already exists and returns `null` on failure.
On `null`, the user is shown a retry of turn 4 rather than a broken card; two
consecutive failures fall back to a minimal form capturing role and rank only,
so a provider outage cannot permanently lock a user out of the platform.

**Total: three LLM calls per onboarding, not four.**

## 7. Context injection — the payoff

`App\Services\AI\DocumentContextService::buildSystemMessage($user)` is already
the single chokepoint feeding six StrategiStudio prompts
(`AiChatController.php:528, 633, 842, 933, 1162, 1280`). It gains an
`--- ORGANIZATIONAL CONTEXT ---` block built from the user's organization's
active context version, prepended ahead of the document block.

`users_new_chat_ask()` (`AiChatController.php:716`) is the one call site that
hand-rolls its own system message instead of using the chokepoint. It is routed
through `buildSystemMessage()` as part of this work, so a single method governs
org context for every prompt in the engine and no call site can drift again.

A user with no organization context yields an empty block, exactly as
`additionalContextBlock()` does today.

**Note:** this step is not in the client brief. It is what makes the feature pay
off — without it the work replaces an inert form with an inert chat.

## 8. Owner rank override

The profile page gains an org-members panel, visible only to
`organizations.owner_user_id`: member name, current rank, and a rank dropdown.
Changing a rank writes `users.hierarchy_rank` and re-evaluates
`active_context_id` under the §5 rule.

One blade partial, one controller method. No approval queue, no notifications.

## 9. Backfill of existing users

Users with a complete eight-field profile are migrated, not re-interviewed:

- organization resolved from their email domain (created if absent)
- `hierarchy_rank` mapped from their existing `chat_role_categories` value via
  the ladder seeded in §4.4; a value matching no seeded category maps to rank
  10, the individual-contributor floor, so a backfilled user can never
  accidentally outrank a calibrated one
- a synthetic `org_context_versions` row built from `company_name`,
  `company_address`, `number_employess`, `company_category`, and
  `about_company`, with `transcript` set to `null` to mark it as backfilled
  rather than interviewed

Users with an incomplete profile are left with null `organization_id` and are
routed into the interview on next login.

Owner assignment during backfill: the earliest-registered user in each
organization, by `users.id`.

## 10. Out of scope

- **Perception-gap analytics.** The brief states inputs are preserved so gaps
  can be measured *later*. This work stores the data; it does not build the
  analysis or any dashboard for it.
- Invite flows and organization switching.
- An organization admin panel beyond the §8 rank dropdown.
- Any rank approval or escalation workflow.

## 11. Testing

Feature tests in the existing `tests/Feature` style:

- a lower-ranked confirmation does **not** repoint `active_context_id`
- a lower-ranked confirmation **does** still insert its version row
- a higher-ranked confirmation repoints the active pointer, and the superseded
  row remains readable
- equal rank repoints (tie resolves to the newer declaration)
- two organizations on different domains cannot read each other's context
- two free-domain users on `gmail.com` land in separate organizations
- `buildSystemMessage()` includes the active context for a calibrated user and
  omits the block entirely for an uncalibrated one
- the dashboard gate redirects an uncalibrated customer to `dashboard/onboarding`

Unit test: the domain-to-organization resolver, including free-domain handling
and case/whitespace normalisation.

## 12. Files touched

| Path | Change |
|---|---|
| `database/migrations/` | 4 new migrations + 1 backfill migration |
| `app/Models/Organization.php` | new |
| `app/Models/OrgContextVersion.php` | new |
| `app/Services/OrganizationService.php` | new — domain resolution, cascade rule |
| `app/Services/AI/OnboardingAgentService.php` | new — turn orchestration, prompts |
| `app/Http/Controllers/Backend/OnboardingController.php` | new |
| `app/Models/User.php` | `organization_id`, `hierarchy_rank` |
| `app/Http/Controllers/Backend/DashboardController.php` | gate condition (line 89) |
| `app/Services/AI/DocumentContextService.php` | org context injection |
| `app/Http/Controllers/Backend/AI/AiChatController.php` | route line 716 through the chokepoint |
| `resources/views/backend/inc/userSidebarMenus.blade.php` | gate condition (line 12) |
| `resources/views/backend/pages/onboarding.blade.php` | new |
| `resources/views/backend/pages/partials/org-members.blade.php` | new |
| `routes/backend.php` | onboarding routes, alongside the existing chat block |
| `config/` | free-domain list |

## 13. Risks

1. **Rank overclaiming.** Mitigated by domain verification, the audit trail, and
   owner override (§3.2). Accepted.
2. **A domain shared by unrelated entities** (a shared agency domain, a holding
   company) collapses distinct businesses into one organization. Not addressed;
   surfaces as a support request, resolved by the invite flow deferred in §10.
3. **Cost.** Three LLM calls per registration, on the registration path. Worth
   watching if signup volume grows; turn 1 is already free.
4. **Provider outage during onboarding** would block registration entirely. The
   §6.4 minimal-form fallback bounds this.
