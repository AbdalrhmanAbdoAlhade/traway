# Phase 2 — Authorization Architecture

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Complete — awaiting approval (`APPROVE PHASE 3`)
**Inputs:** [`USER-ROLES-AND-PERMISSIONS.md`](./USER-ROLES-AND-PERMISSIONS.md), [`FEATURES-CATALOG.md`](./FEATURES-CATALOG.md), [`BUSINESS-LOGIC.md`](./BUSINESS-LOGIC.md), [`DB-DECISION-BRIEF.md`](./DB-DECISION-BRIEF.md)
**Companion:** [`ROLE-PERMISSION-SCOPE-MATRIX.md`](./ROLE-PERMISSION-SCOPE-MATRIX.md)
**Date:** 2026-11-23

---

## 1. Purpose and scope

This document defines the authorization model required by the platform: platform staff, company owners, hotel owners, service owners, employees and customers, with granular, operation-level permissions, organization isolation, delegated administration and audit.

It answers five questions:

1. **Who** can act (identity, membership, role).
2. **What** they can do (permission catalog).
3. **Where** the action applies (platform, organization, service, record ownership).
4. **How** the decision is enforced (middleware, policy, service, query, database).
5. **How** privilege is contained (grant ceilings, scope ceilings, lifecycle rules, audit).

It deliberately separates three concepts that are often conflated:

| Concept | Question | Example |
|---|---|---|
| **Permission** | What operation is allowed? | `bookings.cancel` |
| **Scope** | On which data may it be performed? | Own hotel's bookings only |
| **Grant authority** | Who may give this permission to someone else? | Owner, within their own ceiling |

A permission without a scope rule is incomplete, and a scope without a permission is unauthorised. Every endpoint must evaluate both.

---

## 2. Design principles

| # | Principle | Consequence |
|---|---|---|
| AP-01 | Deny by default | No permission → no access. There is no implicit access for any role except the seeded owner/super-admin sets. |
| AP-02 | Two permission families | Platform permissions and organization permissions are distinct namespaces; a role belongs to exactly one family. |
| AP-03 | Tenant data is scoped at query level | Organization-owned rows carry an owning organization; every query is constrained by the caller's membership. |
| AP-04 | Permissions are additive, never subtractive | Effective rights are a union of roles and direct grants. There is no `deny` rule in v1 (see §5.4). |
| AP-05 | No privilege escalation | A grantor can never grant a permission, scope or role they do not hold themselves. |
| AP-06 | One identity, many memberships | A person has one user account and one membership per organization; roles do not travel between organizations. |
| AP-07 | Owner is structural, not just permissive | An organization always has exactly one active owner; the owner role is immutable and cannot be edited or deleted. |
| AP-08 | Enforcement is layered | Route → request → policy → service → query → database constraint. UI hiding is never treated as enforcement. |
| AP-09 | Sensitive changes are audited | Role, permission, membership, financial and cross-tenant actions produce immutable audit records. |
| AP-10 | Permission keys are stable contracts | Keys are seeded, never renamed silently; deprecation keeps the old key until consumers migrate. |

---

## 3. Domain model

The authorization domain consists of seven concepts. Only names and relationships are given — no schema or code is defined in this phase.

### 3.1 Identity

* **User** — the global person: credentials, verified e-mail/phone, locale, account status. A user is not owned by an organization.
* **Membership** — the link between a user and one organization. It carries: organization, status (`invited`, `active`, `suspended`, `removed`), owner flag, service scope, job title, and invitation metadata.
* **Organization** — a tenant. Three types: `platform`, `company`, `hotel`. The platform itself is represented as the single platform organization so that staff can hold memberships in the same model rather than a parallel table.

Consequences:

* A person may be a member of several organizations (for example, an accountant serving two companies) and receives a different membership in each. Roles never cross.
* Platform staff are members of the platform organization; they do **not** receive organization permissions. Cross-tenant visibility is granted through platform permissions and is audited.
* A user with no active membership can still act as a customer (R-01) on their own data only.

### 3.2 Role

* **Role** — a named bundle of permissions, owned by a scope:
  * **platform role** — belongs to the platform organization; may contain only platform permissions.
  * **organization role** — belongs to one company or hotel; may contain only organization permissions.
* **System roles** — seeded and immutable: `platform_super_admin`, `platform_finance`, `platform_content`, `platform_support`, `org_owner`, `hotel_owner`, plus the fixed employee presets exposed as editable templates (`bookings_employee`, `visa_employee`, `accountant`, `content_manager`, `reception`, `hotel_bookings_manager`, `hotel_accountant`, `restricted_employee`).
* **Custom roles** — created by a platform admin (platform family) or by an owner (organization family), inside the creator's ceiling.
* **Multiple roles per membership** — supported and unioned. The UI may present a primary role, but the model does not forbid a second one. This avoids painful migrations when a person legitimately needs two bundles.

### 3.3 Permission

* **Permission** — an operation, identified by a stable key in `domain.action` form (`bookings.cancel`, `visa_requests.update_status`, `platform.partners.approve`).
* Each permission row declares:
  * **family** — `platform` or `organization`;
  * **domain** — bookings, visas, tours, rooms, rates, employees, roles, coupons, reports, settlements, profile, catalogue, customers, partners, finance, audit;
  * **description** — one sentence used by the role editor;
  * **sensitivity** — normal / sensitive / financial / access-control;
  * **scope requirement** — organization-wide, service-scoped, assigned-records-only, or platform-global (see §4).
* Wildcards, `*` grants and pattern matching are **not** stored. A role lists explicit keys. The full catalog is in `ROLE-PERMISSION-SCOPE-MATRIX.md`.

### 3.4 Service scope

Some organization members are responsible for one service line rather than the whole tenant:

* service values: `flights`, `hotels`, `tours`, `visas`, `esim`;
* a membership may hold zero or more service scopes;
* zero scopes means "all services in the organization" **only when** the role is org-wide (owner). For non-owner roles, zero scopes means no service access.

Service scope is evaluated **in addition to** permission and organization scope. It narrows what a permission can touch; it never grants anything.

### 3.5 Assignment

Three assignment mechanisms exist:

1. **Role assignment** — membership ↔ role. Unioned.
2. **Direct permission grant** — membership ↔ permission, additive, used for "customized permissions per employee" without creating a role per person.
3. **Service scope assignment** — membership ↔ service values.

Direct grants are bounded by the grantor's ceiling at grant time and re-validated when the grantor's own rights shrink (see §7.4).

### 3.6 Ownership

* `owner` is a structural flag **and** an immutable system role.
* Exactly one active owner exists per organization at any time (enforced by a partial unique constraint at the database layer and by a service-level transfer operation).
* Ownership transfer is an atomic, audited operation: demote current owner, promote target, revoke stale sessions, notify both.

### 3.7 Relationship summary

```
User ──< Membership >── Organization
                 │            │
                 │            └──< Role (scope-constrained)
                 ├──< MembershipRole >── Role ──< RolePermission >── Permission
                 ├──< MembershipPermission >── Permission        (direct grants)
                 └──< MembershipServiceScope >── Service
```

---

## 4. Scope model

Scope is a four-level decision. All four must pass.

| Level | Question | Enforcement |
|---|---|---|
| **L1 — Family** | Is this a platform action or an organization action? | The role's family and the permission's family must match. |
| **L2 — Organization** | Does the record belong to the caller's organization (or is the caller platform staff with the matching platform permission)? | Query scoping + record-level policy + non-null `organization_id` on tenant tables. |
| **L3 — Service** | Is the record's service inside the membership's service scope? | Policy check on the service attribute. |
| **L4 — Assignment / ownership** | Is this record assigned to the caller, or does the caller own the organization? | Policy check on assignment or owner flag. |

### 4.1 Scope archetypes

| Archetype | Who | Rule |
|---|---|---|
| **Platform-global** | Platform roles | Permission applies across all tenants. Every cross-tenant read is audited when sensitive. |
| **Organization-wide** | Owners | All records of the organization, all services. |
| **Service-scoped** | Service owners, service employees | Only records whose service is inside the membership's service scope. |
| **Assigned-only** | Restricted employee (R-16) | Only records explicitly assigned to the membership. |
| **Self-only** | Customer (R-01) | Only rows whose `user_id` equals the authenticated user. |
| **Public** | Guest (R-02) | Read-only catalogue and search endpoints; no tenant data. |

### 4.2 Important clarifications

* **Organization scope is not a permission.** `bookings.view` plus a company membership means "view this company's bookings". The same key plus a hotel membership means "view this hotel's bookings". The key does not encode the tenant.
* **Platform staff do not automatically see tenant data.** They need an explicit platform permission (`platform.bookings.view`), and the read is audit-logged. There is no silent super-user bypass.
* **Service scope cannot widen a role.** An employee with `bookings.view` and service scope `hotels` cannot view flight bookings, even if the permission key is generic.
* **Assignment is a filter, not a role.** The restricted employee still needs the permission; assignment further narrows the rows.
* **Cross-tenant identifiers are opaque.** A request carrying another tenant's record id must fail with 404/403 at the policy layer, never leak existence details.

---

## 5. Effective permission resolution

### 5.1 Resolution order

For every request the authorization service resolves, in order:

1. **Context** — which membership is active (platform or organization), its organization and its status. Inactive or removed membership ⇒ deny.
2. **Family filter** — keep only permissions whose family matches the context. A platform context never evaluates organization permissions and vice versa.
3. **Role union** — collect permissions from every role assigned to the membership.
4. **Direct grants** — add membership-level grants.
5. **De-duplicate** — the effective set is a set, not a list.
6. **Scope filter** — at policy time, apply L2–L4 from §4.
7. **Cache** — the set is cached per membership and invalidated by a membership version counter (see §10).

### 5.2 Worked example

An employee of «رحلات النيل» has roles `visa_employee` (permissions `visa_requests.view`, `visa_requests.review`, `visa_requests.update_status`, `visa_offerings.view`) plus a direct grant `reports.view`. Their membership has service scope `visas`.

* They may open a visa request belonging to their company — all four levels pass.
* They may open the company's visa report — `reports.view` passes, but if the report includes tour revenue, the service scope narrows the data to visas.
* They may **not** open a booking — no booking permission.
* They may **not** open another company's visa request — L2 fails.
* They may **not** grant themselves `bookings.cancel` — §7 forbids it.

### 5.3 Owner and super-admin resolution

* **Organization owner** holds the immutable `org_owner` role, which contains every organization permission. It cannot be edited, and it cannot be assigned to a second person without transferring ownership.
* **Platform super admin** holds the immutable `platform_super_admin` role with every platform permission. It contains **no** organization permission, so tenant mutations still require the explicit platform permission and are audited.
* Neither role is a "bypass". Both are ordinary permission sets; the difference is breadth and the invariants around them.

### 5.4 Why there is no explicit "deny"

Additive-only authorization is deliberate:

* It is predictable: "can do X" is a union, not an order-dependent evaluation.
* It avoids the classic conflict where a deny in one role silently overrides a grant in another.
* It makes caching and audit simple: the effective set is computed once.

The cost is that "remove one permission from an otherwise broad role" requires a custom role rather than a deny exception. This is accepted for v1. If deny is later required, it must be modelled as an explicit, audited exception with higher precedence — not as ad-hoc code.

---

## 6. Role model in detail

### 6.1 System roles

| Role | Family | Editable | Contains | Assignment |
|---|---|---|---|---|
| `platform_super_admin` | Platform | No | All platform permissions | Platform admin only |
| `platform_finance` | Platform | No | Financial and settlement permissions, read-only ops | Super admin |
| `platform_content` | Platform | No | Visa/eSIM catalogue permissions | Super admin |
| `platform_support` | Platform | No | Read-only customer/booking/deletion views | Super admin |
| `org_owner` | Organization | No | All organization permissions | Structural, one per org |
| `hotel_owner` | Organization | No | All hotel-scoped organization permissions | Structural, one per hotel |
| `bookings_employee` | Organization | Template | Booking view/update/cancel/export | Owner |
| `visa_employee` | Organization | Template | Visa review and status | Owner / service owner |
| `accountant` | Organization | Template | Reports, settlements view | Owner |
| `content_manager` | Organization | Template | Tours CRUD and submission | Owner / service owner |
| `reception` | Organization | Template | Arrivals, check-in/out | Hotel owner |
| `hotel_bookings_manager` | Organization | Template | Bookings, rates, stop-sell | Hotel owner |
| `hotel_accountant` | Organization | Template | Hotel reports | Hotel owner |
| `restricted_employee` | Organization | Template | Assigned visa requests, read-only bookings | Company owner |

Templates are copied into an organization on first use (or seeded per organization) so that an owner can adjust them without affecting other tenants.

### 6.2 Role creation rules

* Platform roles can be created only by `platform.roles.manage` and may contain only platform permissions.
* Organization roles can be created only by the owner (or a member holding `roles.manage`) and may contain only organization permissions valid for that organization type.
* A hotel cannot create a role containing flight or visa permissions; a company cannot create room/rate permissions. The permission catalog declares which organization types may hold each key.
* A role's name is unique within its scope, not globally.
* Deleting a role requires zero assigned members; otherwise the system forces reassignment first.
* Editing a role affects every member immediately (cache invalidation on the next request).

### 6.3 Employee customization

Two supported patterns:

| Pattern | When to use | Effect |
|---|---|---|
| **Clone a template into a custom role** | A group of employees needs the same variation | Reusable, auditable, easier to maintain |
| **Direct permission grant on one membership** | A single employee needs one extra capability | Fast, visible as an exception, easy to revoke |

Direct grants are deliberately visible in the member detail screen and in the audit log. They are never used to grant platform permissions.

---

## 7. Privilege escalation prevention

The following rules are mandatory and must be enforced in the service layer, not only in the UI.

| # | Rule | Failure mode prevented |
|---|---|---|
| PE-01 | A grantor may only grant permissions they currently hold. | Manager invents capabilities |
| PE-02 | A grantor may only grant a scope equal to or narrower than their own (organization and service). | Company employee grants access to another company |
| PE-03 | Platform permissions cannot be attached to organization roles, and vice versa. | Tenant user escalates to platform staff |
| PE-04 | A role can only be assigned to memberships in the role's organization. | Cross-tenant role injection |
| PE-05 | Only the owner (or platform super admin) can transfer ownership; the owner role cannot be assigned directly. | Silent takeover |
| PE-06 | No one can edit their own role assignments or direct grants. | Self-promotion |
| PE-07 | System roles (`*_owner`, `platform_super_admin`) are immutable. | Removing the safety net |
| PE-08 | The last active owner cannot be deactivated, removed or demoted. | Orphaned tenant |
| PE-09 | A service owner cannot manage members or records outside their service scope. | Lateral access across service lines |
| PE-10 | A member cannot grant a permission that their own role gained through a direct grant unless they hold it effectively. | Chain escalation |
| PE-11 | Role changes and grants are audited with before/after values and actor membership. | Untraceable privilege |
| PE-12 | Removed permissions take effect on the next request, not at next login. | Stale access |

### 7.1 Revocation semantics

When a grantor loses a permission, previously granted permissions **remain** — they were legitimate at the time and revoking them would cascade unpredictably across tenants. Instead:

* every grant records its grantor and the ceiling at grant time;
* a periodic reconciliation flags grants that exceed the current grantor's ceiling;
* the owner and platform audit view can review and revoke flagged grants;
* the owner can always regenerate a role from a template to reset drift.

This is a deliberate availability-over-strictness trade-off and is documented for Phase 3.

---

## 8. Lifecycle rules

### 8.1 Invitation and activation

1. Owner/platform creates a membership in `invited` state with a role and service scope.
2. An invitation token is issued (single-use, expiring).
3. The invitee accepts, sets credentials if new, and the membership becomes `active`.
4. Until activation, the membership grants nothing and cannot authenticate into the dashboard.
5. Resending an invitation invalidates the previous token.

### 8.2 Role and scope changes

* Effective immediately on save; the membership version counter increments; the cache key changes.
* The affected user's next request sees the new set. No re-login is required.
* Service-scope changes behave the same way.

### 8.3 Deactivation

* Status becomes `suspended` (temporary) or `removed` (permanent).
* All sessions and refresh tokens for that membership context are revoked immediately.
* Records assigned to the member are reassigned or flagged; visa queues must not silently lose an owner.
* Audit and historical actions remain attributed to the member.
* A suspended member cannot log in to that organization; the same user may retain other memberships.

### 8.4 Moving between organizations

* Memberships are never moved. The old membership is `removed`, a new one is created in the target organization.
* Roles, direct grants and service scopes do **not** carry over; the new owner assigns them explicitly.
* Sessions for the old organization are revoked; the user may still access other memberships.
* Audit preserves the transition.

### 8.5 Ownership transfer

* Atomic: validate target is an active member, demote current owner, promote target, revoke stale sessions, notify both, audit.
* Cannot transfer to a suspended member.
* Cannot leave an organization without an owner.

### 8.6 Customer account lifecycle

Customers are not members. Their access is ownership-based (self-only). Account deletion follows BR-029 and the 7-day grace period; deletion affects customer data, not staff memberships.

---

## 9. Enforcement architecture

Authorization is enforced at seven layers. Any single layer is insufficient; the redundancy is intentional.

| Layer | Responsibility | Typical failure it catches |
|---|---|---|
| **1. Route middleware** | Authenticate, resolve active membership/context, reject suspended/removed, set tenant context. | Unauthenticated or wrong-context requests |
| **2. Request validation** | Validate payload and assert the coarse permission key. | Missing permission, malformed input |
| **3. Policy** | Record-level decision: organization, service, assignment, ownership, state. | IDOR / cross-tenant access |
| **4. Service/action layer** | Business invariants and a second authorization check before side effects. | Bypassed controller, direct service call, race conditions |
| **5. Query layer** | Organization global scope on tenant models; explicit joins; no unscoped raw queries. | Accidental data leakage in lists/reports |
| **6. Database** | Non-null owning organization, foreign keys, unique constraints (one owner, one coupon per booking), enums/checks. | Corrupt or orphaned data |
| **7. API resource** | Field-level visibility: hide cost/margin from customers, mask PII for partners after deletion. | Over-exposure in responses |

### 9.1 Request flow

```
Request
  → authenticate (session/token)
  → resolve membership + organization + family
  → check membership status
  → check permission key (family-aware)
  → load record (scoped query)
  → policy check (org / service / assignment / state)
  → service action (re-check + invariants + transaction)
  → audit event (if sensitive)
  → resource (field masking)
Response
```

### 9.2 Defense in depth

* A controller must not be the only place a permission is checked; the same key is asserted in the form request and the service.
* A policy must not rely on data passed from the client; it loads the record through the scoped repository.
* A list endpoint must apply the organization scope even when a policy check per row would also work — one missing row check leaks data.
* Background jobs and scheduled tasks must carry an explicit actor/system context; they must never inherit a stale tenant from a long-running worker.

### 9.3 Exports and reports

Exports are a common leak path:

* the export job reuses the same scoped query builder as the screen;
* the actor's membership and filters are stored with the export;
* an export cannot be requested for a scope the actor cannot view;
* exports of customer data are audited and expire.

---

## 10. Multi-tenancy and data isolation

| Rule | Implementation intent |
|---|---|
| MT-01 | Every tenant-owned table carries the owning organization id, non-null. |
| MT-02 | Tenant context comes from the authenticated membership, never from a request parameter or header. |
| MT-03 | Relationships between tenant entities are validated for the same organization at write time (a booking cannot reference another company's tour). |
| MT-04 | Platform cross-tenant reads require a platform permission and produce an audit record. |
| MT-05 | No impersonation feature exists in v1. If added, it must be time-boxed, consented and audited. |
| MT-06 | Unique constraints are tenant-scoped where appropriate (role name per organization, coupon code per owner scope) and global where the business requires it (payment reference). |
| MT-07 | Soft-deleted tenant rows remain invisible to other tenants and are excluded from scoped queries. |
| MT-08 | Customer PII is never exposed to a partner outside the partner's own booking context; after account anonymisation, partner-facing responses are masked (BR-029). |

### 10.1 Partner-visible customer data after deletion

DEC-09 requires that companies and hotels cannot access a deleted customer's personal information. The operational tension is that a hotel needs a guest name for an active stay. The rule is therefore:

* while a booking is active or upcoming, the partner sees the minimum operational data (guest name, stay dates, requests) — deletion is blocked during upcoming trips anyway;
* after anonymisation, historical partner views show a masked customer reference, not name, e-mail or phone;
* platform support may see the pre-deletion identity only through an audited platform permission and only while a legal/financial obligation requires it.

---

## 11. Audit logging

### 11.1 Events that must be audited

| Category | Examples |
|---|---|
| Access control | role created/updated/deleted, permission granted/revoked, member invited/suspended/removed, ownership transferred, service scope changed |
| Financial | refund approved/executed, settlement generated/paid/failed, commission changed, payout requested |
| Tenant administration | partner approved/suspended, hotel profile published, documents verified |
| Sensitive data | customer blocked, deletion processed, export generated, cross-tenant read |
| Authentication | admin login, failed authorization on a sensitive action (optional security log) |

### 11.2 Audit record fields

Actor (user + membership + organization), action key, target entity and id, before/after diff for access-control and financial changes, request id, IP/user agent, timestamp (UTC), outcome, and reason where required.

### 11.3 Properties

* Append-only: no update or delete path in the application.
* Read access is itself a permission (`platform.audit.view`); partner owners see only their organization's access-control audit.
* Retention is longer than operational data (financial/legal), and deletion of a user does not erase the audit trail.
* Audit writes must not be silently dropped: failures are logged and retried, and a failed audit on a sensitive action should fail the action.

---

## 12. Caching and performance

* Cache key: `membership:{id}:v{version}`; the version increments on any role, grant, scope, status or organization change.
* Permission catalog and role definitions may be cached globally with a version tag.
* Never cache an authorization decision that depends on record state (booking status, assignment) — only the permission set.
* Multi-tenant workers must clear or re-resolve context between jobs; a cached set from organization A must never authorize a job running for organization B.
* Bulk operations (bulk price update, bulk export) resolve the actor once and assert the permission once, then apply scoped queries per row.

---

## 13. Evaluation of Spatie Laravel Permission (teams)

### 13.1 What Spatie provides

* roles and permissions tables with polymorphic assignment;
* multiple guards;
* multiple roles per model;
* team scoping (`team_id`) for roles and direct permissions;
* Blade/middleware helpers and a permission cache;
* a mature, well-tested Laravel integration.

### 13.2 Fit against this architecture

| Requirement | Spatie + teams | Gap / action |
|---|---|---|
| Role and permission CRUD | Native | Use as storage/assignment engine |
| Multiple roles per member | Native | Union handled by the package |
| Per-tenant roles | Teams (`team_id`) | Team = organization; platform needs its own team row |
| Platform vs organization permission families | **Not native** | Custom guard/context + separate role sets; never mix |
| Service-level scope | **Not native** | Custom `service_scope` and policy layer |
| Record-level isolation (IDOR) | **Not native** | Policies + organization global scopes |
| Grant ceiling / anti-escalation | **Not native** | Custom grant service enforcing §7 |
| Per-employee direct customization | Native direct permissions, but not ceiling-checked | Wrap grants in the custom service |
| Audit of access-control changes | **Not native** | Custom audit events |
| Soft-deleted roles | Caveat | Prefer explicit role status over soft delete |
| Wildcard permissions | Supported — **we forbid** | Validation rejects `*` |
| Team switching in long-running workers | Risk | Explicit context reset + tests |
| Cache invalidation across teams | Manual | Versioned cache keys |

### 13.3 Verdict

**Spatie with teams is appropriate as the role/permission storage and assignment engine, but not sufficient on its own.** It does not enforce tenant isolation, service scope, grant ceilings or audit. Those must remain first-class application services.

Two viable implementations:

| Option | Description | Advantages | Disadvantages |
|---|---|---|---|
| **A. Spatie + custom authorization layer (recommended)** | Use Spatie tables for roles/permissions/assignments; add Membership, service scopes, a grant-ceiling service, policies, organization scopes and audit. | Faster to build; mature CRUD and cache; familiar to Laravel teams. | Team-context pitfalls; custom layer still substantial; some package assumptions must be overridden. |
| **B. Fully custom authorization tables** | Own roles, permissions, role_permissions, membership_roles, grants. | Exact fit; no team-context leakage; simpler mental model for multi-tenant + platform split. | More code and tests; no package helpers; must build cache and UI. |

**Recommendation: Option A**, with the domain model in §3 unchanged. If the team finds team switching in queues and platform/tenant mixing fragile, Option B is a legitimate fallback; the permission keys, roles and scope rules are identical either way.

This is a technical storage decision, recorded as Q-50 for engineering sign-off. It does not require a business decision.

---

## 14. Deriving permissions from screens and features

The permission catalog was derived bottom-up:

1. Each of the 108 screen states was mapped to a feature (Phase 2 §3).
2. Each feature was decomposed into operations: view a list, view a detail, create, update, delete, submit, publish, approve, refund, export, assign.
3. Operations that a role must be able to perform independently became a permission key.
4. Operations that always occur together stayed inside one key (for example, check-in and check-out are separate because reception may need one without the other).
5. Platform operations were separated from organization operations by **who owns the data**, not by screen location.
6. Cross-cutting operations (exports, audit, impersonation) were given their own keys because they are sensitive.

Result: **76 permission keys** — 31 platform, 45 organization. The full catalog, with the screens and features that justify each key, is in `ROLE-PERMISSION-SCOPE-MATRIX.md`.

### 14.1 Keys deliberately not created

* `*` / `all` — forbidden by design.
* Separate `view` and `list` — list is covered by view; row filtering is scope.
* `bookings.delete` — bookings are cancelled or archived, never deleted.
* `customers.delete` — deletion is a workflow (`platform.deletion_requests.process`).
* `employees.manage` as an umbrella — replaced by view/create/update/deactivate so that delegation is precise.
* Per-field permissions (for example `customers.view.email`) — field masking is a resource concern, not RBAC; sensitive fields are governed by scope and role (support vs finance).

---

## 15. Open items affecting authorization

| ID | Question | Impact |
|---|---|---|
| Q-45 | **What is a "Service Owner"?** Organization-level manager of one service line, or platform-level owner of a service? | Determines whether service scope attaches to memberships (current model) or to a new platform role family. **Blocks Phase 3.** |
| Q-48 | Can platform staff also hold partner memberships, and how is context chosen at login? | Membership model supports it; login/context UI needs a rule. |
| Q-50 | Spatie + custom layer vs fully custom tables | Storage shape, not the permission model. |
| Q-49 | Are hotel groups/multi-branch in scope? | Adds a branch scope level; current model assumes one property per hotel organization. |

---

## 16. Validation checklist

| # | Check | Result |
|---|---|---|
| 1 | Platform and organization permissions separated | ✅ Two families, no mixing |
| 2 | Every role belongs to one family | ✅ |
| 3 | Every permission key has a family, domain, description and sensitivity | ✅ 76/76 |
| 4 | Each permission is justified by a screen/feature | ✅ catalog maps all keys |
| 5 | Organization isolation specified at query, policy and database layers | ✅ |
| 6 | Service scope defined and enforced in addition to permission | ✅ |
| 7 | Grant ceiling and anti-escalation rules defined | ✅ 12 rules |
| 8 | Ownership transfer and last-owner protection | ✅ |
| 9 | Employee move/deactivate semantics | ✅ |
| 10 | Audit coverage for access-control and financial operations | ✅ |
| 11 | Spatie evaluated, limits stated, recommendation given | ✅ |
| 12 | No code, migration or model generated | ✅ |

