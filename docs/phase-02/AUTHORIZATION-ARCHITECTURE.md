# Phase 2 — Authorization Architecture

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Decisions DEC-11 – DEC-19 applied — awaiting approval (`APPROVE PHASE 3`)
**Inputs:** [`USER-ROLES-AND-PERMISSIONS.md`](./USER-ROLES-AND-PERMISSIONS.md), [`FEATURES-CATALOG.md`](./FEATURES-CATALOG.md), [`BUSINESS-LOGIC.md`](./BUSINESS-LOGIC.md), [`DB-DECISION-BRIEF.md`](./DB-DECISION-BRIEF.md)
**Companion:** [`ROLE-PERMISSION-SCOPE-MATRIX.md`](./ROLE-PERMISSION-SCOPE-MATRIX.md)
**Date:** 2026-11-23 · **Authorization reconciliation:** 2026-11-24

---

## 1. Purpose and scope

This document defines the authorization model required by the platform: platform staff, company owners, hotel owners, service-provider owners, service owners, employees and customers, with granular, operation-level permissions, organization and property isolation, protected owner templates, delegated administration and non-delegable permissions.

It answers five questions:

1. **Who** can act (identity, membership, role).
2. **What** they can do (permission catalog).
3. **Where** the action applies (platform, organization, hotel property, service, record ownership).
4. **How** the decision is enforced (middleware, policy, service, query, database).
5. **How** privilege is contained (owner templates, grant ceilings, scope ceilings, non-delegable permissions, lifecycle rules).

> **Audit/activity scope:** the approved design contains **no audit log and no general activity log**. `status_histories` records entity lifecycle status transitions only. The Team Activity Feed may derive status transitions and booking lifecycle updates from `status_histories`; it is not a user-activity or audit log. This is permanent for the approved scope.

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
| AP-07 | Owner roles are structural templates | Each organization has exactly one owner membership. `company_owner`, `hotel_owner` and `service_provider_owner` are fixed templates whose mandatory permissions cannot be edited or deleted by the organization. |
| AP-08 | Enforcement is layered | Route → request → policy → service → query → database constraint. UI hiding is never treated as enforcement. |
| AP-09 | Non-delegable permissions are absolute | The central list in `config/authorization.php` is enforced by the Authorization Service; no role, grant or UI path can delegate them. |
| AP-10 | Permission keys are stable contracts | Keys are seeded, never renamed silently; deprecation keeps the old key until consumers migrate. |
| AP-11 | No audit or general activity log | No audit log, activity log or user-action history is part of the design. Entity lifecycle transitions are stored in `status_histories` only. |
| AP-12 | Owner templates are synchronized, not hand-edited | System templates are written by a documented sync command; organization-level edits cannot change mandatory permissions. |

---

## 3. Domain model

The authorization domain consists of seven concepts. Only names and relationships are given — no schema or code is defined in this phase.

### 3.1 Identity

* **User** — the global person: credentials, verified e-mail/phone, locale, account status. A user is not owned by an organization.
* **Membership** — the link between a user and one organization. It carries: organization, status (`invited`, `active`, `suspended`, `removed`), owner flag, service scope, hotel-property scope (Hotels service; applies to `hotel` and multi-service `service_provider` organizations), job title, and invitation metadata.
* **Organization** — a tenant. Four types: `platform`, `company`, `hotel`, `service_provider`. The platform itself is represented as the single platform organization so that staff can hold memberships in the same model rather than a parallel table.

Consequences:

* A person may be a member of several organizations (for example, an accountant serving two companies) and receives a different membership in each. Roles never cross.
* Platform staff are members of the platform organization; they do **not** receive organization permissions. Cross-tenant visibility is granted through explicit platform permissions. No audit record is produced.
* A user with no active membership can still act as a customer (R-01) on their own data only.
* A **service_provider** is an independent organization type. Its owner template manages the provider organization, employees, bookings, reports, settlements and the customers attached to its services. It holds no `platform.*` permission and cannot reach another organization.

### 3.2 Role

* **Role** — a named bundle of permissions, owned by a scope:
  * **platform role** — belongs to the platform organization; may contain only platform permissions.
  * **organization role** — belongs to one company, hotel or service provider; may contain only organization permissions.
* **Owner templates** — fixed, protected roles: `company_owner`, `hotel_owner`, `service_provider_owner`. Their mandatory permissions cannot be removed, edited or deleted by the organization; they are synchronized by a documented command.
* **Employee templates** — seeded starting points exposed as editable templates (`bookings_employee`, `visa_offerings_manager`, `accountant`, `content_manager`, `company_service_manager`, `reception`, `hotel_bookings_manager`, `hotel_accountant`, `restricted_employee`, provider presets).
* **Custom roles** — created by a platform admin (platform family) or by an owner (organization family), inside the creator's ceiling. `service_provider_owner` is **not** a custom role; R-17 service owner is.
* **Multiple roles per membership** — supported and unioned. The UI may present a primary role, but the model does not forbid a second one. This avoids painful migrations when a person legitimately needs two bundles.

### 3.3 Permission

* **Permission** — an operation, identified by a stable key in `domain.action` form (`bookings.cancel`, `visa_requests.review`, `platform.visa_requests.decide`).
* Each permission row declares:
  * **family** — `platform` or `organization`;
  * **domain** — bookings, visas, tours, rooms, rates, properties, employees, roles, coupons, reports, settlements, profile, catalogue, customers, partners, finance, settings;
  * **description** — one sentence used by the role editor;
  * **sensitivity** — normal / sensitive / financial / access-control;
  * **scope requirement** — organization-wide, property-scoped, service-scoped, assigned-records-only, or platform-global (see §4);
  * **delegability** — whether the key may be delegated at all. Delegability is **not** stored as a database column; it is read from the central `config/authorization.php` list (DEC-13).
* Wildcards, `*` grants and pattern matching are **not** stored. A role lists explicit keys. The full catalog is in `ROLE-PERMISSION-SCOPE-MATRIX.md`.

### 3.4 Property scope

A hotel organization may own **multiple hotel properties**. The `hotels` table remains the property table: one hotel organization has many `hotels` rows.

* A membership may hold zero or more hotel-property scopes through `membership_hotel_scopes`.
* The hotel owner sees every property of its organization.
* A hotel employee with one or more property scopes sees only those properties.
* A hotel employee with **no** property scope has **no** hotel access — zero does not mean "all" for non-owners.
* Every hotel booking stores `hotel_id` (not `hotels_id`), and that property must belong to the booking's organization and the booking type must be hotel.

### 3.5 Service scope

Some organization members are responsible for one service line rather than the whole tenant:

* service values for partner memberships in this release: `hotels`, `tours`, `visas`. `flights` and `esim` are platform-only and are not valid partner membership scopes;
* a membership may hold zero or more service scopes;
* zero scopes means "all services in the organization" **only when** the role is org-wide (owner). For non-owner roles, zero scopes means no service access.

Service scope is evaluated **in addition to** permission and organization scope. It narrows what a permission can touch; it never grants anything.

### 3.6 Assignment

Four assignment mechanisms exist:

1. **Role assignment** — membership ↔ role. Unioned.
2. **Direct permission grant** — membership ↔ permission, additive, used for "customized permissions per employee" without creating a role per person. Non-delegable keys are rejected here.
3. **Service scope assignment** — membership ↔ service values.
4. **Hotel-property scope assignment** — membership ↔ `hotels` rows, hotels only.

Direct grants are bounded by the grantor's ceiling at grant time and re-validated when the grantor's own rights shrink (see §7.4).

### 3.7 Ownership

* `owner` is a structural flag **and** exactly one protected owner template: `company_owner`, `hotel_owner` or `service_provider_owner`.
* Exactly one active owner exists per organization at any time (enforced by a partial unique constraint at the database layer and by a service-level transfer operation).
* Ownership transfer is an atomic operation: demote current owner, promote target, revoke stale sessions, notify both. No audit record is written.
* The mandatory permissions of an owner template cannot be removed or edited by the organization. Optional permissions may be added to a custom employee role, never to the owner template.

### 3.8 Relationship summary

```
User ──< Membership >── Organization
                 │            │
                 │            └──< Role (scope-constrained)
                 ├──< MembershipRole >── Role ──< RolePermission >── Permission
                 ├──< MembershipPermission >── Permission        (direct grants)
                 ├──< MembershipServiceScope >── Service
                 └──< MembershipHotelScope >── Hotel (property)
```

---

## 4. Scope model

Scope is a five-level decision. All five must pass.

| Level | Question | Enforcement |
|---|---|---|
| **L1 — Family** | Is this a platform action or an organization action? | The role's family and the permission's family must match. |
| **L2 — Organization** | Does the record belong to the caller's organization (or is the caller platform staff with the matching platform permission)? | Query scoping + record-level policy + non-null `organization_id` on tenant tables. |
| **L3 — Property** | For hotel records, is the property inside the membership's `membership_hotel_scopes` (or is the member the hotel owner)? | Policy check on `hotel_id`; no scopes means no hotel access for non-owners. |
| **L4 — Service** | Is the record's service inside the membership's service scope? | Policy check on the service attribute. |
| **L5 — Assignment / ownership** | Is this record assigned to the caller, or does the caller own the organization? | Policy check on assignment or owner flag. |

### 4.1 Scope archetypes

| Archetype | Who | Rule |
|---|---|---|
| **Platform-global** | Platform roles | Permission applies across all tenants. Cross-tenant reads require the explicit platform permission; no audit record is produced. |
| **Organization-wide** | Owners | All records of the organization, all services and all properties. |
| **Property-scoped** | Hotel staff | Only records whose `hotel_id` is inside the membership's property scope. |
| **Service-scoped** | Service owners, service employees | Only records whose service is inside the membership's service scope. |
| **Assigned-only** | Restricted employee (R-16) | Only records explicitly assigned to the membership. |
| **Self-only** | Customer (R-01) | Only rows whose `user_id` equals the authenticated user. |
| **Public** | Guest (R-02) | Read-only catalogue and search endpoints; no tenant data. |

### 4.2 Important clarifications

* **Organization scope is not a permission.** `bookings.view` plus a company membership means "view this company's bookings". The same key plus a hotel membership means "view this hotel organization's bookings", narrowed by property scope for non-owners.
* **Platform staff do not automatically see tenant data.** They need an explicit platform permission (`platform.bookings.view`). There is no silent super-user bypass and no audit record.
* **Service scope cannot widen a role.** An employee with `bookings.view` and service scope `hotels` cannot view flight bookings, even if the permission key is generic.
* **Property scope cannot widen a role.** A hotel employee with `bookings.view` and property scope `hotel A` cannot see hotel B, even in the same organization.
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
6. **Scope filter** — at policy time, apply L2–L5 from §4 (organization, property, service, assignment).
7. **Cache** — the set is cached per membership and invalidated by a membership version counter (see §10).

### 5.2 Worked example

An employee of «رحلات النيل» has the employee template `visa_offerings_manager` (permissions `visa_requests.view`, `visa_offerings.view`, `visa_offerings.manage`) plus a direct grant `reports.view`. Their membership has service scope `visas`.

* They may open a visa request attached to one of their company's offerings — L2 and L4 pass.
* They may not record a decision; that is a platform permission (`platform.visa_requests.decide`, `G0-DEC-04`). Their own `visa_requests.review` covers document validation only, and no company-side status key exists.
* They may open the company's visa report — `reports.view` passes, but the service scope narrows the data to visas.
* They may **not** open a booking — no booking permission.
* They may **not** open another company's visa request — L2 fails.
* They may **not** grant themselves `bookings.cancel` — §7 forbids it.

### 5.3 Owner and super-admin resolution

* **Company owner** holds the protected `company_owner` template, which contains every company-applicable organization permission plus the mandatory owner keys. Its mandatory permissions cannot be edited and it cannot be assigned to a second person without transferring ownership.
* **Hotel owner** holds `hotel_owner`; **service-provider owner** holds `service_provider_owner`. Each covers all organization permissions valid for its organization type, including all its hotel properties.
* **Platform super admin** holds the protected `platform_super_admin` role with every platform permission. It contains **no** organization permission, so tenant mutations still require the explicit platform permission.
* Neither role is a "bypass". Both are ordinary permission sets; the difference is breadth and the invariants around them.

### 5.4 Why there is no explicit "deny"

Additive-only authorization is deliberate:

* It is predictable: "can do X" is a union, not an order-dependent evaluation.
* It avoids the classic conflict where a deny in one role silently overrides a grant in another.
* It keeps caching simple: the effective set is computed once.

The cost is that "remove one permission from an otherwise broad role" requires a custom role rather than a deny exception. This is accepted for v1. If deny is later required, it must be modelled as an explicit exception with higher precedence — not as ad-hoc code.

### 5.5 Non-delegable resolution

A permission can be held and exercised but **never delegated** (DEC-13). The Authorization Service checks `config/authorization.php` on every role edit, direct grant, template copy and invitation. The UI may hide the control, but the service rejection is authoritative. There is no `is_delegable` database column; the config file is the single source of truth.

---

## 6. Role model in detail

### 6.1 System roles

| Role | Family | Editable | Contains | Assignment |
|---|---|---|---|---|
| `platform_super_admin` | Platform | No | All platform permissions | Platform admin only |
| `platform_finance` | Platform | No | Financial and settlement permissions, read-only ops | Super admin |
| `platform_content` | Platform | No | Visa/eSIM catalogue permissions | Super admin |
| `platform_support` | Platform | No | Read-only customer/booking/deletion views | Super admin |
| `platform_visa_officer` | Platform | No | Visa request review and status decisions | Super admin |
| `company_owner` | Organization | **No — protected template** | All company-applicable organization permissions | Structural, one per company |
| `hotel_owner` | Organization | **No — protected template** | All hotel-applicable organization permissions, all properties | Structural, one per hotel organization |
| `service_provider_owner` | Organization | **No — protected template** | All service-provider-applicable organization permissions | Structural, one per provider |
| `bookings_employee` | Organization | Template | Booking view/update/cancel/export | Owner |
| `visa_offerings_manager` | Organization | Template | View own requests, manage own offerings | Company owner / service owner |
| `accountant` | Organization | Template | Reports, settlements view | Owner |
| `content_manager` | Organization | Template | Tours CRUD and submission | Company owner |
| `company_service_manager` | Organization | Template (optional) | Service-scoped employees/roles + service operations | Company owner |
| `reception` | Organization | Template | Arrivals, check-in/out, property-scoped | Hotel owner |
| `hotel_bookings_manager` | Organization | Template | Bookings, rates, stop-sell, property-scoped | Hotel owner |
| `hotel_accountant` | Organization | Template | Hotel reports | Hotel owner |
| `restricted_employee` | Organization | Template | Assigned visa requests, read-only bookings | Company owner |

Templates are copied into an organization on first use (or seeded per organization) so that an owner can adjust an employee template without affecting other tenants. **Owner templates are the exception:** they are synchronized from the system definition by a documented command (for example `php artisan authorization:sync-templates`) and cannot be modified at organization level.

### 6.2 Role creation rules

* Platform roles can be created only by `platform.roles.manage` and may contain only platform permissions.
* Organization roles can be created only by the owner (or a member holding `roles.manage`) and may contain only organization permissions valid for that organization type.
* A hotel cannot create a role containing flight, visa or tour permissions; a company cannot create room/rate or property permissions; a service provider can hold only the provider-applicable subset. The permission catalog declares which organization types may hold each key.
* A role's name is unique within its scope, not globally.
* Deleting a role requires zero assigned members; otherwise the system forces reassignment first.
* Editing a role affects every member immediately (cache invalidation on the next request).
* **Owner templates cannot be created, deleted or edited by the organization.** Their mandatory permissions are fixed; the sync command is the only writer.
* **Non-delegable keys** (DEC-13) are rejected in role creation, role editing, direct grants, template copying and invitations.

### 6.3 Employee customization

Two supported patterns:

| Pattern | When to use | Effect |
|---|---|---|
| **Clone an employee template into a custom role** | A group of employees needs the same variation | Reusable, easier to maintain |
| **Direct permission grant on one membership** | A single employee needs one extra capability | Fast, visible as an exception, easy to revoke |

Direct grants are visible in the member detail screen. They are never used to grant platform permissions or non-delegable keys. Owner templates are never used as the base for employee customization; owners add optional permissions to a separate custom role a person also holds.

### 6.4 Owner template protection and synchronization

* Three owner templates exist: `company_owner`, `hotel_owner`, `service_provider_owner`.
* Mandatory permissions are defined in `config/authorization.php` and copied into the template by the sync command.
* Organizations may not delete the template, rename it, remove a mandatory key or add a platform key.
* A missing or drifted owner template is repaired by the sync command; the repair is not a user action and is not logged as one.
* Owner-template role rows must not use a shared `organization_id = NULL` until Spatie Teams behaviour is explicitly validated (Q-50).

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
| PE-07 | Owner templates (`company_owner`, `hotel_owner`, `service_provider_owner`) and `platform_super_admin` are protected from deletion and from mandatory-permission edits. | Removing the safety net |
| PE-08 | The last active owner cannot be deactivated, removed or demoted. | Orphaned tenant |
| PE-09 | A service owner cannot manage members or records outside their service scope. | Lateral access across service lines |
| PE-10 | A member cannot grant a permission that their own role gained through a direct grant unless they hold it effectively. | Chain escalation |
| PE-11 | Non-delegable permissions (DEC-13) are rejected by the Authorization Service in every grant path. | Delegation of reserved control |
| PE-12 | Removed permissions take effect on the next request, not at next login. | Stale access |

### 7.2 Non-delegable permissions (DEC-13)

The central list lives in `config/authorization.php`. At minimum:

| Non-delegable capability | Key / surface |
|---|---|
| Delete the organization | owner-only structural action; **no assignable permission key exists** (there is no `org.delete` key in either family) |
| Transfer ownership / manage owners | ownership transfer operation; no assignable key |
| Edit protected owner templates | owner-template sync surface (system only) |
| Grant a permission the grantor may not delegate | any non-delegable key in the list |
| Edit global commissions | `platform.partners.update` / commission fields (platform only) |
| Manage payout accounts | `settlements.accounts.manage` (owner-only; not delegable to staff) |
| Approve financial transfers | `platform.finance.settlements.execute` (platform only) |
| Any platform permission | `platform.*` — never attachable to an organization role |

Enforcement is in the Authorization Service, not only in the UI. Removing `is_delegable` from the database avoids two sources of truth.

### 7.3 Revocation semantics

When a grantor loses a permission, previously granted permissions **remain** — they were legitimate at the time and revoking them would cascade unpredictably across tenants. Instead:

* every grant records its grantor and the ceiling at grant time;
* a periodic reconciliation flags grants that exceed the current grantor's ceiling;
* the owner reviews flagged grants in the member detail and may revoke them;
* the owner can always regenerate an employee role from a template to reset drift.

This is a deliberate availability-over-strictness trade-off and is documented for Phase 3.

---

## 8. Lifecycle rules

### 8.1 Invitation and activation

1. Owner/platform creates a membership in `invited` state with a role and — where applicable — service and hotel-property scopes.
2. An invitation token is issued (single-use, expiring).
3. The invitee accepts, sets credentials if new, and the membership becomes `active`.
4. Until activation, the membership grants nothing and cannot authenticate into the dashboard.
5. Resending an invitation invalidates the previous token.
6. A hotel employee with no property scope is active but has no hotel data access until a property is assigned.

### 8.2 Role and scope changes

* Effective immediately on save; the membership version counter increments; the cache key changes.
* The affected user's next request sees the new set. No re-login is required.
* Service-scope and property-scope changes behave the same way.

### 8.3 Deactivation

* Status becomes `suspended` (temporary) or `removed` (permanent).
* All sessions and refresh tokens for that membership context are revoked immediately.
* Records assigned to the member are reassigned or flagged; the platform visa queue must not silently lose a reviewer.
* No general activity log is kept; assignment and status records remain the operational evidence.
* A suspended member cannot log in to that organization; the same user may retain other memberships.

### 8.4 Moving between organizations

* Memberships are never moved. The old membership is `removed`, a new one is created in the target organization.
* Roles, direct grants, service scopes and property scopes do **not** carry over; the new owner assigns them explicitly.
* Sessions for the old organization are revoked; the user may still access other memberships.

### 8.5 Ownership transfer

* Atomic: validate target is an active member, demote current owner, promote target, revoke stale sessions, notify both.
* Cannot transfer to a suspended member.
* Cannot leave an organization without an owner.
* The owner template travels with the owner flag; the previous owner receives no residual owner key.

### 8.6 Customer account lifecycle

Customers are not members. Their access is ownership-based (self-only). Account deletion follows BR-029 and the **7-day** grace period; deletion affects customer data, not staff memberships.

* The deletion job checks every blocking condition (upcoming trip, visa application under review, pending refund) immediately before anonymisation.
* Ordinary eligible requests execute automatically after the grace period; **no manual approval is required**.
* The platform deletion queue is operational visibility, not an approval gate.
* If a blocking condition exists, the job skips the request and leaves it pending until the condition clears.

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
  → policy check (org / property / service / assignment / state)
  → service action (re-check + invariants + non-delegable check + transaction)
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
* exports of customer data expire and are never public links.

---

## 10. Multi-tenancy and data isolation

| Rule | Implementation intent |
|---|---|
| MT-01 | Every tenant-owned table carries the owning organization id, non-null. |
| MT-02 | Tenant context comes from the authenticated membership, never from a request parameter or header. |
| MT-03 | Relationships between tenant entities are validated for the same organization at write time (a booking cannot reference another company's tour). |
| MT-04 | Platform cross-tenant reads require an explicit platform permission. No audit record is produced. |
| MT-05 | No impersonation feature exists in v1. If added, it must be time-boxed and consented. |
| MT-06 | Unique constraints are tenant-scoped where appropriate (role name per organization, coupon code per owner scope) and global where the business requires it (payment reference). |
| MT-07 | Soft-deleted tenant rows remain invisible to other tenants and are excluded from scoped queries. |
| MT-08 | Customer PII is never exposed to a partner outside the partner's own booking context; after account anonymisation, partner-facing responses are masked (BR-029). |
| MT-09 | A hotel organization owns one or more `hotels` rows. Room types, rates and availability belong to a property, not directly to the organization. |
| MT-10 | `bookings.hotel_id` must reference a hotel property whose `organization_id` equals the booking's `organization_id`, and the booking type must be hotel. |
| MT-11 | A non-owner hotel membership with zero rows in `membership_hotel_scopes` has no access to any property. |
| MT-12 | The hotel owner reaches every property of its organization regardless of property scopes. |

### 10.1 Partner-visible customer data after deletion

DEC-09 requires that companies and hotels cannot access a deleted customer's personal information. The operational tension is that a hotel needs a guest name for an active stay. The rule is therefore:

* while a booking is active or upcoming, the partner sees the minimum operational data (guest name, stay dates, requests) — deletion is blocked during upcoming trips anyway;
* after anonymisation, historical partner views show a masked customer reference, not name, e-mail or phone;
* platform support may see the pre-deletion identity only through an explicit platform permission and only while a legal/financial obligation requires it. No audit record exists.

### 10.2 Multi-property hotel model

One **hotel organization** may own several **hotel properties** (`hotels` rows). Each property has its own room types, rates, availability and bookings. The owner template spans all properties; staff access is narrowed per property through `membership_hotel_scopes`. Reporting and settlement may aggregate at organization level and break down by property (Q-55).

---

## 11. Status histories and team activity — no audit log

### 11.1 Scope decision

Audit logs and general activity logs are **permanently removed** from the approved design. This is not a deferral.

* No `audit_logs`, `activity_logs`, `user_actions` or equivalent table exists.
* No permission can read an audit log; `platform.audit.view` is deleted from the catalog.
* Role, permission, membership and financial changes are **not** recorded in a general history. The current state is authoritative.
* `status_histories` is retained **only** for entity lifecycle status transitions (booking, visa application, tour, settlement, deletion request, etc.).
* No general Team Activity Feed or user-activity timeline is included. A dashboard may show business status transitions from `status_histories` only, never arbitrary user actions.

### 11.2 `status_histories` fields

Entity type, entity id, previous status, new status, changed-by membership (where the transition has an actor), timestamp (UTC), and an optional reason required by the domain. It does not store request payloads, before/after diffs of arbitrary fields, IP addresses or read events.

### 11.3 Boundaries

* A status history row is written by the domain transition itself, in the same transaction, so the state and its history cannot diverge.
* Deleting or anonymising a customer does not create a general audit entry; it is a lifecycle transition.
* No read of tenant data writes a row.
* No failed-authorization event is stored.
* Reporting may aggregate status histories; it may not reconstruct a user activity timeline beyond entity statuses.

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
| Non-delegable permission list | **Not native** | `config/authorization.php` + Authorization Service check |
| Owner-template protection | **Not native** | Fixed templates + sync command; forbid org edits |
| Owner-template roles with shared `organization_id = NULL` | Unvalidated | Do not use until teams behaviour is proven (Q-50) |
| Soft-deleted roles | Caveat | Prefer explicit role status over soft delete |
| Wildcard permissions | Supported — **we forbid** | Validation rejects `*` |
| Team switching in long-running workers | Risk | Explicit context reset + tests |
| Cache invalidation across teams | Manual | Versioned cache keys |

### 13.3 Verdict

**Spatie with teams is appropriate as the role/permission storage and assignment engine, but not sufficient on its own.** It does not enforce tenant isolation, property/service scope, grant ceilings, owner-template protection or the non-delegable list. Those must remain first-class application services.

Two viable implementations:

| Option | Description | Advantages | Disadvantages |
|---|---|---|---|
| **A. Spatie + custom authorization layer (recommended)** | Use Spatie tables for roles/permissions/assignments; add Membership, property and service scopes, owner templates, a grant-ceiling service, policies and organization scopes. | Faster to build; mature CRUD and cache; familiar to Laravel teams. | Team-context pitfalls; custom layer still substantial; some package assumptions must be overridden. |
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
6. Cross-cutting operations (exports, settings, impersonation) were given their own keys because they are sensitive.
7. Owner-template and non-delegable keys were added or removed by DEC-11 – DEC-19 after the original derivation; the catalog was reconciled against the actual definitions rather than the earlier estimate.

Result: the reconciled catalog is **85 permission keys** — 37 platform, 48 organization (proposed, awaiting Gate 0A approval). The full catalog, with the screens and features that justify each key, is in `ROLE-PERMISSION-SCOPE-MATRIX.md`; the derivation and the delta against both the documentation and the seeder are in `../PERMISSION-CATALOG-RECONCILIATION.md`.

### 14.1 Keys deliberately not created

* `*` / `all` — forbidden by design.
* Separate `view` and `list` — list is covered by view; row filtering is scope.
* `bookings.delete` — bookings are cancelled or archived, never deleted.
* `customers.delete` — deletion is a workflow (`platform.deletion_requests.process`).
* `employees.manage` as an umbrella — replaced by view/create/update/deactivate so that delegation is precise.
* `provider_services.*` — service types and lifecycle are undefined (DEC-17, Q-53).
* `properties.delete` — no archive/delete flow is defined; properties are unpublished instead.
* `platform.audit.view` — deleted with audit logs (§11).
* Per-field permissions (for example `customers.view.email`) — field masking is a resource concern, not RBAC; sensitive fields are governed by scope and role (support vs finance).
* `is_delegable` as a database column — the config list is the single source of truth (DEC-13).

---

## 15. Open items affecting authorization

| ID | Question | Status / impact |
|---|---|---|
| Q-45 | **What is a "Service Owner"?** | **Answered by DEC-18.** R-17 is **not** an owner template. It is a custom role inside a company that manages a service line and the resources assigned to it. The optional employee template `company_service_manager` covers the common case. R-17 never receives company-owner permissions. |
| Q-46 | Which actors move visa statuses, and are rejection reasons mandatory? | **Partially answered by DEC-16:** only Traway staff review and decide; company staff may view requests tied to their offerings. Reason taxonomy and customer-visibility wording remain open and block Phase 4 only. |
| Q-48 | Can one person hold a platform membership and a partner membership, and how is context chosen? | The membership model supports it. Default: allowed; one active context per session. Non-blocking. |
| Q-49 | Are hotel groups / multiple properties in scope? | **Answered by DEC-14.** One hotel organization may own multiple hotel properties; property-level authorization is mandatory. Branch/group modelling beyond one organization is not added. |
| Q-50 | Spatie + custom layer vs fully custom tables | Storage shape, not the permission model. Default: Spatie + custom layer. Do not use shared `organization_id = NULL` roles until teams behaviour is validated. Non-blocking. |
| Q-53 | What service types does a `service_provider` sell, and what is their booking lifecycle? | DEC-17 defers `provider_services.*`; the provider owner exists but its service catalogue and workflow are undefined. Non-blocking for the schema (organization/owner/property model), blocking for provider operations. |
| Q-54 | What verification is required for a payout account, and who may change bank details after approval? | `payout_accounts` and `settlements.accounts.manage` exist, but KYC and change-control are undefined. Non-blocking for table shape; blocking for payout release. |
| Q-55 | When one hotel organization has several properties, are reports and settlements allocated per property? | Property-level bookings are fixed; reporting/settlement allocation is not. Non-blocking; report grouping is additive. |

---

## 16. Validation checklist

| # | Check | Result |
|---|---|---|
| 1 | Platform and organization permissions separated | ✅ Two families, no mixing |
| 2 | Every role belongs to one family | ✅ |
| 3 | Every permission key has a family, domain, description and sensitivity | ✅ 83/83 |
| 4 | Each permission is justified by a screen/feature/decision | ✅ catalog maps all keys |
| 5 | Organization isolation specified at query, policy and database layers | ✅ |
| 6 | Property and service scope defined and enforced in addition to permission | ✅ |
| 7 | Grant ceiling and anti-escalation rules defined | ✅ PE-01 – PE-12 |
| 8 | Owner templates protected; ownership transfer and last-owner protection | ✅ Three protected templates |
| 9 | Non-delegable permissions enforced in the service layer | ✅ `config/authorization.php` |
| 10 | Employee move/deactivate semantics | ✅ |
| 11 | No audit or general activity log; `status_histories` limited to status transitions | ✅ §11 |
| 12 | Spatie evaluated, limits stated, recommendation given | ✅ |
| 13 | MySQL 8.4 LTS is the database baseline (DEC-15) | ✅ documented, no code |
| 14 | No code, migration or model generated | ✅ |

---

## 17. Decision traceability

| Decision | Where it is applied in this document |
|---|---|
| DEC-01 / DEC-02 — USD only | §4.2 (tenant scope), §10 (no FX dimensions), permission catalog has no currency keys |
| DEC-03 — coupons, platform + company owners | §3.2 (custom roles), §6.2 (role creation ceilings) |
| DEC-09 — deletion and partner masking | §8.6 (deletion job), §10.1 (partner-visible data after deletion) |
| DEC-10 — refund approval, gateway payouts | §7.2 (financial control), §7.3 (revocation) |
| DEC-11 — service-provider owner | §3.1 (fourth organization type), §6.1 (`service_provider_owner`), §7.2 (no platform keys) |
| DEC-12 — protected owner templates | §3.2, §6.1, §6.4, PE-07 |
| DEC-13 — non-delegable permissions | §5.5, §7.2, §14.1 (no `is_delegable` column) |
| DEC-14 — multiple hotel properties | §3.4, §4 (L3), §10.2, MT-09 – MT-12 |
| DEC-15 — MySQL 8.4 LTS | §16 (validation), DB-DECISION-BRIEF §10 |
| DEC-16 — platform-only visa decisions | §5.2, §6.1 (`platform_visa_officer`), permission catalog |
| DEC-17 — service-provider organization | §3.1, §6.1, §14.1 (no `provider_services.*`) |
| DEC-18 — R-17 is a custom role | §3.2, §6.1 (`company_service_manager`), Q-45 |
| DEC-19 — settings, payout accounts, properties, platform settlement execution | §6.1, §7.2, §14, MT-09 – MT-12 |
| Authorization requirement 1–10 | §3 (membership model), §4 (scope), §5 (resolution), §6 (roles), §7 (anti-escalation), §8 (lifecycle), §9 (enforcement), §10 (isolation), §11 (status histories) |

**Scope split that answers the requirement's last paragraph:**

| Actor | Family | Owns | Cannot reach |
|---|---|---|---|
| Platform Admin | Platform | Platform-wide permissions, tenant administration through explicit `platform.*` keys | Tenant data without the matching platform permission; never holds organization permissions |
| Company Owner | Organization (company) | All company services and records | Another company, any hotel, platform operations |
| Hotel Owner | Organization (hotel) | The single hotel tenant | Any company, another hotel, platform operations |
| Service Owner | Organization (company or hotel) | One or more service lines inside its own tenant | Other services, other tenants |
| Employees | Organization | Only what their role + direct grants + service scope allow | Their owner's full set unless granted; never other tenants |

This separation is enforced three times: the role's family (§3.2), the record's owning organization (§4 L2), and the membership's service scope (§4 L3). No single control is trusted alone.




## 15. Multi-service provider model (owner-approved direction)

The authoritative service-provider model, permission-family boundaries, resource rules and Gate 0 consequences are defined in [`MULTI-SERVICE-PROVIDER-AUTHORIZATION.md`](./MULTI-SERVICE-PROVIDER-AUTHORIZATION.md). This section supersedes any earlier wording that limits a service provider to one service type or treats generic `bookings.*` as the authorization model for every service.

Implementation rule: organization-enabled services and membership service scopes are separate. Every organization-side query must enforce organization ownership, enabled service, membership service scope and applicable resource/property scope. Empty scope remains fail-closed under G0-DEC-05. Flights remain platform-only; visa final decisions remain platform-only under G0-DEC-04.


## 15. Service-aware authorization update — 2026-11-24

The controlling provider model and catalog are now documented in [`MULTI-SERVICE-PROVIDER-AUTHORIZATION.md`](./MULTI-SERVICE-PROVIDER-AUTHORIZATION.md) and [`SERVICE-AWARE-PERMISSION-CATALOG.md`](./SERVICE-AWARE-PERMISSION-CATALOG.md).

The following rules supersede any earlier single-service/provider-type assumptions in this document:

1. One `service_provider` organization may operate multiple services enabled by Traway.
2. Organization-enabled services and a membership's service scopes are separate data concepts. A member can receive multiple service scopes, but cannot be scoped to a service that is not enabled for the organization.
3. Effective access is the intersection of permission, organization ownership, enabled service, membership service scope, resource/property scope and assignment/workflow rules.
4. Generic `bookings.*` applies only to actual Hotels/Tours booking records; it does not authorize visa applications or eSIM orders. Visa requests use `visa_requests.*`. eSIM is platform-only under owner-approved Q-53; no provider eSIM permission, scope or workflow is allowed.
5. `customers.view` is a minimal contextual projection on records the member is already authorized to process; it is never the platform customer directory.
6. Flights remain platform-only. Final visa approval/rejection remains platform-only under G0-DEC-04.
7. Empty service/resource scope grants no access to non-owner memberships. Owner access is limited to services enabled for that organization.

**Implementation gap requiring correction:** the current `Membership::isRestrictedToHotel()` implementation treats an empty hotel-scope list as unrestricted, which conflicts with the approved fail-closed property-scope rule. The implementation must be corrected and covered by negative tests before release. This documentation update does not change code.


---

## 11. Backend authorization reconciliation — owner-approved Q-53–Q-56 (2026-11-24)

This section controls where earlier provisional statements conflict with the owner's later decisions. Backend-only scope; no frontend implementation is specified.

1. **Organization services vs membership scopes:** the platform-managed organization/service capability is separate from employee membership service assignments. Q-54 requires organization request → Platform Admin approve/reject → enabled; Traway may suspend/disable one service independently of the organization. Only enabled services authorize provider-side operations.
2. **Partner service keys:** valid organization membership scopes are `hotels`, `tours`, `visas`. `flights` and `esim` are not valid organization service scopes. Legacy booking discriminator values remain stored as-is and must be mapped server-side to canonical authorization services.
3. **eSIM:** Q-53 makes the entire eSIM business workflow platform-only. No provider-side permission, scope, resource assignment, queue or API tenant. Any external supplier is a Traway-controlled technical integration.
4. **Resource scopes:** Q-56 requires service scope first, then resource/property/assignment scope where meaningful. Hotel property scopes apply to both hotel and service_provider organizations and empty required property scope denies. Tours use tenant-owned tours plus a dedicated membership-tour scope only where individual tour restriction is part of the confirmed workflow. Visa requests use assigned membership or an explicitly modeled team queue; unassigned does not mean globally visible.
5. **Owner boundary:** an organization owner reaches all resources only inside that organization's currently enabled services and only with the required permission. Owner status never bypasses tenant, service enablement, permission eligibility or platform-only boundaries.
6. **Permission metadata:** each organization permission must declare applicable service(s), distinct from `allowed_org_types` and `scope_requirement`. Generic `bookings.*` applies only to persisted Hotels/Tours booking records. `customers.view` is contextual data projection, not a directory permission.
7. **Query enforcement:** tenant/service/resource/assignment constraints apply before pagination, counts, aggregates, reports and exports, not only in single-record policies.

The detailed persistence proposal, lifecycle columns, authorization evaluation order and test requirements are in [`AUTHORIZATION-DATA-MODEL-DESIGN.md`](./AUTHORIZATION-DATA-MODEL-DESIGN.md) §10. These are design requirements; no source implementation is implied.
