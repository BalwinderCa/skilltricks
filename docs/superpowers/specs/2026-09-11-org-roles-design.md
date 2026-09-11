# Organization roles

Owner-managed roles, scoped to an organization, used wherever a role is picked or
shown in the dashboard.

## Problem

"Role" meant three different things: the IC→Board rank ladder
(`users.hierarchy_rank`), the chat taxonomy (`chat_role_categories`), and an
unused global spatie RBAC. An owner could not define a role of their own, and
nothing recorded what a role is allowed to do.

## Shape

A role is a name plus permissions, belonging to one organization.

```
org_roles(id, organization_id → organizations, name,
          can_read, can_write, timestamps)
          unique(organization_id, name)

users.org_role_id → org_roles, nullable
```

**Roles carry no seniority.** An earlier draft gave them a `level` that drove
`users.hierarchy_rank`, with a drag-and-drop Hierarchy page to set it. That was
cut: levels are deferred. The consequence is explicit — nothing in the UI sets
seniority now, so every member added (dialog or CSV) starts at the floor rung and
changing a role never moves a rank. `hierarchy_rank` still exists and still drives
the org chart's order, OI's "highest rank governs" election, and
`AiChatController::chatRoleIdFor()`; those keep working off the ranks members
already hold, they just stop changing.

## Backfill

Every existing organization is seeded six roles named after the current
`RANK_LABELS`, and each member is put on the role matching their rank's rung by
**name**. New organizations get the same six at creation, from
`OrganizationService::seedDefaultRoles()`.

The migration is idempotent — `firstOrCreate` on the table's unique key, and
members are only touched while `org_role_id` is null. It reads rank and never
writes it, so nobody's standing in the governance election moves.

## Surfaces

| Surface | Behaviour |
|---|---|
| Sidebar | "Roles" → `dashboard/organization/roles`, owner-only |
| Roles page | `Role \| Permission (Read/Write) \| Action`; toggles POST on change |
| Member dialog | Role `<select>` lists the org's roles, posts `org_role_id` |
| Roster / org chart | Show the role's name |
| CSV import | No role column (`name,email,department`); imported members arrive role-less |
| AI org context | The org's roles and departments, marked authoritative over documents |

## Components

- `OrgRole`; `Organization::roles()`, `User::orgRole()`.
- `ResolvesOwnedOrganization` — the owner guard extracted from
  `DashboardController::ownedOrganization()` so both controllers share one copy.
- `OrgRoleController` — index, store, update, destroy, togglePermission.

## Rules

- Every write is owner-only, through the trait.
- A role is always resolved scoped to the caller's organization.
- **Deleting a role that has holders is refused**, so nobody is silently
  unassigned.
- Role names are unique per organization, not globally.
- Role names reach the AI system prompt, so they are sanitised at render: a role
  named after the closing fence would otherwise escape the context block.

## Testing

`OrgRoleTest`, `OrgRoleBackfillTest` (idempotence, no rank moves),
`OrgContextInjectionTest` (roster renders uncalibrated; fence cannot be broken),
plus the existing Teams/import suites updated to the new contract.

## Out of scope

- read/write are stored, not enforced. Nothing checks them yet.
- Seniority/levels, deferred by request.
- spatie's roles/permissions stay untouched and unused.
- The organization owner still holds no role; the roster excludes owners by design.
