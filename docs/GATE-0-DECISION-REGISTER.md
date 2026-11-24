# Gate 0 Decision Register

**Project:** Traway
**Date:** 2026-11-24
**Status:** Gate 0A open — **5 architectural decisions approved; the revised service-aware catalog, service enablement model and resource scopes await approval.** Delegation, owner-template and empty-scope semantics are already approved; Phase A implements them. Gate 0B open — **6 business/integration items awaiting approval.**
**Source of the blocking list:** `IMPLEMENTATION-GAP-ANALYSIS.md` §11 "Gate 0"
**Companion documents:** [`PERMISSION-CATALOG-RECONCILIATION.md`](./PERMISSION-CATALOG-RECONCILIATION.md) · [`IMPLEMENTATION-GAP-ANALYSIS.md`](./IMPLEMENTATION-GAP-ANALYSIS.md) · [`IMPLEMENTATION-STATUS.md`](./IMPLEMENTATION-STATUS.md) · [`phase-02/OPEN-QUESTIONS.md`](./phase-02/OPEN-QUESTIONS.md) · [`README.md`](./README.md)

> **This register is documentation only.** No application code, migration, model, service, controller, policy, middleware, route, seeder, test or `.env` file was created or modified in producing it. Phase A has not started. No decision below has been approved by the project owner except the five marked **Approved**.

---

## 1. Conventions used in this register

### 1.1 Decision identifiers and the ID collision

`docs/phase-02/OPEN-QUESTIONS.md` §2.9 already records ten business decisions numbered **DEC-01 – DEC-10** (USD only, coupon rules, reference formats, no fare hold, visa payment, manual visa processing, hotel search inputs, deletion grace, settlement policy). Those IDs are referenced throughout `BUSINESS-LOGIC.md` and `FEATURES-CATALOG.md` and cannot be reused without corrupting every cross-reference. `DB-DECISION-BRIEF.md` carries a second, overlapping `DEC-01 – DEC-10` numbering for the same ten answers.

The five decisions approved for this task were supplied labelled `DEC-01 – DEC-05`. They are registered here as **`G0-DEC-01` – `G0-DEC-05`** so that the three sets never collide. The mapping is explicit:

| Supplied label | Register ID | Subject |
|---|---|---|
| DEC-01 | **G0-DEC-01** | Audit and activity logs — permanently excluded |
| DEC-02 | **G0-DEC-02** | Single authoritative permission catalog |
| DEC-03 | **G0-DEC-03** | Hotel organizations and multiple properties |
| DEC-04 | **G0-DEC-04** | Visa final decision authority |
| DEC-05 | **G0-DEC-05** | Empty scope semantics |

### 1.2 Approval status vocabulary

| Status | Meaning |
|---|---|
| **Approved** | The owner has approved this decision; it is authoritative and must not be reopened |
| **Recommended — awaiting approval** | A single recommendation is made and needs the owner's explicit yes |
| **Requires clarification** | The owner must supply information the documents and code cannot determine (provider selection, business intent) |
| **Deferred to Phase A** | An implementation decision, not a contract; it does not block the authorization work |

Three statuses are kept strictly apart throughout:

1. **The principle** — e.g. "there is one centralized permission catalog". Approved (`G0-DEC-02`).
2. **The content** — the former 85-key draft is withdrawn as an implementation target. The revised service-aware catalog is **not approved yet**.
3. **Everything in Gate 0B** — business and integration choices. None approved.

### 1.3 The two approval gates

Gate 0 was a single list. It is now split so that the authorization work is not held hostage by provider and business choices.

| | **Gate 0A — Authorization approval** | **Gate 0B — Business & integration approval** |
|---|---|---|
| Purpose | Everything Phase A (authorization and tenancy repairs) depends on | Everything later phases depend on |
| Blocks | Phase A, and by consequence every later phase | Only the phase that needs the specific decision |
| Items | `G0-DEC-01` … `G0-DEC-05`; the revised service-aware permission catalog; organization-enabled service and membership/resource scopes; delegation and owner-template rules; empty-scope fail-closed behaviour | Notification architecture and delivery; payment gateway, flight and eSIM provider selection; gateway partner-payout capability; F-027/F-043/F-071 and multi-city; settlement schema and lifecycle; other open feature decisions |
| Sections | §4 | §5 |
| Current state | **Ready for owner review** — no item needs information from outside the repository except the owner's yes | **Pending** — provider selection cannot be answered from the repository |

**No Phase A work starts until Gate 0A is approved in full.** Gate 0B does not block Phase A, but no implementation phase that depends on an unresolved Gate 0B item may begin until that item is approved.

### 1.4 What the gates do and do not unblock

Gate 0A exists because Phase A needs a frozen permission catalog and unambiguous scope semantics. Gate 0B exists because Phases D–J need contract decisions. Neither gate requires provider credentials: adapters, state machines, the ledger and settlement logic can all be built and tested against fake providers (the hotel flow needs no provider at all). Only real flight/eSIM/payment traffic requires credentials.

---

## 2. Gate 0 at a glance

### 2.1 Gate 0A — Authorization (blocks Phase A)

| # | Item | Position | Status |
|---|---|---|---|
| 1 | `G0-DEC-01` | Audit and general activity logs permanently excluded; `status_histories` only | **Approved** |
| 2 | `G0-DEC-02` | One centralized authoritative permission catalog | **Approved (principle)** |
| 3 | `G0-DEC-03` | A hotel organization owns many properties; `hotels` is the property entity | **Approved** |
| 4 | `G0-DEC-04` | Only platform staff holding `platform.visa_requests.decide` decide a visa | **Approved** |
| 5 | `G0-DEC-05` | An empty scope means access to nothing | **Approved** |
| 6 | **Catalog content** (§4.1) | Previous 85-key draft withdrawn; revised exact catalog and service eligibility matrix required | **Recommended — awaiting approval** |
| 7 | **P-09** (§4.2) | Replaced by service-aware provider permission eligibility and organization/service/resource scopes | **Owner-approved direction; exact catalog pending** |
| 8 | **Delegation and owner templates** (§4.3) | Family rule, non-delegable keys, owner-only actions and immutable owner templates (DEC-11–DEC-19) | **Approved — implementation in Phase A** |
| 9 | **Empty scopes fail closed** (§4.4) | Approved semantics under `G0-DEC-05`; Phase A implements fail-closed query enforcement and pre-deploy checks | **Approved — implementation in Phase A** |

### 2.2 Gate 0B — Business and integration (does not block Phase A)

| # | Item | Position | Status |
|---|---|---|---|
| 1 | `G0.5` Notifications (§5.1) | Database + e-mail at launch on the database queue; per-channel delivery rows; at-least-once delivery, **not** exactly-once; push/SMS/WhatsApp deferred behind adapters | **Recommended — awaiting approval** |
| 2 | `G0.6` Providers — architecture (§5.2) | Gateway/flight/eSIM adapter contracts + fakes; hotels are first-party inventory and need no provider; unconfigured flight/eSIM search returns `provider_unavailable`; add `payment_webhooks` | **Recommended — awaiting approval** |
| 3 | `G0.6` Providers — selection | *Which* gateway, flight provider and eSIM provider; whether the gateway can execute partner payouts (DEC-10 depends on it) | **Requires clarification** |
| 4 | `G0.7` F-027 seat selection | Defer; free and optional at launch | **Recommended — awaiting approval** |
| 5 | `G0.7` F-043 eSIM usage | Show purchased allowance only; no live remaining-data figure unless a provider exposes one | **Recommended — awaiting approval; clarification advisable** |
| 6 | `G0.7` F-071 tour cancellation | Follow the tour company's own policy; **no platform-invented 24-hour full-refund rule**; terms and refund basis explicit before booking | **Pending — policy not approved** |
| 7 | `G0.7` Multi-city | Remove from v1 scope | **Recommended — awaiting approval** |
| 8 | `G0.8` Settlements (§5.4) | `settlement_periods` + `settlement_items` derived from the ledger, with the full lifecycle in §5.4; `payout_accounts` stays a **proposal** | **Recommended — awaiting approval** |

---

## 3. Approved decisions

### 3.1 G0-DEC-01 — Audit and activity logs permanently excluded

**Current documented situation.**
`BUSINESS-LOGIC.md` BR-107, BR-111 and BR-124, feature F-112 ("Dynamic roles, permissions **& audit**") and the "audit" columns of `ROLE-PERMISSION-SCOPE-MATRIX.md` require an audit trail with actor, timestamp and before/after values. `AUTHORIZATION-ARCHITECTURE.md` §11 states the opposite: audit logs are permanently removed and no UI shows them. The implementation has no audit table and never seeded the audit permission. Phase 0 had already deleted a previously drafted audit architecture (migration, model, service, event, listener, controller, resource, policy, two tests) and recorded the deletion in `IMPLEMENTATION-STATUS.md` row 3. This was conflict **C-01**.

**Decision (approved).**
Permanently excluded. No audit log, no general activity log, no user activity tracking, no audit table, service, event, listener or endpoint. `status_histories` remains and is permitted **only** for business-entity status transitions (booking, visa application, refund, payout request, tour review). It must never be widened into a record of user actions, logins, reads, or permission changes.

**Consequence for the catalog.** `platform.audit.view` is removed and is not re-purposed. The remaining organization permission that reads status histories is renamed `status_history.view` (formerly `team_activity.view`) so its name cannot be mistaken for a general activity feed — see §4.1 and `PERMISSION-CATALOG-RECONCILIATION.md` §8 P-08.

**Database impact.**
- Tables affected: `status_histories` only (already exists, append-only, model throws on update/delete).
- New tables required: **none**. Columns: **none**. Constraints/indexes: **none**.
- Data-integrity implication: every entity whose transitions appear in `status_history.view` must write a status-history row. The feed is therefore bounded by the set of entities that have status histories — the intended constraint, not a gap.

**API impact.**
- Affected API groups: organization reports and every admin mutation response.
- Required endpoints: **no** audit endpoints. One read-only status-history endpoint under the organization reports group, scoped to the caller's organization and service/property scope.
- Authorization: `status_history.view` (company only) and `reports.view`.
- No `audit`, `activity` or `changed_by` block may appear in any resource. Existing attribution columns (`room_rates.updated_by`, `payout_requests.processed_by`, `payments` attribution) are attribution fields, not an activity log, and are permitted.

**Security and tenant isolation impact.**
The deliberate trade-off: after-the-fact reconstruction of *who changed a permission or a role* is not possible. That is the one element of BR-107 with genuine security value, accepted as a residual risk. Compensating controls that remain: single active owner per organization enforced by a unique index; a non-delegable permission list enforced centrally; `permissions_version` bumped on every role/grant/scope change (so stale access ends on the next request, PE-12); append-only status histories; immutable financial rows. Tenant isolation is unaffected — status-history reads are strictly organization-scoped and fail closed under `G0-DEC-05`.

**Dependencies.** Resolves C-01; removes the audit key from the catalog; retitles F-112; retires the Phase K audit item.

**Risks.** If a legal, tax or insurer requirement for an audit trail appears later, it is not a bolt-on: it needs a schema addition plus an interceptor on every mutation, retrofitted across all services. Mitigation available now at no cost: never repurpose `status_histories`, and keep the catalog free of audit keys so nobody believes an audit capability exists.

**Approval status:** **Approved.**

---

### 3.2 G0-DEC-02 — One centralized authoritative permission catalog

**Current documented situation.**
Four conflicting counts: 83 (matrix catalog tables), 84 (naive extraction, inflated by the phantom `platform.audit.view`), 78 (matrix §9 stale self-check), 80 (seeder). Six documented keys are missing from the seeder and four seeded keys are undocumented. Full analysis in `PERMISSION-CATALOG-RECONCILIATION.md`. Conflict **C-02**, with C-03, C-04 and C-17 attached.

**Decision (approved in principle).**
There is exactly one permission catalog, held in one place: the `permissions` table for content and metadata, `config/authorization.php` for delegability, owner templates, families and owner-only actions. **The content of that catalog is not approved by this decision.** The historical 85-key proposal is withdrawn; the active candidate is `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md` and awaits explicit approval of its keys, service applicability and scope metadata.

**Reasoning.** The count cannot be chosen by majority or by seniority of document — it has to be derived from capabilities. The reconciliation unions what the documents require with what the implementation enforces, then removes only what an approved Gate 0 decision makes impossible (`audit.view`, `update_status`) or redundant (`org.profile.publish`). Keeping the split established in Phase 0 (metadata in the table, delegability in config) preserves the single source of truth.

**Database impact.** Data-only changes to `permissions` and the role tables; **no migration**. `scope_requirement` is computed by string prefix in the seeder (`tours.`, `visa_requests.`, `visa_offerings.`, `rooms.`, `rates.`) and `properties.` must join that list as a `property` scope. Removing a key that a custom role references must be handled by the authorization service, not a silent cascade.

**API impact.** No new endpoints; the role editor's available-permission list and grant validation read the frozen catalog. Removed keys must be rejected with a clear error, not ignored.

**Security and tenant isolation impact.** Every owner template, custom role and policy check derives from these keys. The catalog closes the visa-decision escalation, makes publishing a per-property right, and introduces `settlements.accounts.manage` as a non-delegable financial control. The family rule (no `platform.*` key on an organization role) is unchanged and must be asserted on every grant path.

**Dependencies.** Depends on `G0-DEC-01`, `G0-DEC-03`, `G0-DEC-04`. Blocks Phase A entirely, and Phases C–J.

**Risks.** A wrong catalog mis-seeds every tenant at once, silently. Two specific hazards: (a) removing a key later breaks custom roles that reference it, so deletion must be a documented migration, not a seed refresh; (b) granting a permission without checking the organization's enabled service and the member's service/resource scopes could expose unrelated tenant data.

**Approval status:** **Approved in principle; catalog content recommended — awaiting approval** (§4.1).

---

### 3.3 G0-DEC-03 — A hotel organization may own multiple hotel properties

**Current documented situation.**
The matrix defines `properties.view/create/update/publish` for hotel organizations (F-101, F-106), while the implementation modelled hotels directly under a hotel organization and instead had the organization-wide `org.profile.publish`. `OPEN-QUESTIONS.md` Q-49 assumed one property per hotel organization at launch, while `DB-DECISION-BRIEF.md` DEC-14 supports multiple hotels. Conflicts **C-03** and **C-11**.

**Decision (approved).**
- `hotels` **is** the hotel-property entity; it is not renamed and not duplicated.
- A hotel organization **may own many** hotels.
- `membership_hotel_scopes` carries property-level membership access.
- Bookings reference the relevant `hotel_id`.
- Every property access validates the ownership chain (`hotel.organization_id` = the acting membership's organization).
- Hotel owners manage every property of their organization; hotel staff reach only properties in their assigned scope.
- An empty property scope means **no** property access (`G0-DEC-05`).

**Reasoning.** The schema already implements this model — `hotels.organization_id` (FK, restrict), `membership_hotel_scopes` (`unique(membership_id, hotel_id)`), `bookings.hotel_id`, `hotel_bookings.hotel_id` — so the decision aligns the documents with the stronger model already in place instead of weakening the tables to express a one-property constraint. It also makes `properties.*` meaningful and lets BR-110 (publish requires verified legal documents) attach to the correct unit.

**Database impact.**
- Tables affected: `hotels`, `membership_hotel_scopes`, `bookings`, `hotel_bookings`. New tables: **none**. Columns: **none** — all required columns exist.
- Constraints: `hotels` must **not** receive a unique constraint on `organization_id`. `membership_hotel_scopes` unique (`membership_id`,`hotel_id`) is present and correct.
- Nothing at the database level prevents a `membership_hotel_scopes` row pointing at a hotel owned by a *different* organization. That invariant must be enforced in the service layer (and by a policy check on every property-scoped query) until a composite FK on (`hotel_id`,`organization_id`) is considered. Recorded as an implementation requirement, not a migration.
- **Comment defect to fix when code is next touched (not now):** the `membership_hotel_scopes` migration comment reads "No rows = every property the organization owns", which contradicts `G0-DEC-05`. It must read "No rows = no property access".

**API impact.** Property list/create/update/publish within the hotel portal; room-type and rate endpoints nested under a property; booking list/detail filtered by `hotel_id`. Authorization: `properties.*`, `rooms.*`, `rates.*`, `bookings.*` plus the property-scope and ownership-chain checks; publishing additionally requires verified legal documents (BR-110). Every property-scoped request must carry or derive the property id explicitly and must not leak sibling properties to scoped staff. No webhook/job implication.

**Security and tenant isolation impact.** Two boundaries now exist inside one tenant — organization and property — and both must fail closed. A receptionist restricted to one property must not enumerate a sibling property's rooms, rates, arrivals or bookings. Property scope narrows; it never widens. The ownership-chain check must run before the scope check, so a forged `hotel_id` belonging to another tenant is a 404/403, never a cross-tenant read.

**Dependencies.** Depends on `G0-DEC-05` and `G0-DEC-02` (`properties.*`). Blocks Phase E (hotel catalogue/search), Phase G.4 (hotel booking) and Phase H.5 (hotel portal).

**Risks.** If property scope is implemented only in policies and not in the query layer, a scoped employee can still page through siblings. If `hotels` were later given `unique(organization_id)`, the approved model would silently break. If publishing stayed organization-wide, one unverified document set could block or release every property at once.

**Approval status:** **Approved.**

---

### 3.4 G0-DEC-04 — Only platform staff may make the final visa decision

**Current documented situation.**
`ROLE-PERMISSION-SCOPE-MATRIX.md` defined `platform.visa_requests.update_status` for platform staff and a "final decision" description on `platform.visa_requests.review`; `AUTHORIZATION-ARCHITECTURE.md`/DEC-16 removed the company-side status key; `OPEN-QUESTIONS.md` Q-46 still said "company employees with `visa_requests.update_status`"; DEC-07 said "employees record decisions". The implementation seeds `platform.visa_requests.review`/`decide`/`assign` and organization `visa_requests.review`, with no `update_status` anywhere. Conflict **C-04**.

**Decision (approved).**
- Only Traway platform staff holding `platform.visa_requests.decide` may record an approval or rejection.
- Company users may view visa requests within their authorized scope (`visa_requests.view`) and validate documents / flag missing items (`visa_requests.review`). They cannot decide.
- Customer-visible statuses stay exactly `Submitted → Under Review → Documents Required → Processing → Approved / Rejected`. No new customer-facing status is introduced.
- Internal operational transitions (assignment, document requests, notes) are separate from customer-visible statuses.

**Reasoning.** A visa decision is a legal/consular act with financial and regulatory exposure, and the traveler has no contractual relationship with the company beyond the offering. Centralising the decision in platform staff makes the outcome attributable to one accountable actor, keeps a single quality gate across all publishing companies, and matches the schema: `visa_applications.decided_by` / `decided_at` / `rejection_reason` exist for exactly one decider. It also removes the last path by which a partner could approve its own sale.

**Database impact.**
- Tables affected: `visa_applications`, `visa_application_documents`, `status_histories`. New tables: **none**. Columns: **none** — `status`, `assigned_membership_id`, `rejection_reason`, `decided_by`, `decided_at`, `submitted_at` and per-document `status`/`rejection_reason`/`reviewed_by`/`reviewed_at` all exist. Indexes unchanged.
- The decision columns must only be written when the actor holds `decide` **and** the transition is legal; the customer-visible status must never be set by a company-side review action. Internal states beyond the six customer statuses are deliberately **not** proposed: DEC-04 forbids new customer-facing statuses and the current `status` column plus document states is sufficient.

**API impact.** Company: list/detail visa requests within scope, per-document accept/reject, request additional documents. Platform: list/detail all, assign, review, decide (approve/reject with mandatory reason on rejection). Authorization: `visa_requests.view` + `visa_requests.review` (company, service-scoped to `visas`); `platform.visa_requests.view`/`review`/`assign`/`decide` (platform). `update_status` does not exist. Decision and status changes emit notification events (§5.1); no external authority submission exists inside Traway (DEC-07).

**Security and tenant isolation impact.** Removes the partner's ability to approve its own application — the main conflict-of-interest path in the visa module. Company scope remains service- (`visas`) and organization-bound; a restricted employee sees only assigned requests (`assigned_membership_id`, BR-115). Platform access is cross-tenant by design and is the only path that ignores the tenant filter.

**Dependencies.** Depends on `G0-DEC-02` (`review`/`decide`/`assign` are the canonical keys; `update_status` removed) and `G0-DEC-05`. Supersedes the "employees record decisions" phrasing in DEC-07 and resolves Q-46. Blocks Phase H.2 and Phase C (customer tracker copy).

**Risks.** Platform staff become a throughput bottleneck for every visa decision, with no fallback if the visa-officer role is unstaffed. If `visa_requests.review` is loosely interpreted, company staff could request documents in a way that reads as a decision to the customer — the customer-facing status vocabulary must be driven only by platform-confirmed transitions.

**Approval status:** **Approved.**

---

### 3.5 G0-DEC-05 — An empty scope means access to nothing

**Current documented situation.**
BR-121 and MT-11 state that a non-owner with no service scope has no service access. `AuthorizationService` treats an **empty** service or hotel scope as unrestricted, and the `membership_hotel_scopes` migration comment said "No rows = every property". Conflict **C-08**, and Phase A item A1.

**Decision (approved).**
An empty property, organization or resource scope grants access to **nothing**. No empty array and no null scope value may be read as implicit global access. Global access is explicit and independently authorized. Every scoped query fails closed. Authorization validates, in order: permission → active membership → organization ownership → applicable resource scope.

**Reasoning.** Fail-open defaults are the classic source of cross-tenant leaks, and this codebase's default was exactly backwards from its documented rule. An explicit "all"/ownership representation is required so that legitimate full access is still expressible — a hotel owner must see all properties **because they are the owner**, not because their scope list happens to be empty. Making ownership the explicit grant, and empty the denial, means the absence of a row can never be a privilege.

**Database impact.**
- Tables affected: `membership_service_scopes`, `membership_hotel_scopes`. New tables: **none**. Columns: **none**, provided "all" is expressed by ownership (`memberships.is_owner`) rather than by an empty scope set. Constraint: if a non-owner must be granted all properties/services, that is an explicit row per value, which the existing unique constraints support.
- Existing rows with zero scope rows become "no access" rather than "all access". Any seeded or manually created membership relying on the old meaning loses access — the intended correction, covered by the data check in §4.4.

**API impact.** No new endpoints; scoped list/detail endpoints return empty (not forbidden) result sets for members with no applicable scope. An empty list is a legitimate response for a scoped member; a 403 is reserved for a missing permission or a foreign record. No webhook/job implication.

**Security and tenant isolation impact.** Directly closes the highest-severity class of defect found in the audit, and fixes the restricted-employee tier (BR-087/115), which could previously see the whole organization because its scope list was empty.

**Dependencies.** Blocks Phase A (A1/A2) and every later phase's query layer. Required by `G0-DEC-03` and `G0-DEC-04`.

**Risks.** Failing closed can lock out legitimate members whose access was implicitly granted by the old semantics — notably hotel staff created without property rows and company staff created without service rows. The correction must ship with the data check in §4.4; otherwise the first deploy looks like an outage.

**Approval status:** **Approved.**

---

## 4. Gate 0A — Authorization approval

Everything Phase A depends on. Gate 0A is independently reviewable: no item below needs information that is not in this repository.

### 4.1 Revised service-aware catalog content

**Current documented situation.**
The principle is approved (`G0-DEC-02`), but the catalog itself is not: 83 documented, 84 by naive extraction, 78 in a stale self-check, 80 implemented, 88 in the union. Six documented keys are missing from the seeder; four seeded keys are undocumented; `platform.audit.view` exists only as a mention in a validation note; the non-delegable table in `AUTHORIZATION-ARCHITECTURE.md` §7.2 cites a key (`org.delete`) that exists nowhere. Full evidence in `PERMISSION-CATALOG-RECONCILIATION.md`.

**Recommended decision.**
Review and approve the revised service-aware candidate catalog: 37 platform + 48 organization candidate keys, as listed in `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md`. The count is provisional until exact keys and metadata are approved.

**Two different baselines — do not mix them.**

The earlier draft of this register described "9 additions and 3 removals". That was a **union** comparison, not a change to the seeder, and presenting it as "9 new permissions" was wrong. Both baselines are correct for their own question and are now stated separately:

| Baseline | Count | Comparison | Additions | Removals | Renames | Result |
|---|---|---:|---|---:|---:|---|
| **A. Current seeder** (what the code creates today) | 80 (36 platform + 44 organization) | Seeder → final catalog | **6 genuinely new keys** | **1** (`org.profile.publish`) | **1** (`team_activity.view` → `status_history.view`) | 80 + 6 − 1 = **85** |
| **B. Union of documented + code-defined keys** (what exists anywhere) | 88 (39 platform + 49 organization) | Union → final catalog | 9 single-source keys are *retained* (6 exist only in the docs, 3 only in the code) | **3** (`platform.audit.view`, `platform.visa_requests.update_status`, `org.profile.publish`) | see baseline A | 88 − 3 = **85** |

- Under **baseline A** the six new keys are: `platform.settings.manage`, `properties.view`, `properties.create`, `properties.update`, `properties.publish`, `settlements.accounts.manage`. They are new *to the seeder*; five of the six already existed in the documented catalog, which is why the union view counts them as "retained from a single source" rather than new.
- Under **baseline B** the three removals include two documented keys that never existed in code. Only one of the three removals is a change to the seeder.
- The one rename changes no count. The catalog adopts `status_history.view`; the seeder still creates `team_activity.view` and is renamed when the catalog is approved.
- One description narrows: `platform.visa_requests.review` loses "and record the final decision".

**Retained, promoted, renamed, removed — individually.** All fourteen keys that exist in only one source, plus the three decisions, are justified one by one in `PERMISSION-CATALOG-RECONCILIATION.md` §6–7. No key is invented in either source.

**Non-delegable list:** add `settlements.accounts.manage` (`config/authorization.php`).

**Open sub-item:** P-14 — sensitivity value spelling (`access_control` → `access-control`), data value only.

**Reasoning.** Derived, not chosen: union of documented and implemented keys, minus what the approved decisions make impossible or redundant. That is the only method that leaves no orphan keys in either direction.

**Database impact.** No migration. Data-only changes to `permissions` + role assignments; `scope_requirement` prefix list gains `properties.`; `config/authorization.php` non-delegable list gains one key; `access_control` spelling normalised.

**API impact.** No new endpoints. Role editor payloads and grant validation read the frozen list; removed keys are rejected with a clear error.

**Security and tenant isolation impact.** Closes the visa decision escalation path, makes publishing per-property, adds a non-delegable payout-account control, renames the status-history read so it cannot be mistaken for an activity feed, and eliminates a phantom capability.

**Dependencies.** `G0-DEC-01`, `G0-DEC-03`, `G0-DEC-04`. Blocks Phase A.

**Risks.** A wrong key set mis-seeds every tenant at once; later removals break custom roles. The revised exact service-aware catalog and its scope rules must be approved before the seeder or authorization implementation is touched.

**Approval status:** **Recommended — awaiting approval.**

### 4.2 P-09 — service-aware permissions for multi-service providers

The owner confirmed that one service-provider organization may operate multiple service lines. The former blanket restriction of `bookings.*` and `customers.view` to company/hotel organization types is superseded.

**Approved direction.** Permission eligibility is service-aware, not a blanket allow/deny by provider type. A provider may use only operations belonging to services enabled for its organization, and each employee is further restricted by explicit membership service scope and resource/property scope. Generic booking permissions do not authorize visa or eSIM workflows. Customer contact is a minimal contextual projection for records already authorized through a service, never directory access.

**Non-changes.** Flights remain platform-only. Final visa decisions remain platform-only. eSIM operational permission keys are not added until provider order/activation responsibilities are defined.

**Impact.** The exact catalog, allowed service metadata and scope requirements must be recalculated before Phase A. The old 85-key draft is not a seed target. **Approval status:** owner-approved direction; exact catalog pending.

### 4.3 Delegation and owner-template rules

Gate 0A needs these frozen because Phase A rewrites the grant paths. The source of truth is `config/authorization.php`; `PERMISSION-CATALOG-RECONCILIATION.md` §9 is the canonical list.

1. **Family rule.** A `platform.*` key can never be attached to an organization role, and an organization key can never be attached to a platform role. No exceptions. Enforced on every grant path: role create/edit, direct grant, template copy, invitation and transfer.
2. **Non-delegable keys** — an owner may hold them but may never grant them: `employees.create`, `employees.update`, `employees.deactivate`, `roles.manage`, `roles.assign`, `settlements.manage`, `settlements.accounts.manage`, `bookings.refund`.
3. **Owner-only structural actions**, never expressible as a permission string and never delegable: `ownership.transfer`, `organization.close`, `organization.delete`. `AUTHORIZATION-ARCHITECTURE.md` §7.2 previously listed `org.delete` as a permission key; no such key exists and none is proposed.
4. **Owner templates** `company_owner`, `hotel_owner`, `service_provider_owner` are immutable, system-only, derived from the catalog, and never shared with `organization_id = NULL` pending Q-50.
5. **Grant ceiling.** A grantor may grant only permissions and scopes they currently hold (`PE-01`/`PE-02`), never to themselves (`PE-06`).

**Impact.** No schema change. Phase A enforces all five rules in the service layer and asserts them in tests. **Approval status:** **Approved under DEC-11–DEC-19; implementation belongs to Phase A.**

### 4.4 Empty scopes fail closed — enforcement detail

`G0-DEC-05` is approved. Gate 0A adds the enforceable statement Phase A implements:

- A membership with **no** row in `membership_service_scopes` sees no service-scoped resources. A membership with **no** row in `membership_hotel_scopes` sees no property. Neither is "all".
- Full access is expressed by ownership (`memberships.is_owner`) or by explicit rows, never by absence.
- The query layer, not only the policy, applies the scope filter; a scoped list returns an empty set rather than a 403.
- Before deploy, a data check must confirm that every non-owner member who legitimately needs access has explicit scope rows (development data only at present).
- The `membership_hotel_scopes` migration comment is corrected to "No rows = no property access" when Phase A touches the file.

**Approval status:** **Approved under G0-DEC-05; implementation and verification belong to Phase A. No additional business approval is required.**

### 4.5 Gate 0A exit criteria

Gate 0A is approved when the owner has confirmed the revised exact service-aware catalog, provider service eligibility matrix and the required eSIM provider workflow boundary. §4.3 and §4.4 are already approved decisions; Phase A must implement and test them. At that point Phase A may begin with: A1 empty-scope semantics, A2 assigned-only filters, A3 removal of the `X-Organization-Id` fallback, A4 transfer as remove + create, A5 invitation rotation, A6 grant reconciliation, A7 tenant middleware and policy enforcement, A8 the catalog keys and the `status_history.view` rename.

Gate 0A does **not** include, and must not be delayed by: notification delivery design, provider selection, F-027/F-043/F-071, or settlements. Those are §5.

---


### 4.6 Owner-approved multi-service provider authorization direction (2026-11-24)

**Decision.** A single service-provider organization may operate multiple service lines. Organization-enabled services form the tenant capability boundary; each membership has its own service scope, and each service may have a further resource/property scope. Every check is intersected with explicit permissions and organization ownership. Empty scope means no access under G0-DEC-05.

**Permission consequence.** P-09's previous blanket recommendation to deny all `bookings.*` and `customers.view` to service providers is superseded. Permissions are eligible only for the corresponding enabled service and are still restricted by membership service/resource scopes. `customers.view` is contextual/minimal for records the member may already access, not a customer directory. eSIM provider operations require a defined workflow before adding permission keys.

**Non-changes.** Flights remain platform-only. Final visa decisions remain restricted to authorized platform staff under G0-DEC-04. The centralized permission-catalog principle remains approved; the exact catalog and its count are not approved and must be recalculated.

**Status.** Owner-approved direction; documentation updated 2026-11-24. Phase A remains blocked on the revised exact catalog, service eligibility matrix and provider eSIM workflow definition. See [`phase-02/MULTI-SERVICE-PROVIDER-AUTHORIZATION.md`](./phase-02/MULTI-SERVICE-PROVIDER-AUTHORIZATION.md).

## 5. Gate 0B — Business and integration approval

None of these blocks Phase A. Each blocks only the phase that depends on it. None is approved.

### 5.1 G0.5 — Notification architecture and delivery behaviour

**Current documented situation.**
BR-104 requires notifications generated by domain events; BR-105 requires per-user read state; F-021 defines the mobile inbox with filters, grouping, unread dots and mark-all-read. The `notifications` table exists (Laravel standard: uuid id, type, morphs notifiable, data, read_at). There are **no** Events, Listeners, Jobs, Mailables or Notifications, no queue worker configuration beyond the default `jobs` table, no push credentials and no SMS/WhatsApp provider. Q-07 (deep links, push in scope) is Blocking-4 and open.

**Recommended decision — channels and queue.**

| Channel | Launch | Rationale |
|---|---|---|
| Database (in-app inbox) | **Yes** | Satisfies BR-105 natively (`read_at`), needs no external service, and is the source of truth any future push mirrors |
| E-mail | **Yes** | Transactional receipts and visa/settlement notices |
| Push (APNs/FCM) | **No** | No credentials, no provider chosen, no mobile app in this repository (Q-07 open) |
| SMS | **No** | No provider selected |
| WhatsApp | **No** | No provider selected; business-initiated templates must be approved per provider and cannot be assumed |

One domain event per business fact (booking confirmed/cancelled, visa submitted/documents required/approved/rejected, eSIM issued, join request, deletion request, refund requested/settled, settlement transferred, employee deactivated) — BR-104's list, no polling. Two channels at launch: `database` and `mail`. Preferences are by category, not channel: bookings, payments/refunds, visa, eSIM and security are always on; only offers/marketing are opt-out, stored in a `notification_preferences` table added in Phase D. Deep links (Q-07) stay open; the payload carries a typed `{entity, id}` pair rather than a URL.

**Delivery semantics — what is and is not guaranteed.**

The earlier draft claimed that a deterministic UUIDv5 notification ID made delivery safe. It does not. A deterministic primary key makes **notification creation** idempotent; it says nothing about a delivery that is retried, a worker that dies mid-send, or a channel that has already accepted an e-mail. The design is therefore stated honestly:

- **Guarantee: at-least-once delivery per channel, at-most-once notification creation.** Exactly-once e-mail is **not** claimed and cannot be guaranteed without provider-side idempotency.
- **Creation is idempotent.** The notification UUID is a UUIDv5 over `(event class, entity id, recipient user id, milestone)`. Re-emitting the same milestone inserts nothing new; the primary key is the dedupe record. No `dedupe_key` column, no cleanup job.
- **Per-channel delivery is tracked.** A `notification_deliveries` table: `id`, `notification_id` (FK cascade), `channel` (`database|mail|push|sms|whatsapp`), `status` (`pending|sending|sent|failed|skipped`), `attempts`, `last_attempt_at`, `sent_at`, `failed_at`, `error_code` (safe, non-provider text), timestamps, **unique (`notification_id`,`channel`)**. One row per notification per channel; the row is the retry unit.
- **A worker claims before it sends.** The delivery job performs an atomic claim — `UPDATE notification_deliveries SET status='sending', attempts=attempts+1, last_attempt_at=now() WHERE id=? AND status IN ('pending','failed') AND attempts < max_attempts`. Zero rows affected means another worker owns it or the retry budget is exhausted; the job exits without sending. This prevents two concurrent workers sending the same e-mail.
- **A crash after send is possible and is reported as such.** If the process dies after the provider accepted the message but before `status='sent'` was written, the row stays `sending`. A recovery sweep marks rows `sending` older than a configured threshold as `failed` with `error_code=unknown_outcome` and re-queues them. **This may deliver a duplicate e-mail.** That is the accepted cost of at-least-once, and it is why the inbox row (already written, immutable) remains the authoritative record of the notification.
- **Dispatch after commit, creation inside it.** The notification and its `notification_deliveries` rows are inserted **inside** the domain transaction (cheap inserts, so a rollback leaves nothing behind), and the delivery jobs are dispatched **after** commit via `ShouldDispatchAfterCommit` / `DB::afterCommit`. A crash between commit and dispatch is recovered by a sweeper that picks up `pending` delivery rows older than the threshold. This is why creation is not deferred to a listener that could be lost.
- **Retries and failure.** Bounded attempts (default 3) with exponential backoff at job level, then `status='failed'` with the row preserved and surfaced in the admin failure view (Phase K). A terminal delivery failure never fails the business fact, never removes the inbox row, and never rolls back a booking or payment. A manual re-send resets the row to `pending` and increments `attempts`.
- **Queues.** Notification and outbound-delivery work runs on separate queue names from booking/payment work, so a slow mail provider cannot starve financial jobs.
- **Push, SMS, WhatsApp** are deferred behind one messaging contract with a no-op implementation. No SDK is installed until a provider is chosen; domain code emits events and never names a channel.

**Database impact.**
- Tables affected: `notifications` (existing), `jobs`/`failed_jobs` (existing).
- New tables: **`notification_deliveries`** (recommended, above). `notification_preferences` in Phase D. No `dedupe_key` column.
- Constraints: `notification_deliveries` unique (`notification_id`,`channel`); index (`status`,`last_attempt_at`) for the recovery sweep; `notifications` PK on `uuid id` is the creation dedupe.
- The notification `data` payload must render the inbox without joining business tables (so a deleted booking still renders) and must not contain secrets or full passport/IBAN values.

**API impact.** List (filter by category, paginate), unread count, mark one read, mark-all-read (BR-105: only the caller's visible set) for the customer inbox (F-021) and the platform/company/hotel/employee inboxes (F-079, F-092, F-102, F-110). Authorization: ownership-based for customers; organization and property scope for staff inboxes. The category enum matches the filter chips plus visa/eSIM/account; the response carries `read_at`, `created_at`, type and the typed deep-link pair.

**Security and tenant isolation impact.** The recipient must be resolved server-side from the event's owning entity, never from a request parameter — a queue job carrying the wrong recipient is the most common cross-tenant leak. Staff inboxes respect organization and property scope under `G0-DEC-05`. E-mail must never include another party's personal data (a hotel must not receive the customer's passport number).

**Dependencies.** `G0-DEC-05` (inbox scoping), Phase C (identity) for recipient resolution. Blocks Phase D and every later phase that promises "notify the customer".

**Risks.** Deferring push means BR-104's "prompt" expectation is met in-app only until a provider and the mobile app exist. A duplicate e-mail after a crash is possible by design; it must be stated rather than denied.

**Approval status:** **Recommended — awaiting approval.**

### 5.2 G0.6 — Provider strategy: architecture, then selection

**Current documented situation.**
`payments`, `refunds`, `checkout_sessions`, `esim_packages`, `esim_orders`, `hotels`/`room_types`/`room_rates` and the booking tables exist; `bookings.provider_reference` holds the upstream reference. No gateway, flight/GDS provider or eSIM provider is approved or contracted, and no SDK is installed. DEC-10 says partner payouts go through the payment gateway "subject to its capabilities" — unverified. Q-31, Q-40 and Q-44 are Blocking-4.

**Recommended architecture (this part is approvable now).**
Build **adapter contracts plus fake providers**; keep provider choice a configuration decision:
- `PaymentGateway`: initialise/authorise, capture, refund, fetch transaction, verify + parse webhook. `payments.provider` already discriminates implementations.
- `FlightProvider`: search, revalidate, book, cancel, refund status.
- `HotelProvider`: **not required at launch** — hotels, room types and daily rates are first-party Traway inventory, entered through the hotel portal, so hotel search/availability/booking is fully implementable with no external provider.
- `EsimProvider`: sync catalogue, availability/pricing, purchase, provisioning, activation, status/usage (usage optional, F-043).
- Fakes for every contract, enabled only in non-production through a config guard; production refuses to boot with a fake provider in a money or inventory slot.
- Flights and eSIM **without** a provider return `provider_unavailable` (503 + stable code) — never simulated availability or prices, never an empty list pretending to be a result.

**Payment gateway contract detail.**

| Operation | Input | Output | Notes |
|---|---|---|---|
| `initialize` | amount, currency, idempotency key, customer ref, return URL | provider intent id, client secret/redirect | Never called twice for the same idempotency key |
| `confirm`/`capture` | provider intent id | status, captured amount, provider payment id | May be driven by webhook instead of a poll |
| `refund` | provider payment id, amount, reason | provider refund id, status | Partial refunds must be expressible even if a given provider lacks support |
| `fetchTransaction` | provider reference | status, amount, fees | Used by reconciliation |
| `verifyWebhook` | raw body, signature headers | verified event DTO | Mandatory, adapter-specific, over the raw body (re-serialising breaks HMACs) |
| `parseWebhook` | verified payload | event type, provider payment/refund id, amount, failure code | Pure mapping, no side effects |

Create the `payments` row first (`status=pending`, `idempotency_key` from the request, `amount_cents` from the revalidated checkout total), then call `initialize`, so a crash between the two is reconcilable. **Only a verified webhook or an authenticated `fetchTransaction` may move a payment to `captured`; a browser redirect is not proof of payment.** Three idempotency layers already exist in the schema: `payments.idempotency_key` unique, `unique(provider, provider_payment_id)`, `partner_ledger_entries.entry_key` unique. Refunds store what the gateway reports (`gateway_fee_cents`) — Traway never computes a cancellation fee (DEC-01). Gateway errors map to a small stable vocabulary (`card_declined`, `insufficient_funds`, `expired_card`, `3ds_required`, `provider_unavailable`, `duplicate_request`) in `payments.failure_code`; raw provider text is never surfaced or logged.

**Flight and hotel adapter shape.**

| Stage | Flight | Hotel |
|---|---|---|
| Search | `search(criteria)` → offers with opaque `offer_token`, price, expiry | Not required — first-party inventory |
| Availability | included in search, re-checked on revalidate | `room_rates` per room type per date (exists) |
| Price validation | `revalidate(offer_token)` immediately before payment (DEC-05: no fare hold) | recompute from `room_rates` at booking |
| Booking confirmation | `book(offer, passengers)` → PNR + ticket numbers | internal: decrement availability atomically, create booking |
| Cancellation | `cancel(provider_reference)` → policy + fee | internal policy from `room_types.cancellation_policy` / `free_cancellation_hours_before` |
| Refund status | `refundStatus(provider_refund_id)` | internal refund record |

`checkout_sessions.quote` already stores the snapshot (`quoted_total_cents`, `revalidated_total_cents`, `price_changed_at`, `price_change_accepted_at`): quote → revalidate at payment → if the total differs, set `price_changed` and require explicit consent before charging. Every adapter call gets a bounded timeout, a per-provider circuit breaker and a mapped error (`provider_timeout`, `provider_unavailable`, `sold_out`, `price_changed`). Order of operations: reserve/confirm with the provider → capture → commit the booking; if capture fails after provider confirmation, the cancellation path runs.

**eSIM provider contract.**

| Operation | Purpose | Schema already available |
|---|---|---|
| `listPackages` / `getPackage` | Catalogue synchronisation | `esim_packages` (`provider`, `provider_package_id`, `cost_cents`, `sale_price_cents`, `is_stale`, `synced_at`) |
| `checkAvailability` | Whether a package can still be sold | `is_active` / `is_stale` |
| `purchase(package, order)` | Buy the eSIM | `esim_orders.provider_order_id` |
| `provision(order)` | Obtain the activation payload (LPA / QR) | `esim_orders.activation_payload` (encrypted) |
| `getStatus(order)` | pending/issued/activated/expired | `esim_orders.status`, `activated_at` |
| `getUsage(order)` | Remaining data | **No source confirmed** — F-043 |

Sync upserts by (`provider`,`provider_package_id`); on failure keep the last known good catalogue, set `is_stale=true` and **never silently delist**. Sale price is the stored `sale_price_cents` (BR-008), never recomputed from cost + markup at display time. The activation payload is a secret: never in a list response, never logged, shown only to the owning customer (BR-058 single-use display — an `activation_payload_viewed_at` column may be required; not proposed until BR-058's intent is confirmed).

**Database impact.**
- New table: **one, recommended** — `payment_webhooks` (`id`, `provider`, `provider_event_id` unique per provider, `type`, `payload` json, `received_at`, `processed_at`, `error`). Every gateway delivers at-least-once; without a raw-event record a duplicate capture cannot be diagnosed and `unique(provider, provider_payment_id)` cannot distinguish a replay from a genuine second event.
- Columns: none mandatory. Existing provider-reference columns cover the adapters.
- Constraints: new `payment_webhooks` unique (`provider`,`provider_event_id`).

**API impact.** Checkout initialise/confirm, refund request; flight search and revalidate-and-book; hotel availability and book; eSIM packages and purchase/QR; one unauthenticated webhook receiver per provider prefix with mandatory signature verification. Idempotency key required on every money-writing call; price changes require explicit consent; provider errors mapped to safe codes.

**Security and tenant isolation impact.** Credentials stay server-side and out of the browser; webhooks are unauthenticated by nature, so signature verification is the only boundary — a forged webhook that marks a booking paid is a direct financial loss. Fake providers must be impossible to enable in production.

**Dependencies.** Blocks Phase G and Phase J. Requires owner clarification on gateway, flight provider, eSIM provider, and gateway partner-payout capability.

**Risks.** A gateway without partner payouts invalidates the DEC-10 payout design; a flight provider with no cancellation/refund API makes F-049 unbuildable; a GDS with no seat data makes F-027 impossible. Building against fakes risks discovering late that a real provider cannot express something the fake could.

**Approval status:** **Architecture recommended — awaiting approval. Provider selection requires clarification.**

### 5.3 G0.7 — F-027, F-043, F-071 and multi-city

#### F-027 — Seat selection (defer)

The catalogue marks it `U`: only a confirmation bar with three seats and «مجاناً» is drawn — no seat map, no inventory, no pricing rule, no provider integration. **Recommended:** defer entirely; seats are free and optional at launch (Q-09's working assumption); the passenger step completes without a seat choice; no seat data is stored. Deferring needs nothing; implementing would need seat inventory per segment, seat assignment per passenger and provider seat maps that no approved provider supplies. **Clarification essential? No.**

#### F-043 — eSIM usage and balance (allowance only)

The screen shows «5 GB متبقية» and a progress bar, but there is no usage source, refresh rule or depletion behaviour (Q-17). **Recommended:** display the **purchased allowance** (`esim_packages.data_mb`, `validity_days`) and the order status, never a "remaining" figure, unless the selected provider exposes a usage API — in which case the number and its refresh remain a separate, later approval. Hide the progress bar if no source exists. No usage table, no polling. **Clarification essential? Yes** — whether the product expects a live remaining-data figure (and therefore a provider with a usage API) or an allowance display.

#### F-071 — Tour cancellation and refund (**pending; no policy approved**)

The catalogue marks it `U` with a UI promise «إلغاء مجاني خلال 24 ساعة» and no cancellation screen, deadline calculation or refund rule (Q-24).

**What must not happen.** The 24-hour full-refund rule proposed in the earlier draft of this register was a **recommendation only** and is **not approved**. The platform must not invent a mandatory full-refund rule, and no document may present a 24-hour window, a percentage, a fee schedule or any other refund arithmetic as Traway policy.

**Documented position (approved in principle only as a principle).**
- Cancellation and refund eligibility follow **the applicable tour company's policy** for that tour — the partner selling the tour, not a platform default.
- The refund calculation and the cancellation terms must be **explicit and available to the customer before booking**, on the tour detail and again at checkout, with the accepted terms recorded against the booking.
- Traway does not compute a cancellation fee; any fee the gateway reports is stored, not derived (DEC-01).
- What remains open: the policy model (a free-text policy plus a structured window and fee basis per tour, or a small set of platform-defined policy presets the partner selects), the deadline reference point (slot start vs booking time), whether the customer self-cancels or requests cancellation, and what happens when the partner cancels a paid slot.
- Until the owner approves a policy, F-071 stays `U`, the cancellation flow stays unbuilt, and the UI copy must not promise a specific free-cancellation window.

**Implementation note.** `tour_slots` already holds the start time and `bookings`/`refunds`/`status_histories` already exist, so no schema is needed to *record* a policy decision; the open question is what the policy is, not how to store a booking. A per-tour policy field is the likely shape and needs no migration until the model is chosen.

**Clarification essential? Yes** — this is a customer-facing promise and a business decision. **Approval status:** **Pending; no policy approved.**

#### Multi-city flights (C-13 — remove from scope)

F-022 offers multi-city; BR-036 forbids partial implementation; Q-08 defers it; `flight_bookings.trip_type` already constrains `one_way|round_trip`. **Recommended:** remove it from v1 scope and hide the option; the enum already rejects it, so no schema or API change is needed. **Clarification essential? No.**

**Approval status (whole item):** F-027 and multi-city **recommended — awaiting approval**; F-043 **recommended, clarification advisable**; F-071 **pending — policy not approved**.

### 5.4 G0.8 — Settlement schema and lifecycle

**Current documented situation.**
BR-098–BR-100, BR-114 and DEC-10 require settlement periods with states `pending → processing → transferred` (with `failed`), partner dues reconciling to the cent with the admin view, refunds reducing the next settlement, and on-demand payouts. The schema has `payout_accounts`, `payout_requests` (`requested|processing|transferred|failed|rejected`, `method` manual|gateway) and an append-only `partner_ledger_entries` (`type` ∈ `sale|commission|refund|gateway_fee|chargeback|payout|adjustment`, signed `amount_cents`, unique `entry_key`) — but **no** settlement table, so a period cannot be represented, locked or reconciled. Conflict **C-16**.

**The five layers, kept strictly separate.**

| Layer | What it is | Where it lives | Mutable? |
|---|---|---|---|
| 1. Ledger entries | Immutable signed facts | `partner_ledger_entries` (`entry_key` unique) | **No** — append-only |
| 2. Partner balance | `SUM(amount_cents)` for the organization | **Derived**, never stored | n/a |
| 3. Settlement | A closed claim over a specific set of ledger entries | `settlement_periods` + `settlement_items` (**proposed**) | Status and links only, until transferred |
| 4. Payout request | The partner asking for money already due | `payout_requests` (exists) | Status only |
| 5. Payout execution | The actual transfer, manual or via gateway | `payout_requests.method` + `provider_payout_id` + `transferred_at` | Status only |

**Recommended schema.**

- `settlement_periods`: `id`, `organization_id` (FK restrict), `period_start` (date), `period_end` (date, exclusive), `status` (`pending|processing|transferred|failed`), `sales_cents`, `commission_cents`, `refunds_cents`, `gateway_fees_cents`, `adjustments_cents`, `due_cents`, `payout_request_id` (nullable FK restrict), `computed_at`, `transferred_at`, `failure_reason`, timestamps. **unique (`organization_id`,`period_start`,`period_end`)**; index (`organization_id`,`status`) and (`status`,`period_end`).
- `settlement_items`: `id`, `settlement_period_id` (FK cascade), `partner_ledger_entry_id` (FK restrict, **globally unique**), `amount_cents`, timestamps.
- Partner balance remains `SUM(partner_ledger_entries.amount_cents)`. BR-114 holds structurally: the partner's settlement total and the admin's partner-due figure are both `SUM(settlement_items.amount_cents)` over the same period.

**Eligibility — which entries may be settled.**

- **Eligible `type` values:** `sale`, `commission`, `refund`, `gateway_fee`, `chargeback`, `adjustment`.
- **Excluded:** `payout`. A payout entry records the execution of a settlement, so claiming it in a settlement would count the same money twice. Payout entries are reconciled against `payout_requests`, not against periods.
- **Eligibility window:** an entry is eligible for the period whose half-open interval contains its **`created_at` (posting time)**, not the date of the underlying booking or refund. A refund posted in March for a February sale settles in March. This is deliberate: the ledger is the truth and the alternative (allocating entries back to the booking month) requires mutable period totals.
- **Eligibility state:** the entry exists, belongs to the organization, is not already referenced by any `settlement_items` row, and the period is `pending` or `failed`.

**Period lifecycle.**

| Transition | Allowed from | Rule |
|---|---|---|
| compute | — (new) or `pending` | Insert the period, select eligible entries, insert `settlement_items`, set `computed_at`. Runs inside one transaction with a row lock on the period and a per-organization advisory lock. |
| recompute | `pending`, `failed` | Delete the period's `settlement_items` and re-link from scratch, then recompute the totals. Only legal before `transferred`. |
| submit | `pending` | Creates or links a `payout_request`, sets `status=processing`, freezes the items. |
| transfer | `processing` | Sets `transferred` + `transferred_at`. **This is the only closure** (BR-099); the period and its items become immutable. |
| fail | `processing` | Sets `failed` + `failure_reason`; items are **retained** so the failed numbers stay auditable, and the period may be recomputed or retried. |

**Reopening a closed period.** A `transferred` period is **never** reopened, edited or recomputed. There is no supported path back from `transferred`. `pending` and `failed` periods are the only "open" states, and recompute is the only way to change them. Corrections after transfer are new ledger entries in a later period, never edits to a closed one.

**Refunds and chargebacks after closure.** They post new negative ledger entries through the normal path and are claimed by the next open period. They are never written into a closed period. If the negative entries exceed the next period's positives, `due_cents` is negative and the balance carries forward (DEC-10's negative balance → reduce the next settlement), which is arithmetic on a signed ledger rather than a special case.

**Failed settlements.** A `failed` period never releases value to the partner. Because the partner balance is derived from the ledger — not from periods — a failed transfer cannot leave the balance wrong; it only means the claim was not executed. Items stay linked (auditable), the period is retryable as-is, and repeating the transfer attempt is safe because the ledger has no new entries until a payout entry is posted.

**Timezone and cutoffs.** Period boundaries are calendar intervals in a configured **settlement timezone** (`config('traway.settlement_timezone')`, default UTC; the business timezone still to be approved), evaluated as `[period_start 00:00:00, period_end 00:00:00)` — start inclusive, end exclusive, so no entry can fall in two adjacent periods. Timestamps are stored in UTC; the comparison converts to the settlement timezone. The cadence (weekly or monthly) is **not** decided and is part of this approval. Overlapping periods for one organization are prevented by `unique(organization_id, period_start, period_end)` plus an application check that no existing period overlaps the new interval.

**Preventing a second settlement of the same entry.** Three independent guards: (1) `settlement_items.partner_ledger_entry_id` is **globally unique**, so an entry can be claimed by exactly one period ever; (2) `unique(organization_id, period_start, period_end)` stops two periods covering the same interval; (3) compute runs in one transaction with a row lock on the period and a per-organization lock, so two concurrent computes cannot both claim the same entries. The unique constraint, not careful arithmetic, is what makes BR-100 ("deducted twice") impossible.

**Payout accounts — explicitly not approved.** `payout_accounts` exists (encrypted account number/IBAN, one default per organization via `default_owner_key` unique, `pending|verified|rejected`, `verified_by`/`verified_at`). **This register does not endorse it.** Open items: who verifies an account and on what evidence; what happens when an account changes after a payout (re-verification window); whether an account is per-currency (USD only, so currently moot); whether removal is soft-delete only. Recommendation: treat the table as a draft and add change-control semantics (a change requires re-verification and blocks payouts until verified) before Phase J. `settlements.accounts.manage` being non-delegable is a catalog decision (§4.1), not an approval of the account model.

**Payout execution.** `method` already distinguishes `manual` from `gateway`. Manual is the only mode that can be guaranteed: platform staff transfer outside Traway and record `transferred_at` + `processed_by`. Gateway payouts depend on a capability the selected gateway may not have (DEC-10's own caveat), so the schema must never *require* `provider_payout_id`.

**API impact.** List periods and their items; compute/recompute a period (platform); request an on-demand payout; mark transferred/failed; export. Authorization: `platform.finance.view`, `platform.finance.settlements.execute`; partner side `settlements.view`/`settlements.manage`; `settlements.accounts.manage` for destination changes (non-delegable). The partner view and the admin partner-dues row must come from the same aggregation code path; amounts in cents, USD only. Gateway payout callbacks reuse the `payment_webhooks` pattern.

**Security and tenant isolation impact.** Settlements are the highest-value cross-tenant risk: a partner must see only its own periods and items, and an organization must never trigger a payout to an account it does not own. Account changes are owner-only and non-delegable; execution is platform-only.

**Dependencies.** Depends on Phase G (payments/refunds produce the entries a period closes) and `G0.6` (gateway payout capability). Blocks Phase J.

**Risks.** Building settlements before payments/refunds are stable produces unverifiable numbers. A mutable "balance" column would create a second source of truth that drifts. Assuming gateway payouts exist could force a manual-only launch — acceptable, but it must be stated to partners.

**Approval status:** **Recommended — awaiting approval**, with the period cadence and settlement timezone explicitly included in the approval.

---

## 6. Conflict resolution status

Every conflict from `IMPLEMENTATION-GAP-ANALYSIS.md` §9, with its status after this register. Nothing is deleted from the analysis; superseded conflicts are marked resolved and linked here.

| ID | Conflict | Status | Resolved by / next step |
|---|---|---|---|
| C-01 | Audit required vs permanently removed | **RESOLVED** | `G0-DEC-01` — audit and activity logs permanently excluded; `platform.audit.view` removed |
| C-02 | Permission counts 83/84/78/80 | **RESOLVED (evidence) / awaiting approval (content)** | `G0-DEC-02` + `PERMISSION-CATALOG-RECONCILIATION.md`; the 85-key content needs approval (§4.1) |
| C-03 | `properties.*` vs hotels-as-profile | **RESOLVED** | `G0-DEC-03` — `hotels` is the property entity; `properties.*` canonical; `org.profile.publish` removed |
| C-04 | Visa transition authority | **RESOLVED** | `G0-DEC-04` — platform-only decision; `update_status` removed; company review retained |
| C-05 | Role naming/count (`org_owner` vs `company_owner`, R-18/R-19) | **Partially resolved** | Canonical names are the seeded `company_owner`/`hotel_owner`/`service_provider_owner`. Role **count** still differs (docs list 19, seeder creates 5 platform + 11 templates = 16). Documentation sub-item under Gate 0A; no code impact |
| C-06 | `bookings.view` organization scope | **SUPERSEDED** | Service-aware eligibility and resource scoping in `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md`; no blanket provider deny/allow |
| C-07 | Tenant context from `X-Organization-Id` header | **OPEN — not a Gate 0 decision** | Phase A item A3 (remove the header fallback) |
| C-08 | Empty scope = all vs none | **RESOLVED** | `G0-DEC-05` + §4.4 — empty means nothing; fail closed |
| C-09 | Membership transfer mutates `organization_id` | **OPEN — not a Gate 0 decision** | Phase A item A4 (remove + create, clear scopes) |
| C-10 | Invitation resend semantics | **OPEN — not a Gate 0 decision** | Phase A item A5 |
| C-11 | One property vs many per hotel org | **RESOLVED** | `G0-DEC-03` — many properties; Q-49 answered |
| C-12 | Global NULL-team owner templates | **OPEN — not a Gate 0 decision** | Q-50 remains; recommend accepting copy-on-create (already implemented) and documenting it |
| C-13 | Multi-city offered vs forbidden | **RECOMMENDED** | §5.3 — remove from v1 scope; awaiting approval |
| C-14 | F-027, F-043, F-071 unresolved | **RECOMMENDED / PENDING** | §5.3 — defer F-027 and F-043 usage (recommended); F-071 policy **not approved** |
| C-15 | Phantom feature references F-113/F-115 | **RESOLVED** | Documentation correction — matrix rows re-pointed to F-101/F-106/F-096/F-108 |
| C-16 | Settlement schema absent | **RECOMMENDED** | §5.4 — `settlement_periods` + `settlement_items` and the full lifecycle; awaiting approval |
| C-17 | Settings authority missing | **RESOLVED** | `G0-DEC-02` — `platform.settings.manage` added; `platform_settings` table already exists |
| C-18 | Three legal documents vs generic document table | **OPEN — not a Gate 0 decision** | Requires a document-type enum decision; Phase H.3 |

**Summary (18 conflicts total):** **8 resolved** — C-01, C-02 (evidence only; content awaiting approval), C-03, C-04, C-08, C-11, C-15, C-17. **4 recommended, awaiting approval** — C-13, C-14, C-16 (and C-14's F-071 part pending). **1 partially resolved** — C-05. **6 carried to Phase A or later** — C-06, C-07, C-09, C-10, C-12, C-18. 8 + 4 + 1 + 6 = 19 assignments across 18 conflicts, because C-14 has a recommended part (F-027, F-043) and a pending part (F-071).

---

## 7. Documentation corrections

Corrections identified while producing this register. Status is per document; nothing here changes code.

| # | Document | Correction | Status |
|---|---|---|---|
| D-01 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §3 header and totals | 83 → **85** (37 platform + 48 organization), labelled proposed pending approval | **Applied** |
| D-02 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §9 | Row 1 → 85 (37+48); row 2 no longer cites `platform.audit.view`; row 5 → 48/48 | **Applied** |
| D-03 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §3.1 | Remove the `update_status` row; narrow the `review` description; add `assign` and `decide`; correct F-115 → F-096/F-108 | **Applied** |
| D-04 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §3.2 | Add `visa_requests.review`; rename `team_activity.view` → `status_history.view`; correct F-113 → F-101/F-106 | **Applied** |
| D-05 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §4, §5, §8 | New keys added to the role tables; `status_history.view` renamed in every table; service-aware provider eligibility note and catalog link | **Applied** |
| D-06 | `AUTHORIZATION-ARCHITECTURE.md` §7.2/§8 | `org.delete` is an owner-only **action**, not a permission — reworded | **Applied** |
| D-07 | `AUTHORIZATION-ARCHITECTURE.md` §11 | No change required — §11 already states audit is removed, now confirmed policy under `G0-DEC-01` | **No change required** |
| D-08 | `FEATURES-CATALOG.md` F-112 | Retitled "Dynamic roles and permissions"; audit clause dropped; catalog reference updated to the service-aware candidate catalog | **Applied** |
| D-09 | `BUSINESS-LOGIC.md` BR-107, BR-111, BR-124 | Marked superseded by `G0-DEC-01`, original text preserved | **Applied** |
| D-10 | `BUSINESS-LOGIC.md` BR-121 | Consistent with `G0-DEC-05`; pointer added | **Applied** |
| D-11 | `phase-02/OPEN-QUESTIONS.md` Q-38, Q-46, Q-49 | Answered by `G0-DEC-01`, `G0-DEC-04`, `G0-DEC-03` | **Applied** |
| D-12 | `IMPLEMENTATION-GAP-ANALYSIS.md` §9 | C-01/03/04/08/11/17 marked resolved and linked here | **Applied** |
| D-13 | `IMPLEMENTATION-STATUS.md` | Gate 0A/0B state recorded | **Applied** |
| D-14 | `AUTHORIZATION-ARCHITECTURE.md` §14 | "83 permission keys — 36 platform, 47 organization" → the proposed 85 | **Applied** |
| D-15 | `FEATURES-CATALOG.md` summary, `DB-DECISION-BRIEF.md` §9.1 | Permission-key totals stated as 78 → the proposed 85, with the draft estimate recorded | **Applied** |

### 7.1 Documentation cleanup review

Every file under `docs/` was reviewed against the deletion criteria (duplicated by a newer canonical document, obsolete, superseded by a decision, contradictory with this register, temporary working notes, misleading about current architecture, unreferenced and redundant).

**No documentation file was deleted.** Each one either remains authoritative for a distinct subject or preserves information no other document carries:

- `UI-SCREENS-INVENTORY.md` and `SCREEN-COVERAGE-REPORT.md` hold the `M-`/`D-` screen IDs that every other document cites, plus the coverage evidence. Not reconstructible.
- `USER-ROLES-AND-PERMISSIONS.md` is the role narrative (R-01–R-19 with responsibilities per portal); the matrix is the key table. Deleting either loses the other's content.
- `DB-DECISION-BRIEF.md` records the option analysis behind `DEC-01`–`DEC-10`, which `OPEN-QUESTIONS.md` §2.9 summarises but does not reproduce.
- `WORKFLOWS.md`, `BUSINESS-LOGIC.md`, `FEATURES-CATALOG.md`, `AUTHORIZATION-ARCHITECTURE.md` each own a distinct requirement namespace (WF-, BR-, F-, L/PE-).

The real duplication risk was subject ownership, not file count: two documents describing the same decision differently. That is now fixed by [`README.md`](./README.md) (one owner per subject plus the rule that a decision is recorded once, in this register, and linked from elsewhere) and by the corrections above, not by deleting history. Any contradiction that remains is marked superseded in place, so the original requirement stays readable.

---

## 8. Approval checklist

Approve each line individually. Nothing in Phase A starts until Gate 0A is approved in full.

### Gate 0A — Authorization

**Already approved — no action needed**

- [x] **G0-DEC-01** Audit and activity logs permanently excluded; `status_histories` restricted to business status transitions.
- [x] **G0-DEC-02 (principle)** One centralized authoritative permission catalog.
- [x] **G0-DEC-03** A hotel organization owns multiple hotel properties; `hotels` is the property entity; `membership_hotel_scopes` scopes staff; bookings carry `hotel_id`.
- [x] **G0-DEC-04** Only platform staff with `platform.visa_requests.decide` may approve or reject a visa; company users review documents only; the six customer statuses unchanged.
- [x] **G0-DEC-05** An empty scope grants nothing; every scoped query fails closed.

**Awaiting your approval**

- [ ] **§4.1 Catalog content** — derive and approve the revised exact permission catalog with service-aware eligibility and scope requirements. The former 85-key proposal is withdrawn as a seed target.
- [ ] **§4.2 Provider service/resource matrix** — approve enabled-service eligibility, employee service scopes, resource/property scopes, contextual customer-data rules and the eSIM operational boundary.
- [ ] **§4.3 Delegation and owner templates** — family rule, 8 non-delegable keys, 3 owner-only actions, 3 immutable templates, grant ceiling.
- [ ] **§4.4 Empty-scope enforcement detail** — query-layer filter, ownership as the explicit full-access grant, pre-deploy data check, migration comment correction.

### Gate 0B — Business and integration

- [ ] **§5.1 Notifications** — database + e-mail over the database queue; deterministic notification IDs for creation only; `notification_deliveries` per-channel tracking; **at-least-once delivery, no exactly-once e-mail claim**; transactional categories always on; push/SMS/WhatsApp deferred.
- [ ] **§5.2 Provider architecture** — gateway/flight/eSIM adapter contracts with fakes; hotels are first-party inventory; unconfigured flight/eSIM search returns `provider_unavailable`; add `payment_webhooks`.
- [ ] **§5.2 Provider selection** — *which* gateway, flight provider and eSIM provider, and whether the gateway supports partner payouts. **Requires your answer.**
- [ ] **§5.3 F-027** — defer seat selection; free and optional.
- [ ] **§5.3 F-043** — show purchased allowance only; **confirm** whether a live remaining-data figure is expected.
- [ ] **§5.3 F-071** — decide the tour cancellation policy. **No policy is approved.** The documented requirement is that eligibility follows the tour company's policy and that terms and refund basis are explicit before booking; the actual rule, deadline semantics and fee basis need your decision.
- [ ] **§5.3 Multi-city** — remove from v1 scope.
- [ ] **§5.4 Settlements** — `settlement_periods` + `settlement_items` with the eligibility, lifecycle, closing, reopening, failure, timezone and double-settlement rules in §5.4; **approve the period cadence and settlement timezone**; `payout_accounts` remains a proposal.

### Carried to Phase A (not a gate, listed so nothing is lost)

- [ ] A1 empty-scope semantics (decision approved; implementation pending)
- [ ] A3 remove the `X-Organization-Id` header fallback
- [ ] A4 membership transfer as remove + create
- [ ] A5 invitation resend/rotation
- [ ] C-05 role count, C-06 `bookings.view` scope, C-12 global templates, C-18 legal document types

### Explicit non-goals

- No Phase A implementation started.
- This documentation-only update is committed to `main`; no application code, migration, model, service, controller, policy, middleware, route, seeder or test was modified.
- No `.env` change, migration run, database change or dependency change.



### 4.7 Service-aware permission catalog — revised candidate

The active candidate catalog is [`phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md`](./phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md). It preserves 37 platform and 48 organization candidate keys while introducing service applicability and scope requirements. The 85-key figure is a candidate count only, not an approved final catalog.

**Gate 0A must now explicitly approve:** organization eligibility, enabled-service applicability, membership service scopes, resource/property scope requirements, owner-template derivation, contextual customer-data projection and the exclusion of provider-side eSIM operations until Q-53 is answered. Q-54 and Q-56 block authorization implementation; Q-55 blocks provider portal implementation.

A code review also found a fail-closed mismatch: `Membership::isRestrictedToHotel()` currently treats no hotel-scope rows as unrestricted, while the approved property-scope rule requires no access for non-owner staff. Correct this before release and add negative tests. No code change is included in this documentation update.
