# Gate 0 Decision Register

**Project:** Traway
**Date:** 2026-11-24
**Status:** Open — 4 decisions approved, 5 awaiting owner approval
**Source of the blocking list:** `IMPLEMENTATION-GAP-ANALYSIS.md` §11 "Gate 0"
**Companion documents:** [`PERMISSION-CATALOG-RECONCILIATION.md`](./PERMISSION-CATALOG-RECONCILIATION.md) · [`IMPLEMENTATION-GAP-ANALYSIS.md`](./IMPLEMENTATION-GAP-ANALYSIS.md) · [`IMPLEMENTATION-STATUS.md`](./IMPLEMENTATION-STATUS.md) · [`phase-02/OPEN-QUESTIONS.md`](./phase-02/OPEN-QUESTIONS.md)

> **This register is documentation only.** No application code, migration, model, service, controller, policy, middleware, route, seeder, test or `.env` file was created or modified in producing it. Phase A has not started.

---

## 1. Conventions used in this register

### 1.1 Decision identifiers and the ID collision

`docs/phase-02/OPEN-QUESTIONS.md` §2.9 already records ten business decisions numbered **DEC-01 – DEC-10** (USD only, coupon rules, reference formats, no fare hold, visa payment, manual visa processing, hotel search inputs, deletion grace, settlement policy). Those IDs are referenced throughout `BUSINESS-LOGIC.md` and `FEATURES-CATALOG.md` and cannot be reused without corrupting every cross-reference.

The five decisions approved for this task were supplied labelled `DEC-01 – DEC-05`. They are registered here as **`G0-DEC-01` – `G0-DEC-05`** so that the two sets never collide. The mapping is explicit:

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
| **Already approved** | The owner has approved this decision; it is authoritative |
| **Recommended — awaiting user approval** | A single recommendation is made and needs the owner's yes |
| **Requires clarification** | The owner must supply information the documents and code cannot determine (provider selection, business intent) |

No recommendation in this register is labelled "approved".

### 1.3 Gate 0 item → register record

| Gate 0 item (§11 of the gap analysis) | Register record | Approval status |
|---|---|---|
| G0.1 Audit in/out | `G0-DEC-01` (§3.1) | **Already approved** |
| G0.2 Freeze the permission catalog | `G0-DEC-02` (§3.2) + catalog content (§4.1) | Principle approved; **content recommended — awaiting approval** |
| G0.3 Property model vs hotels-as-properties | `G0-DEC-03` (§3.3) | **Already approved** |
| G0.4 Visa transition authority | `G0-DEC-04` (§3.4) | **Already approved** |
| G0.5 Notification channels and provider strategy | §4.2 + Topic A (§5.1) | **Recommended — awaiting approval** |
| G0.6 Payment / flight / hotel / eSIM providers | §4.3 + Topics B–D (§5.2–5.4) | **Requires clarification** (provider selection); architecture recommended |
| G0.7 Unresolved features F-027, F-043, F-071 and multi-city | §4.4 + Topic F (§5.6) | **Recommended — awaiting approval** |
| G0.8 Settlement schema | §4.5 + Topic E (§5.5) | **Recommended — awaiting approval** |
| *(cross-cutting, not a Gate 0 item)* Empty scope = no access | `G0-DEC-05` (§3.5) — unblocks Phase A item A1 | **Already approved** |

### 1.4 What Gate 0 does and does not unblock

Gate 0 exists because Phase A (authorization repairs) needs a frozen permission catalog and unambiguous scope semantics, and Phases E–J need contract decisions. Gate 0 does **not** require provider credentials: the adapters, state machines, ledger and settlement logic can all be built and tested against fake providers. Only real flight/eSIM/payment traffic requires credentials (§5.2–5.4).

---

## 2. Summary of remaining approvals required

| # | Decision | Recommendation in one line | Status |
|---|---|---|---|
| 1 | **G0.2 content** — the 85-key catalog | Adopt 37 platform + 48 organization keys; remove `platform.audit.view`, `platform.visa_requests.update_status`, `org.profile.publish`; add 9 retained keys | Recommended — awaiting approval |
| 2 | **G0.5** — notifications | Database + e-mail at launch on the database queue; push/SMS/WhatsApp deferred behind adapters; deterministic notification IDs for dedupe | Recommended — awaiting approval |
| 3 | **G0.6** — providers | Gateway/adapter contracts + fake providers now; hotels are first-party inventory so the hotel flow needs no credentials; flights and eSIM return `provider_unavailable` until a provider is chosen | Requires clarification (which providers) + architecture recommended |
| 4 | **G0.7** — F-027 / F-043 / F-071 / multi-city | Defer seat selection and eSIM usage tracking; implement tour cancellation eligibility (not fee calculation); remove multi-city from UI scope | Recommended — awaiting approval (2 items need clarification) |
| 5 | **G0.8** — settlements | Add `settlement_periods` + `settlement_items` keyed to `partner_ledger_entries` so BR-114 reconciles to the cent by construction | Recommended — awaiting approval |

---

## 3. Approved decisions

### 3.1 G0-DEC-01 — Audit and activity logs permanently excluded

**Current documented situation.**
`BUSINESS-LOGIC.md` BR-107, BR-111 and BR-124, feature F-112 ("Dynamic roles, permissions **& audit**") and the "audit" columns of `ROLE-PERMISSION-SCOPE-MATRIX.md` require an audit trail with actor, timestamp and before/after values. `AUTHORIZATION-ARCHITECTURE.md` §11 states the opposite: audit logs are permanently removed and no UI shows them. The implementation has no audit table and never seeded the audit permission. Phase 0 had already deleted a previously-drafted audit architecture (migration, model, service, event, listener, controller, resource, policy, two tests) and recorded the deletion in `IMPLEMENTATION-STATUS.md` row 3. This is conflict **C-01**.

**Recommended decision (approved).**
Permanently excluded. No audit log, no general activity log, no audit table, service, event, listener or endpoint. `status_histories` remains and is permitted **only** for business-entity status transitions (booking, visa application, refund, payout request, tour review). It must never be widened into a record of user actions, logins, reads, or permission changes.

**Reasoning.**
The authorization architecture, the Phase 0 correction log and the current schema all already assume audit is absent; BR-107/111/124 are the only remaining source of the requirement, and they are the older text. Keeping exactly one append-only history table for entity lifecycle satisfies the operational need that the audit rule was reaching for (who moved this booking to `cancelled`, when) without building a second, parallel event store. It also preserves the security properties already proven: append-only `status_histories`, idempotent `partner_ledger_entries.entry_key`, and immutable `payments`/`refunds`.

**Database impact.**
- Tables affected: `status_histories` only (already exists, append-only, model throws on update/delete).
- New tables required: **none**.
- Columns required: **none**.
- Foreign keys / unique constraints / indexes: **none**.
- Data-integrity implication: `team_activity.view` must read `status_histories`, so every entity whose transitions appear in a team activity feed must write a status history row. The feed is therefore bounded by the set of entities that have status histories — this is the intended constraint, not a gap.

**API impact.**
- Affected API groups: platform administration, organization reports (`team_activity.view`), and every admin mutation response.
- Required endpoints: **no** audit endpoints. One read-only "team activity" endpoint under the organization reports group, serving `status_histories` rows scoped to the caller's organization and service/property scope.
- Authorization: `team_activity.view` (company only) and `reports.view`.
- Request/response implications: no `audit`, `activity` or `changed_by` block may appear in any resource. Existing business tables keep their `created_by` / `updated_by` / `updated_by`-style columns (e.g. `room_rates.updated_by`, `payments` attribution, `payout_requests.processed_by`) — these are attribution fields, not an activity log, and are permitted.
- Webhook/background-job implications: none.

**Security and tenant isolation impact.**
The deliberate trade-off: after-the-fact reconstruction of *who changed a permission or a role* is not possible. This is the one element of BR-107 with genuine security value, and it is being accepted as a residual risk. Compensating controls that remain: single active owner per organization enforced by a unique index; non-delegable permission list enforced centrally; `permissions_version` bumped on every role/grant/scope change (so stale access ends on the next request, PE-12); append-only status histories; immutable financial rows. Tenant isolation is unaffected — activity feeds are strictly organization-scoped and must fail closed under `G0-DEC-05`.

**Dependencies.**
- Blocks nothing.
- Resolved by it: **C-01**; the permission key `platform.audit.view` (§4.1); F-112's title and scope; gap-analysis §11 Phase K audit item.

**Risks.**
If a legal, tax or insurer requirement for an audit trail appears later, it is not a bolt-on: it needs a schema addition plus an interceptor or observer on every mutation, retrofit across all services. Mitigation available now at no cost: never repurpose `status_histories`, and keep the permission catalog free of audit keys so nobody believes an audit capability exists.

**Approval status:** **Already approved.**

---

### 3.2 G0-DEC-02 — One centralized authoritative permission catalog

**Current documented situation.**
Four conflicting counts: 83 (matrix header and totals), 84 (naive extraction, inflated by the phantom `platform.audit.view`), 78 (matrix §9 self-check), 80 (seeder). Eight documented keys are missing from the seeder and four seeded keys are undocumented. Full analysis in `PERMISSION-CATALOG-RECONCILIATION.md`. This is conflict **C-02**, with C-03, C-04 and C-17 attached.

**Recommended decision (approved in principle).**
There is exactly one permission catalog, held in one place. The **content** of that catalog is the 85-key list in `PERMISSION-CATALOG-RECONCILIATION.md` §4–5: **37 platform + 48 organization**. Every addition, removal, rename and consolidation is individually justified there; no key is invented.

**Reasoning.**
The count cannot be chosen by majority or by seniority of document — it has to be derived from capabilities. The reconciliation derives it by unioning what the documents require with what the implementation already enforces, then removing only what the approved Gate 0 decisions make impossible (`audit.view`, `update_status`) or redundant (`org.profile.publish`). That yields 85 keys with no orphans in either direction. Keeping the catalog in the `permissions` table (with `family`, `domain`, `sensitivity`, `scope_requirement`, `allowed_org_types`) and delegability in `config/authorization.php` preserves the existing single-source-of-truth split that Phase 0 deliberately established.

**Database impact.**
- Tables affected: `permissions` (data rows only), `roles`, `role_has_permissions`, `model_has_roles`, `model_has_permissions`.
- New tables required: **none**. No migration.
- Columns required: **none** — `permissions` already stores `family`, `domain`, `description`, `sensitivity`, `scope_requirement`, `allowed_org_types`.
- Foreign keys / unique constraints / indexes: unchanged. `permissions` already unique on (`name`, `guard_name`).
- Data-integrity implications: `scope_requirement` is currently computed by string prefix in the seeder (`tours.`, `visa_requests.`, `visa_offerings.`, `rooms.`, `rates.`). `properties.` must be added to that prefix list as a `property` scope, and the `access_control` sensitivity value normalised to `access-control` (P-14). Removing a key that a custom role references must be handled by the authorization service, not by a silent cascade.

**API impact.**
- Affected API groups: platform roles/permissions (F-112), organization roles (F-097/F-098), and every endpoint that asserts a permission.
- Required endpoints: none new; the role editor's available-permission list and the grant validation must read the frozen catalog.
- Authorization: the catalog defines authorization; it does not consume it. `platform.roles.*` and `roles.*` gate the editors.
- Request/response implications: permission keys appear in role resources as strings; removing a key changes those payloads. No versioning needed while nothing is released.
- Webhook/background-job implications: none.

**Security and tenant isolation impact.**
This is the highest-leverage decision in Gate 0: every owner template, every custom role and every policy check derives from these keys. The catalog closes the visa-decision escalation (`update_status` could record a final decision without holding `decide`), makes property publishing a per-property right, and introduces `settlements.accounts.manage` as a non-delegable financial control. The family rule (no `platform.*` key on an organization role) is unchanged and must be asserted on every grant path.

**Dependencies.**
- Depends on `G0-DEC-01` (removes the audit key), `G0-DEC-03` (defines `properties.*`), `G0-DEC-04` (defines review/decide/assign).
- Blocks Phase A entirely, and Phases C–J.

**Risks.**
A wrong catalog mis-seeds every role in every tenant at once, and the damage is silent. Two specific hazards: (a) removing a key later breaks custom roles that reference it — deletion must be a documented migration, not a seed refresh; (b) widening `bookings.*`/`customers.view` to service providers before their service catalog exists grants access to a business they cannot operate (P-09).

**Approval status:** **Already approved in principle; catalog content recommended — awaiting user approval** (§4.1).

---

### 3.3 G0-DEC-03 — A hotel organization may own multiple hotel properties

**Current documented situation.**
The matrix defines `properties.view/create/update/publish` for hotel organizations (F-101, F-106), while the implementation models hotels directly under a hotel organization and instead has the organization-wide `org.profile.publish`. `OPEN-QUESTIONS.md` Q-49 assumes one property per hotel organization at launch, while `DB-DECISION-BRIEF.md` DEC-14 supports multiple hotels. Conflict **C-03** and **C-11**.

**Recommended decision (approved).**
- `hotels` **is** the hotel-property entity; it is not renamed and not duplicated.
- A hotel organization **may own many** hotels.
- `membership_hotel_scopes` carries property-level membership access.
- Bookings reference the relevant `hotel_id`.
- Every property access validates the organization ownership chain (`hotel.organization_id` = the acting membership's organization).
- Hotel owners manage every property of their organization; hotel staff reach only properties in their assigned scope.
- An empty property scope means **no** property access (`G0-DEC-05`).

**Reasoning.**
The schema already implements this model — `hotels.organization_id` (FK, restrict), `membership_hotel_scopes` (`unique(membership_id, hotel_id)`), `bookings.hotel_id`, `hotel_bookings.hotel_id` — so the approved decision aligns the documents with the stronger model already in place, rather than forcing a one-property constraint that the tables would have to be weakened to express. It also makes the documented `properties.*` keys meaningful and lets `BR-110` (publish requires verified legal documents) attach to the correct unit.

**Database impact.**
- Tables affected: `hotels`, `membership_hotel_scopes`, `bookings`, `hotel_bookings`.
- New tables required: **none**.
- Columns required: **none** — `hotels.organization_id`, `membership_hotel_scopes.membership_id`/`hotel_id`, `bookings.hotel_id`, `hotel_bookings.hotel_id` already exist.
- Foreign keys: present and correct (`hotels.organization_id` restrict; `membership_hotel_scopes.*` cascade; `bookings.hotel_id` restrict-nullable; `hotel_bookings.hotel_id` restrict).
- Unique constraints: `membership_hotel_scopes` unique (`membership_id`, `hotel_id`) — present. `hotels` must **not** receive a unique constraint on `organization_id`.
- Indexes: `hotels` has (`city`,`status`) and (`latitude`,`longitude`); the `organization_id` FK is indexed implicitly by InnoDB, which serves the ownership-chain lookup. `hotel_bookings` has (`hotel_id`,`check_in`). No new index is required for correctness.
- Data-integrity implications: nothing at the database level prevents a `membership_hotel_scopes` row pointing at a hotel owned by a *different* organization. That invariant must be enforced in the service layer (and by a policy check on every property-scoped query) until a composite FK on (`hotel_id`, `organization_id`) is considered. Recorded as an implementation requirement, not a migration.
- **Comment defect to fix when code is next touched (not now):** the migration comment on `membership_hotel_scopes` currently reads "No rows = every property the organization owns", which contradicts `G0-DEC-05`. The comment must be corrected to "No rows = no property access".

**API impact.**
- Affected API groups: hotel portal (F-101, F-103, F-106), hotel bookings (F-104), platform partner/hotel oversight.
- Required endpoints: property list/create/update/publish within the hotel portal; room-type and rate endpoints nested under a property; booking list/detail filtered by `hotel_id`.
- Authorization: `properties.view/create/update/publish`, `rooms.*`, `rates.*`, `bookings.*`, plus the property-scope and ownership-chain checks. Publishing additionally requires verified legal documents (`BR-110`).
- Request/response implications: every property-scoped request must carry or derive the property id explicitly; responses must not leak other properties of the same organization to scoped staff. Reservation/booking responses must include the property reference.
- Webhook/background-job implications: none. (Rate/availability jobs iterate properties, a later Phase K concern.)

**Security and tenant isolation impact.**
Two boundaries now exist inside one tenant — organization and property — and both must fail closed. The organization boundary alone is insufficient for staff: a receptionist restricted to one property must not enumerate a sibling property's rooms, rates, arrivals or bookings. Property scope narrows; it never widens. The ownership-chain check must run before the scope check, so a forged `hotel_id` belonging to another tenant is a 404/403, never a cross-tenant read.

**Dependencies.**
- Depends on `G0-DEC-05` (empty scope meaning) and `G0-DEC-02` (`properties.*` keys).
- Blocks Phase E (hotel catalogue/search), Phase G.4 (hotel booking) and Phase H.5 (hotel portal).

**Risks.**
If property scope is implemented only in policies and not in the query layer, a scoped employee can still page through siblings by pagination or by id enumeration. If `hotels` were later given a `unique(organization_id)`, the approved multi-property model would be silently broken. If publishing stays organization-wide (`org.profile.publish`), one unverified document set could block or release every property at once.

**Approval status:** **Already approved.**

---

### 3.4 G0-DEC-04 — Only platform staff may make the final visa decision

**Current documented situation.**
`ROLE-PERMISSION-SCOPE-MATRIX.md` defines `platform.visa_requests.update_status` for platform staff and a "final decision" description on `platform.visa_requests.review`; `AUTHORIZATION-ARCHITECTURE.md`/DEC-16 removed the company-side status key; `OPEN-QUESTIONS.md` Q-46 still says "company employees with `visa_requests.update_status`"; DEC-07 says "employees record decisions". The implementation seeds `platform.visa_requests.review`/`decide`/`assign` and organization `visa_requests.review`, with no `update_status` anywhere. Conflict **C-04**.

**Recommended decision (approved).**
- Only Traway platform staff holding `platform.visa_requests.decide` may record an approval or rejection.
- Company users may view visa requests within their authorized scope (`visa_requests.view`) and validate documents / flag missing items (`visa_requests.review`). They cannot decide.
- Customer-visible statuses stay exactly `Submitted → Under Review → Documents Required → Processing → Approved / Rejected`. No new customer-facing status is introduced.
- Internal operational transitions (assignment, document requests, notes) are separate from customer-visible statuses.

**Reasoning.**
A visa decision is a legal/consular act with financial and regulatory exposure, and the traveler has no contractual relationship with the company beyond the offering. Centralising the decision in platform staff makes the outcome attributable to one accountable actor, keeps a single quality gate across all publishing companies, and matches the schema: `visa_applications.decided_by` / `decided_at` / `rejection_reason` exist for exactly one decider. It also removes the last path by which a partner could approve its own sale.

**Database impact.**
- Tables affected: `visa_applications`, `visa_application_documents`, `status_histories`.
- New tables required: **none**.
- Columns required: **none** — `visa_applications` already has `status`, `assigned_membership_id`, `rejection_reason`, `decided_by`, `decided_at`, `submitted_at`; documents have per-document `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`.
- Foreign keys / unique constraints / indexes: unchanged (`visa_applications` already indexes (`organization_id`,`status`) and (`user_id`,`status`); `booking_id` is unique).
- Data-integrity implications: the decision columns must only be written when the actor holds `decide` **and** the transition is legal; the customer-visible status must never be set by a company-side review action. If internal states later need to be distinguishable from the six customer statuses, that is an added internal column — deliberately **not** proposed here, because DEC-04 forbids new customer-facing statuses and the current `status` column plus document states is sufficient.

**API impact.**
- Affected API groups: customer visa tracker, company visa workspace (D-04.6), restricted employee queue (D-06.1), platform visa queue (D-03.3).
- Required endpoints: company — list/detail visa requests within scope, per-document accept/reject, request additional documents. Platform — list/detail all, assign, review, decide (approve/reject with mandatory reason on rejection).
- Authorization: `visa_requests.view` + `visa_requests.review` (company, service-scoped to `visas`); `platform.visa_requests.view`/`review`/`assign`/`decide` (platform). `update_status` does not exist.
- Request/response implications: the decision endpoint is platform-only and requires `rejection_reason` when rejecting. Company responses expose documents and review status but not decision controls. Customer responses expose only the six statuses plus reason category/note.
- Webhook/background-job implications: decision and status change emit notification events (BR-104, G0.5); no external authority submission exists inside Traway (DEC-07).

**Security and tenant isolation impact.**
Removes the partner's ability to approve its own application — the main conflict-of-interest path in the visa module. Company scope remains service- (`visas`) and organization-bound: a company sees only applications attached to its own offerings (`visa_applications.organization_id`), and a restricted employee sees only assigned requests (`assigned_membership_id`, `BR-115`). Platform access is cross-tenant by design and must be the only path that ignores the tenant filter.

**Dependencies.**
- Depends on `G0-DEC-02` (`review`/`decide`/`assign` are the canonical keys; `update_status` removed) and `G0-DEC-05`.
- Supersedes the "employees record decisions" phrasing in DEC-07 and resolves Q-46.
- Blocks Phase H.2 (visa review workflow) and Phase C (customer tracker copy).

**Risks.**
Platform staff become a throughput bottleneck for every visa decision, and there is no fallback if the visa-officer role is unstaffed. If `visa_requests.review` is loosely interpreted, company staff could still request documents in a way that reads as a decision to the customer — the customer-facing status vocabulary must be driven only by platform-confirmed transitions.

**Approval status:** **Already approved.**

---

### 3.5 G0-DEC-05 — An empty scope means access to nothing

**Current documented situation.**
BR-121 and MT-11 state that a non-owner with no service scope has no service access. `AuthorizationService` treats an **empty** service or hotel scope as unrestricted, and the `membership_hotel_scopes` migration comment says "No rows = every property". Conflict **C-08**, and Phase A item A1. This decision is not one of the eight Gate 0 items, but it is approved and blocks the same work.

**Recommended decision (approved).**
An empty property, organization or resource scope grants access to **nothing**. No empty array and no null scope value may be read as implicit global access. Global access is explicit and independently authorized. Every scoped query fails closed. Authorization validates, in order: permission → active membership → organization ownership → applicable resource scope.

**Reasoning.**
Fail-open defaults are the classic source of cross-tenant leaks, and this codebase's default is exactly backwards from its documented rule. An explicit "all services"/"all properties" representation is required so that legitimate full access is still expressible — a hotel owner must see all properties **because they are the owner**, not because their scope list happens to be empty. Making ownership the explicit grant, and empty the denial, means the absence of a row can never be a privilege.

**Database impact.**
- Tables affected: `membership_service_scopes`, `membership_hotel_scopes`.
- New tables required: **none**.
- Columns required: **none**, provided "all" is expressed by ownership (`memberships.is_owner`) rather than by an empty scope set. If a non-owner must be granted all properties/services, that is an explicit row per value, which the existing unique constraints support.
- Foreign keys / unique constraints / indexes: unchanged.
- Data-integrity implications: existing rows with zero scope rows now become "no access" rather than "all access". Any seeded or manually created membership relying on the old meaning will lose access — this is the intended correction and must be covered by the Phase A migration/verification plan for existing data (development data only at present).

**API impact.**
- Affected API groups: every organization-scoped endpoint, plus restricted-employee queues (F-108, F-109, F-110).
- Required endpoints: none new; the behavior of scoped list/detail endpoints changes to empty (not forbidden) result sets for members with no applicable scope.
- Authorization: policies and queries must both consult scope; a permission alone must not imply record access.
- Request/response implications: an empty list is a legitimate response for a scoped member; a 403 is reserved for a missing permission or a foreign record.
- Webhook/background-job implications: none.

**Security and tenant isolation impact.**
Directly closes the highest-severity class of defect found in the audit. It also fixes the restricted-employee tier (BR-087/115), which today could see the whole organization because its scope list is empty.

**Dependencies.**
- Blocks Phase A (A1/A2) and every later phase's query layer.
- Required by `G0-DEC-03` (empty property scope = none), `G0-DEC-04` (assigned-only queue).

**Risks.**
Failing closed can lock out legitimate members whose access was implicitly granted by the old semantics — notably hotel staff created without property rows, and company staff created without service rows. The correction must be accompanied by a data check that every non-owner member who needs access has explicit scope rows; otherwise the first deploy looks like an outage.

**Approval status:** **Already approved.**

---

## 4. Remaining decisions requiring approval

### 4.1 G0.2 (content) — Freeze the canonical permission catalog

**Current documented situation.**
The principle is approved (`G0-DEC-02`), but the catalog itself is not: 83 documented, 84 by naive extraction, 78 in a stale self-check, 80 implemented, 88 in the union. Eight documented keys missing from the seeder; four seeded keys undocumented; `platform.audit.view` exists only as a mention in a validation note; the non-delegable table in `AUTHORIZATION-ARCHITECTURE.md` §7.2 cites a key (`org.delete`) that exists nowhere. Full evidence in `PERMISSION-CATALOG-RECONCILIATION.md`.

**Recommended decision.**
Adopt the **85-key** catalog: **37 platform + 48 organization**.
- **Add (9):** `platform.settings.manage`; `platform.visa_requests.assign`; `platform.visa_requests.decide`; `visa_requests.review`; `properties.view`; `properties.create`; `properties.update`; `properties.publish`; `settlements.accounts.manage`.
- **Remove (3):** `platform.audit.view` (G0-DEC-01); `platform.visa_requests.update_status` (G0-DEC-04); `org.profile.publish` (superseded by `properties.publish`).
- **Rename (0).** The `org.` prefix inconsistency is recorded, not fixed — renaming 44 keys would invalidate every role for no functional gain.
- **Description change (1):** `platform.visa_requests.review` loses "and record the final decision".
- **Non-delegable list:** add `settlements.accounts.manage`.
- **Open sub-items:** P-09 (`bookings.*`/`customers.view` and service providers — recommend keeping `['company','hotel']` until the provider catalog exists) and P-14 (sensitivity spelling).

**Reasoning.**
Derived, not chosen: union of documented and implemented keys, minus what the approved decisions make impossible or redundant. That is the only method that leaves no orphan keys in either direction and needs no invention. Keeping delegability in config and metadata in the `permissions` table preserves the Phase 0 split.

**Database impact.**
No migration. Data-only changes to `permissions` + role assignments; `scope_requirement` prefix list gains `properties.`; `config/authorization.php` non-delegable list gains one key; `access_control` spelling normalised.

**API impact.**
No new endpoints. Role editor payloads and grant validation read the frozen list; removed keys must be rejected with a clear error, not silently ignored.

**Security and tenant isolation impact.**
Closes the visa decision escalation path, makes publishing per-property, adds a non-delegable payout-account control, and eliminates a phantom capability from the docs. Unchanged: the family rule and owner-template immutability.

**Dependencies.** `G0-DEC-01`, `G0-DEC-03`, `G0-DEC-04`. Blocks all of Phase A.

**Risks.** A wrong key set mis-seeds every tenant at once; later removals break custom roles. The exact 85 must be approved before the seeder is touched.

**Approval status:** **Recommended — awaiting user approval.**

---

### 4.2 G0.5 — Notification channels and initial architecture

**Current documented situation.**
BR-104 requires notifications generated by domain events; BR-105 requires per-user read state; F-021 defines the mobile inbox with filters, grouping, unread dots and mark-all-read. The `notifications` table exists (Laravel standard: uuid id, type, morphs notifiable, data, read_at). There are **no** Events, Listeners, Jobs, Mailables or Notifications, no queue worker configuration beyond the default `jobs` table, no push credentials and no SMS/WhatsApp provider. Q-07 (deep links, push in scope) is Blocking-4 and open.

**Recommended decision.**
Launch on **database notifications + e-mail**, processed by the **database queue**:
- One domain event per business fact (booking confirmed/cancelled, visa submitted/documents required/approved/rejected, eSIM issued, join request, deletion request, refund requested/settled, settlement transferred, employee deactivated) — BR-104's list, no polling.
- Delivery through Laravel's notification system with two channels at launch: `database` (the inbox, read state via `read_at`) and `mail`.
- **Dedupe:** set the notification UUID to a deterministic value derived from (event class, entity id, recipient id, milestone). The primary key then makes duplicate delivery a no-op — no schema change, no dedupe table.
- **Queue/retry:** dispatch **after** the domain transaction commits; 3 attempts with exponential backoff, then the failed-jobs path. A notification failure never rolls back a booking or payment.
- **Preferences:** transactional categories (bookings, payments, visas, eSIM, security) are always on; only marketing/offer categories are opt-out. Store preferences in a small per-user table added in Phase D, not now.
- **Push, SMS, WhatsApp:** deferred. No credentials, no selected provider, and no mobile app in this repository. Define channel adapters so they can be added without touching domain code; do not implement providers.
- Deep links (Q-07) stay open; the payload carries a typed `{entity, id}` pair rather than a URL, so the surface can be decided later.

**Reasoning.**
Database + e-mail is the only combination that can be built and verified today with zero external dependencies, it satisfies BR-105 natively, and it keeps the inbox as the single source of truth that push would later mirror. Deterministic IDs solve duplicate prevention without adding a table or a cleanup job — the failure mode of the obvious alternative (a `dedupe_key` column plus a unique index) is that it needs a migration for a problem the primary key already solves.

**Database impact.**
- Tables affected: `notifications` (existing), `jobs`/`failed_jobs` (existing).
- New tables required: **none at launch**. Phase D adds `notification_preferences` (user, category, channel, enabled) when opt-outs ship.
- Columns required: **none** — no `dedupe_key` (deterministic `id` used instead).
- Foreign keys / unique constraints / indexes: unchanged; `notifications` primary key on `uuid id` provides the dedupe guarantee. `morphs(notifiable)` is already indexed.
- Data-integrity implications: the notification `data` payload must be self-contained enough to render the inbox without joining business tables (so a deleted booking still renders); it must not contain secrets or full passport/IBAN values.

**API impact.**
- Affected API groups: customer inbox (F-021), platform/company/hotel/employee inboxes (F-079, F-092, F-102, F-110).
- Required endpoints: list (filter by category, paginate), unread count, mark one read, mark-all-read (BR-105: only the caller's visible set).
- Authorization: ownership-based for customers (`notifiable` = the user); organization/role-scoped for staff inboxes.
- Request/response implications: category enum matches the filter chips (all, bookings, offers, payments) plus visa/eSIM/account; response carries `read_at`, `created_at`, type, and the typed deep-link pair.
- Webhook/background-job implications: notifications are queued jobs; e-mail is the only outbound channel at launch.

**Security and tenant isolation impact.**
Notifications are the most common source of cross-tenant leakage when a queue job carries the wrong recipient; the recipient must be resolved server-side from the event's owning entity, never from a request parameter. Staff inboxes must respect organization and (for hotels) property scope under `G0-DEC-05`. E-mail must never include another party's personal data (e.g. a hotel must not receive the customer's passport number).

**Dependencies.** `G0-DEC-05` (inbox scoping), Phase C (identity) for recipient resolution. Blocks Phase D and every later phase that promises "notify the customer".

**Risks.** Deferring push means BR-104's "prompt" expectation is only met in-app until a push provider and the mobile app exist. A shared queue with no separate worker would let a slow mail provider stall bookings — the queue must be separated by queue name when workers are configured.

**Approval status:** **Recommended — awaiting user approval.**

---

### 4.3 G0.6 — Payment, flight, hotel and eSIM provider strategy

**Current documented situation.**
`payments`, `refunds`, `checkout_sessions`, `esim_packages`, `esim_orders`, `hotels`/`room_types`/`room_rates` and the booking tables exist; `bookings.provider_reference` holds the upstream reference. No gateway, no flight/GDS provider and no eSIM provider is approved or contracted, and no SDK is installed. DEC-10 says partner payouts go through the payment gateway "subject to its capabilities" — which is unverified. Q-31 (baggage payment), Q-40 (gateway fees shown as cancellation fees), Q-44 (visa withdrawal refund) are Blocking-4.

**Recommended decision.**
Build **adapter contracts plus fake providers** now; keep provider choice as a configuration decision:
- `PaymentGateway` contract: initialise/authorise, capture, refund, fetch transaction, verify + parse webhook. The `payments.provider` column already discriminates implementations.
- `FlightProvider` contract: search, revalidate, book, cancel, refund status.
- `HotelProvider`: **not required at launch** — hotels, room types and daily rates are first-party Traway inventory, so hotel search/availability/booking is fully implementable without any external provider.
- `EsimProvider` contract: sync catalogue, availability/pricing, purchase, provisioning, activation, status/usage (usage optional).
- Fake implementations for every contract, enabled only in non-production through a config guard; production refuses to boot with a fake gateway.
- Flights and eSIM **without** a provider return an explicit `provider_unavailable` error — never simulated availability or prices.

**Reasoning.**
The audit's core warning is that inventing availability or prices produces a product that lies to customers and corrupts the ledger. Separating "domain logic exists" from "provider is connected" lets Phases F–H proceed on real business rules (quote snapshots, idempotency, state machines, ledger posting) while making the missing integration a visible, handled state rather than fake data. It also keeps booking logic provider-neutral, which is what makes a later switch of gateway or GDS survivable.

**Database impact.**
- Tables affected: `payments`, `refunds`, `checkout_sessions`, `bookings`, `flight_bookings`, `flight_segments`, `flight_passengers`, `esim_packages`, `esim_orders`.
- New tables required: **one, recommended** — `payment_webhooks` (id, provider, provider_event_id unique per provider, type, payload json, received_at, processed_at, error). Rationale: webhook replay and at-least-once delivery are guaranteed behaviours of every gateway; without a raw-event record, a duplicate capture cannot be diagnosed and `unique(provider, provider_payment_id)` alone cannot tell a replay from a genuine second event.
- Columns required: none mandatory. `bookings.provider_reference`, `payments.provider`/`provider_payment_id`/`idempotency_key`/`failure_code`, `refunds.provider_refund_id`/`gateway_fee_cents`, `esim_orders.provider_order_id`/`activation_payload`, `esim_packages.provider`/`provider_package_id`/`is_stale`/`synced_at` already cover the adapter needs. **Gap to confirm later:** BR-058 "single-use activation" may require an `activation_payload_viewed_at` column; not proposed now.
- Foreign keys / unique constraints / indexes: `payments` unique (`idempotency_key`) and unique (`provider`,`provider_payment_id`) exist; `refunds.provider_refund_id` indexed; new `payment_webhooks` needs unique (`provider`,`provider_event_id`).
- Data-integrity implications: money movements must be insert-only; a webhook may only advance a payment's status forward; refunds reference a payment; every ledger entry carries an idempotency `entry_key`.

**API impact.**
- Affected API groups: checkout/payment, flight search+booking, hotel search+booking, eSIM catalogue+purchase, refunds, and the provider webhook endpoint.
- Required endpoints: checkout initialise, payment intent/confirm, refund request; flight search, revalidate-and-book; hotel availability and book; eSIM packages and purchase/QR; one unauthenticated webhook receiver per provider prefix with mandatory signature verification.
- Authorization: customer-owned checkout/booking; platform for refund approval and settlement execution; webhook endpoints authenticated by signature, never by session.
- Request/response implications: idempotency key required on initialise and on every money-writing call; price-change responses require explicit customer consent (checkout `price_changed` + `price_change_accepted_at`); provider errors mapped to safe codes, never raw provider text.
- Webhook/background-job implications: signature verification, replay protection, `payment_webhooks` persistence, post-commit jobs for confirmation notifications, and a reconciliation job comparing provider transactions with `payments`.

**Security and tenant isolation impact.**
Provider credentials stay server-side and out of the browser; `payments.provider` must never echo secrets. Webhooks are unauthenticated by nature, so signature verification is the only boundary — a forged webhook that marks a booking paid is a direct financial loss. Idempotency keys prevent double charging on retries. Fake providers must be impossible to enable in production.

**Dependencies.** Blocks Phase G (payments/bookings) and Phase J (settlements/payouts). Requires owner clarification on: gateway choice, flight provider, eSIM provider, and whether the gateway supports partner payouts (DEC-10's assumption).

**Risks.** Choosing a gateway that cannot do partner payouts invalidates the DEC-10 payout design; choosing a flight provider with no cancellation/refund API makes F-049 unbuildable; a GDS with no seat data makes F-027 impossible. Building against fakes risks discovering late that a real provider cannot express something the fake could (price validity windows, per-passenger tickets).

**Approval status:** **Requires clarification** (provider selection). The adapter architecture is **recommended — awaiting user approval**.

---

### 4.4 G0.7 — Unresolved features F-027, F-043, F-071 and multi-city

**Current documented situation.**
The catalogue marks three features `U` (unresolved) and offers multi-city flights while a business rule forbids partial implementation. Details and recommendations are in Topic F (§5.6). Conflicts **C-13**, **C-14**.

**Recommended decision.**
- **F-027 Seat selection — defer.** No seat map, no seat inventory, no seat fee in v1. Seats are free and optional (Q-09's working assumption).
- **F-043 eSIM usage & balance — defer live usage; display package allowance only.** No usage screen, no polling, no usage table. Show the purchased data/validity from the package snapshot; hide the progress bar if no source exists.
- **F-071 Tour cancellation — implement the eligibility rule, not a fee engine.** Full refund when cancelling ≥24h before slot start; no self-service cancellation inside 24h; gateway applies any fee (DEC-01). Add a per-tour policy flag only when a second policy exists.
- **Multi-city — remove from scope.** `flight_bookings.trip_type` already constrains `one_way|round_trip`; the option is hidden in the UI.

**Reasoning.**
All four are places where the design shows a concept without the rule needed to build it. Deferring keeps the launch surface honest and avoids inventing inventory or pricing that no provider has confirmed. The tour rule is the one case where a real customer promise ("free cancellation within 24 hours") exists, and it can be honoured by an eligibility check plus a refund, without Traway computing fees — consistent with DEC-01.

**Database impact.**
No schema change for any of the four. F-027 would later need seat inventory per flight segment; F-043 would need usage snapshots only if the provider exposes an API; F-071 uses existing `bookings`/`refunds`/`status_histories`. Multi-city would require multiple `flight_bookings` per order and a fare-combination model — explicitly out of scope.

**API impact.** No endpoints for F-027/F-043. Tour cancellation adds a cancel-eligibility check to the existing booking-cancel path. Multi-city: the trip-type enum rejects `multi_city`.

**Security and tenant isolation impact.** None directly; the tour cancellation path inherits booking ownership and refund authorization (`bookings.cancel`, `bookings.refund`).

**Dependencies.** F-071 depends on the refund path (G0.6). F-043 depends on eSIM provider selection or its absence.

**Risks.** Shipping a UI that promises seats or live data usage without the backend is a support and chargeback risk; conversely, deferring tour cancellation while the UI says "free cancellation within 24 hours" is a consumer-protection risk — the rule must ship with the promise.

**Approval status:** **Recommended — awaiting user approval.** User clarification is **essential** for F-043 (is a live data source expected?) and **advisable** for F-071's exact refund basis; **not essential** for F-027 or multi-city, where the documents already defer.

---

### 4.5 G0.8 — Settlement and payout schema

**Current documented situation.**
BR-098–BR-100, BR-114 and DEC-10 require settlement periods with states `pending → processing → transferred` (with `failed` returning to pending), partner dues reconciling to the cent with the admin view, refunds reducing the next settlement, and on-demand payouts. The schema has `payout_accounts`, `payout_requests` (`requested|processing|transferred|failed|rejected`, `method` manual|gateway) and an append-only `partner_ledger_entries` with a signed amount and unique `entry_key` — but **no** settlement table, so a settlement period cannot be represented, locked or reconciled. Conflict **C-16**.

**Recommended decision.**
Add two tables, both derived from the ledger:
- `settlement_periods`: `id`, `organization_id` (FK restrict), `period_start`, `period_end`, `status` (`pending|processing|transferred|failed`), `sales_cents`, `commission_cents`, `refunds_cents`, `adjustments_cents`, `due_cents`, `payout_request_id` (nullable FK), `computed_at`, `transferred_at`, timestamps; **unique (`organization_id`,`period_start`,`period_end`)**.
- `settlement_items`: `id`, `settlement_period_id` (FK cascade), `partner_ledger_entry_id` (FK restrict, **unique**), `amount_cents`, timestamps.
- Partner balance remains `SUM(partner_ledger_entries.amount_cents)` — a derived value, never stored as a mutable column.
- BR-114 is satisfied structurally: the partner's settlement total and the admin's partner-due figure are both `SUM(settlement_items.amount_cents)` for the same period, so they cannot diverge.

**Reasoning.**
The ledger is already the append-only truth and the codebase already derives balance from it. A settlement is therefore a *claim over a closed set of ledger rows*, not a second money store — which is why `settlement_items` keys individual ledger entries rather than storing a total. That design makes double-counting and "deducted twice" (BR-100) impossible: an entry belongs to at most one settlement, enforced by a unique constraint, instead of by careful arithmetic.

**Database impact.**
- New tables: `settlement_periods`, `settlement_items` (as above).
- Tables affected: `payout_requests` (add nullable `settlement_period_id` FK, or keep the link on the period — one direction is enough; the recommendation above puts it on `settlement_periods`).
- Columns required: the two new tables only.
- Foreign keys: `settlement_periods.organization_id` → organizations (restrict); `settlement_periods.payout_request_id` → payout_requests (restrict, nullable); `settlement_items.settlement_period_id` → settlement_periods (cascade); `settlement_items.partner_ledger_entry_id` → partner_ledger_entries (restrict).
- Unique constraints: (`organization_id`,`period_start`,`period_end`); `settlement_items.partner_ledger_entry_id`.
- Indexes: (`organization_id`,`status`), (`status`,`period_end`).
- Data-integrity implications: only `transferred` closes a period (BR-099); a `failed` period returns to pending; an entry may not be reassigned to another period once transferred; refund carry-forward is expressible as a negative `settlement_items` row or a `refunds_cents` total plus the entry links.

**API impact.**
- Affected API groups: platform finance (F-089), company/hotel settlements (F-099), payout requests.
- Required endpoints: list periods and their items; compute/close a period (platform); request an on-demand payout; mark transferred/failed; export.
- Authorization: `platform.finance.view`, `platform.finance.settlements.execute`; partner side `settlements.view`/`settlements.manage`; `settlements.accounts.manage` for destination changes (non-delegable).
- Request/response implications: the partner view and the admin partner-dues row must be produced by the same aggregation code path; amounts in cents, USD only.
- Webhook/background-job implications: payout execution via gateway where supported (`method=gateway`), otherwise manual with recorded `transferred_at`; gateway payout callbacks reuse the `payment_webhooks` pattern.

**Security and tenant isolation impact.**
Settlements are the highest-value cross-tenant risk: a partner must see only its own periods and items, and an organization must never be able to trigger a payout to an account it does not own. Payout-account changes are owner-only and non-delegable; execution is platform-only. `payout_accounts` currently has no approved design — it is an unapproved implementation artefact and its KYC/re-verification change control is still open (Q-54).

**Dependencies.** Depends on Phase G (payments/refunds produce the ledger entries a settlement closes) and G0.6 (gateway payout capability). Blocks Phase J.

**Risks.** Building settlements before payments/refunds are stable produces unverifiable numbers. Adding a mutable "balance" column would create a second source of truth that will drift from the ledger. Assuming the gateway supports payouts (DEC-10's caveat) could leave `method=gateway` unimplementable, forcing a manual-only launch — acceptable, but it must be stated to partners.

**Approval status:** **Recommended — awaiting user approval.**

---

## 5. Topic deep-dives

These sections answer the specific topics required in addition to the eight decision records. Where a topic duplicates a decision record, the record states the decision and this section states the architecture.

### 5.1 A — Notification strategy

**Channels**

| Channel | Launch | Rationale |
|---|---|---|
| Database (in-app inbox) | **Yes** | Satisfies BR-105 natively (`read_at`), needs no external service, and is the source of truth any future push mirrors |
| E-mail | **Yes** | Transactional receipts and visa/settlement notices; Laravel mail with a configured driver |
| Push (APNs/FCM) | **No** | No credentials, no provider chosen, and no mobile app exists in this repository (Q-07 open) |
| SMS | **No** | No provider selected |
| WhatsApp | **No** | No provider selected, and business-initiated templates must be approved per provider — cannot be assumed |

**Queue processing.** Every notification is a queued job on the `database` queue driver (`jobs` and `failed_jobs` tables already exist). Dispatch happens **after** the domain transaction commits (`DB::afterCommit` / `ShouldDispatchAfterCommit`), so a mail provider slowdown or failure can never abort a booking, payment or visa transition. Notification and outbound-delivery queues should be separate queue names so a stuck mail worker does not starve booking work.

**Retry and failure behavior.** Three attempts with exponential backoff, then the job lands in `failed_jobs` and is surfaced by the admin failure view (Phase K observability). A permanently failed *delivery* never fails the *business fact*: the inbox row is the durable record and the customer can still see the notification in-app.

**Duplicate prevention.** The notification's UUID is **deterministic**: a UUIDv5 over `(event class, entity id, recipient user id, milestone)`. Delivering the same milestone twice writes the same primary key, so the second insert is a no-op. This needs no `dedupe_key` column, no unique index and no cleanup job — the ledger of record is the primary key. This also makes queue retries safe by construction.

**Notification preferences.** Split by category, not by channel:
- **Always on** (no opt-out): bookings, payments and refunds, visa status, eSIM issuance, security/account actions.
- **Opt-out**: offers, marketing, recommendations.
A `notification_preferences` table (user, category, channel, enabled) is added in Phase D when opt-outs ship. Until then the split is enforced by category, so no preference storage is required to launch.

**Future SMS/WhatsApp.** Define one `SmsChannel`/messaging contract with a no-op implementation and register it only when a provider is selected. Domain code emits events and never names a channel, so adding WhatsApp later is a listener plus credentials, not a rewrite. **Do not install an SMS/WhatsApp SDK until a provider is chosen.**

**What can be built now vs later.** Buildable now with no credentials: events, listeners, inbox API, e-mail channel, deterministic IDs, queue wiring, read state. Requires a provider: push, SMS, WhatsApp.

---

### 5.2 B — Payment gateway abstraction

**Contract (`PaymentGateway`)**

| Operation | Input | Output | Notes |
|---|---|---|---|
| `initialize` | amount, currency, idempotency key, customer ref, return URL | provider intent id, client secret/redirect | Never called twice for the same idempotency key |
| `confirm`/`capture` | provider intent id | status, captured amount, provider payment id | May be driven by webhook instead of a poll |
| `refund` | provider payment id, amount, reason | provider refund id, status | Partial refunds must be supported by the adapter contract even if unsupported by a given provider |
| `fetchTransaction` | provider reference | status, amount, fees | Used by reconciliation |
| `verifyWebhook` | raw body, signature headers | verified event DTO | Signature check is mandatory and adapter-specific |
| `parseWebhook` | verified payload | event type, provider payment/refund id, amount, failure code | Pure mapping, no side effects |

**Payment initialisation.** Create `payments` row first (`status=pending`, `idempotency_key` from the client request, `amount_cents` from the checkout session's revalidated total), then call `initialize`. The row exists before the provider is called so a crash between the two is reconcilable.

**Confirmation.** Only a verified webhook or an authenticated `fetchTransaction` may move a payment to `captured`. A browser redirect is *not* proof of payment. This is the single most important rule in the payment layer.

**Webhook signature verification.** Per-adapter `verifyWebhook` using the raw request body (not the parsed array — re-serialising changes the bytes and breaks HMACs) and a shared secret from configuration. Unverified requests are rejected with 400 and never touch the database.

**Idempotency.** Three layers, all already supported by the schema: `payments.idempotency_key` unique (client retries), `unique(provider, provider_payment_id)` (duplicate provider events), `partner_ledger_entries.entry_key` unique (duplicate ledger postings).

**Refunds.** `refunds` already models `type` (`policy_automatic|manual_exception`), the full status chain, `gateway_fee_cents`, approver fields and `provider_refund_id`. The gateway decides any cancellation fee (DEC-01) — Traway stores what the gateway reports and never computes a fee.

**Reconciliation.** A scheduled job compares provider transactions for a window against `payments`/`refunds`: missing locally, missing at provider, amount mismatch, status mismatch. Requires provider API access; the comparison logic itself is buildable against a fake.

**Gateway errors.** Map to a small stable vocabulary (`card_declined`, `insufficient_funds`, `expired_card`, `3ds_required`, `provider_unavailable`, `duplicate_request`) stored in `payments.failure_code`; never surface raw provider messages or codes to the client, and never log full PANs (only `card_brand`/`card_last4`, which already exist).

**Provider transaction references.** `payments.provider` + `provider_payment_id` + `refunds.provider_refund_id` + `payout_requests.provider_payout_id` are sufficient. Traway's own references (`PAY-…`) remain separate from provider ids (DEC-04).

**What can be built now vs later.** Buildable now: the contract, the fake gateway, checkout→payment state machine, idempotency, refund workflow, ledger posting, reconciliation logic. Requires a gateway: real tokenisation, capture, webhooks, payouts.

**Do not assume** that the selected gateway supports partner payouts, partial refunds, or the same webhook shape as any documented example — each is a clarification item under G0.6.

---

### 5.3 C — Flight and hotel provider integrations

**Shared adapter shape**

| Stage | Flight | Hotel |
|---|---|---|
| Search | `search(criteria)` → offers with opaque `offer_token`, price, expiry | Not required — first-party inventory |
| Availability | included in search results; re-checked on revalidate | `room_rates` per room type per date (already exists) |
| Price validation | `revalidate(offer_token)` immediately before payment (DEC-05: no fare hold) | recompute from `room_rates` at booking |
| Booking confirmation | `book(offer, passengers)` → PNR + ticket numbers | internal: decrement availability atomically, create booking |
| Cancellation | `cancel(provider_reference)` → policy + fee | internal policy from `room_types.cancellation_policy` / `free_cancellation_hours_before` |
| Refund status | `refundStatus(provider_refund_id)` | internal refund record |
| Provider references | `bookings.provider_reference` (PNR), `flight_passengers.ticket_number` | none needed |

**Price validation.** `checkout_sessions.quote` already stores the quoted snapshot with `quoted_total_cents`, `revalidated_total_cents`, `price_changed_at` and `price_change_accepted_at`. The flow is: quote → revalidate at payment → if the total differs, set `price_changed` and require explicit consent before charging. Never silently charge a higher amount.

**Provider failures and timeouts.** Every adapter call gets a bounded timeout, a per-provider circuit breaker, and a mapped error (`provider_timeout`, `provider_unavailable`, `sold_out`, `price_changed`). A failure never produces a booking, never fabricates availability, and never leaves a `payments` row in `captured` without a booking — the ordering rule is: reserve/confirm with the provider first, then capture, then commit the booking; if capture fails after a provider confirmation, the cancellation path must run.

**The honest split**

| Capability | Without provider credentials | Needs a real provider |
|---|---|---|
| Hotel search, detail, availability, booking, voucher, cancellation | **Yes** — `hotels`/`room_types`/`room_rates` are Traway's own inventory | No |
| Flight search / pricing / ticketing | Structure, DTOs, state machine, fake | **Yes** |
| eSIM catalogue / purchase | Structure, local catalogue, fake | **Yes** |
| Payment capture / refund | Structure, fake, ledger | **Yes** |

The important correction to any earlier assumption: **hotels are not a GDS integration**. They are partner-supplied inventory entered through the hotel portal, so the entire hotel shopping and booking flow is implementable and testable with no external provider.

**Never fake availability or price.** The fake providers exist for tests and local development only, must be selected by explicit configuration, and production must refuse to start with a fake provider in the flight/eSIM/payment slot. A flight search with no provider configured returns `provider_unavailable` (503 + stable code), not an empty list and not sample flights.

---

### 5.4 D — eSIM provider integration

**Contract (`EsimProvider`)**

| Operation | Purpose | Schema already available |
|---|---|---|
| `listPackages` / `getPackage` | Catalogue synchronisation | `esim_packages` (`provider`, `provider_package_id`, `cost_cents`, `sale_price_cents`, `is_stale`, `synced_at`) |
| `checkAvailability` | Whether a package can still be sold | `esim_packages.is_active` / `is_stale` |
| `purchase(package, order)` | Buy the eSIM | `esim_orders.provider_order_id` |
| `provision(order)` | Obtain the activation payload (LPA string / QR) | `esim_orders.activation_payload` (encrypted) |
| `getStatus(order)` | pending/issued/activated/expired | `esim_orders.status`, `activated_at` |
| `getUsage(order)` | Remaining data | **No source confirmed** — F-043 |

**Catalogue synchronisation.** A scheduled job upserts by (`provider`,`provider_package_id`) — the unique constraint exists. On failure or partial data, keep the last known good catalogue, set `is_stale=true`, and **never silently delist** (Q-32's working assumption, already encoded in the schema). `markup_bp` and `sale_price_cents` are Traway-side; the provider's `cost_cents` must never be exposed to customers.

**Availability and pricing.** Sale price is the stored `sale_price_cents` (BR-008) — never recomputed at display time from cost + markup, which would drift.

**Purchase and provisioning.** Order → provider purchase → store `provider_order_id` → provision → store the encrypted activation payload. The payload is a secret: never in a list response, never logged, shown only to the owning customer, with a single-use display (BR-058). **Open item:** if "single-use" means "displayed once", an `activation_payload_viewed_at` column is required; this is deliberately not proposed until BR-058's intent is confirmed.

**Activation.** `activated_at` + status `activated`; activation is irreversible and ends the refund window (Q-16).

**Usage/status retrieval.** Only if the selected provider exposes it. The contract includes the method so the domain can call it when available; **no usage table or polling is designed** (F-043 deferred).

**Failure and refund handling.** Provider purchase failure → `esim_orders.status=failed` and no charge captured (or a refund through the normal path). Post-capture failure → refund until activation. A provider outage must not block browsing the local catalogue.

**Do not assume an API exists.** No eSIM provider is selected and no provider documentation has been verified in this repository. The catalogue may be maintained manually until an API is contracted; the sync job and `platform.esim_catalog.sync` permission then become meaningful.

---

### 5.5 E — Settlement and payout architecture

**The five layers, kept strictly separate**

| Layer | What it is | Where it lives | Mutable? |
|---|---|---|---|
| 1. Ledger entries | Immutable signed facts: sale, commission, refund, gateway fee, chargeback, payout, adjustment | `partner_ledger_entries` (`entry_key` unique, signed `amount_cents`) | **No** — append-only |
| 2. Partner balance | `SUM(amount_cents)` for the organization | **Derived**, never stored | n/a |
| 3. Settlement calculation | A closed claim over a specific set of ledger entries for a period | `settlement_periods` + `settlement_items` (**proposed**) | Status only |
| 4. Payout request | The partner asking for money already due | `payout_requests` (exists) | Status only |
| 5. Payout execution | The actual transfer, manual or via gateway | `payout_requests.method` + `provider_payout_id` + `transferred_at` | Status only |

**Why the separation matters.** Layers 1 and 2 already exist and are correct. Layers 3–5 are where double-counting, double-payment and "the partner saw a different number from the admin" appear. Modelling the settlement as *links to ledger rows* (layer 3) rather than a second money figure makes BR-114 true by construction: the partner's number and the admin's partner-due number are the same `SUM` over the same rows.

**Recommended schema** (see §4.5 for full columns and constraints): `settlement_periods` with `unique(organization_id, period_start, period_end)` and `settlement_items` with `unique(partner_ledger_entry_id)`. The second unique constraint is the guarantee that a ledger row is settled exactly once — including a payout that later fails and is re-settled.

**Negative balances.** DEC-10 requires a negative partner balance to reduce the next settlement. With a signed ledger and `settlement_items` including negative rows, this is arithmetic, not a special case. BR-100 ("deducted twice") is prevented by the same unique constraint.

**Refund carry-forward.** A refund after a settlement closed creates new negative ledger entries in the *next* period; a closed, transferred period is never edited.

**Payout accounts — explicitly not an approved design.** `payout_accounts` exists (encrypted account number/IBAN, one default per organization via `default_owner_key` unique, `pending|verified|rejected`, `verified_by`/`verified_at`). This register does **not** endorse it as final. Open items: who verifies an account and on what evidence; what happens when an account changes after a payout (re-verification window); whether an account is per-currency (USD only, so currently moot); and whether removal is soft-delete only. Recommendation: treat the existing table as a draft and add change-control semantics (a change requires re-verification and blocks payouts until verified) before Phase J.

**Payout execution.** `method` already distinguishes `manual` from `gateway`. Manual is the only mode that can be guaranteed: platform staff execute the transfer outside Traway and record `transferred_at` + `processed_by`. Gateway payouts depend on a capability the selected gateway may not have (DEC-10's own caveat), so the schema must never *require* `provider_payout_id`.

**What can be built now vs later.** Buildable now: ledger posting rules, balance query, settlement computation and periods, payout requests, manual execution, reports. Requires a gateway: automated payouts and payout status webhooks.

---

### 5.6 F — The three unresolved features

#### F-027 — "Seat selection"

> *F-027 | Seat selection | MOB | M-026 (fragment) | P3 | U | Only a confirmation bar is supplied: 3 seats (12A/12B/12C), «مجاناً», «تأكيد المقاعد». No seat map, pricing or availability design. **Not a committed v1 requirement** until `Q-09` is answered.*

**Exact ambiguity.** The screen fragment proves a confirmation control, not a feature: there is no seat map, no seat inventory, no indication whether a seat is free or paid (the bar says «مجاناً» — free — for three specific seats), no rule for who chooses, and no interaction with a provider's seat inventory. Q-09 is open and non-blocking.

**Business impact.** Low if deferred: nothing else depends on it. High if implemented badly: charging for a seat that does not exist, or promising an assigned seat the airline does not honour, creates refund and complaint exposure. Commission accounting would also need a seat line item.

**Database impact.** Deferring requires **nothing**. Implementing would need seat inventory per flight segment (cabin map, seat attributes, price), seat assignment per passenger, and provider seat-map integration — none of which any approved provider supplies yet.

**API impact.** None while deferred. Implementing would add a seat-map endpoint and seat selection on the passenger step, plus provider calls.

**Recommended interpretation.** Defer entirely. Seats are free and optional at launch (Q-09's working assumption); the confirmation bar is a design artefact, not a requirement. No seat data is stored; the passenger step completes without a seat choice.

**User clarification essential?** **No.** The catalogue already declares it uncommitted; confirming the deferral is sufficient.

#### F-043 — "eSIM usage & balance"

> *F-043 | eSIM usage & balance | MOB | M-037 (partial) | P3 | U | Booking card shows «5 GB متبقية» and a progress bar, but no usage screen, refresh rule or depletion behaviour is drawn. Not a committed v1 requirement (`Q-17`).*

**Exact ambiguity.** Where the remaining-data figure comes from: the provider's live API, a locally decremented counter, or nothing at all. Also unresolved: refresh cadence, what happens at depletion, and whether the progress bar communicates purchased allowance or remaining usage.

**Business impact.** Medium: showing a stale or wrong "remaining" figure is a trust problem, and a live-usage feature implies a provider API that may not exist or may be rate-limited. Nothing financial depends on it.

**Database impact.** Deferring requires **nothing**. Live usage would need periodic usage snapshots (a table plus a polling job and retention), because providers cap historical usage queries.

**API impact.** None while deferred. Live usage would add a usage endpoint plus a background sync per active order — with per-order rate limits that scale with active eSIMs.

**Recommended interpretation.** Display the **purchased allowance** (`esim_packages.data_mb`, `validity_days`) and the order's status, never a "remaining" figure, unless the selected provider exposes a usage API. If no source exists, hide the progress bar. No usage table, no polling.

**User clarification essential?** **Yes** — whether the product expects a live remaining-data figure (and will therefore require a provider with a usage API) or an allowance display. The recommendation is safe either way, but the UI copy depends on the answer.

#### F-071 — "Tour cancellation & refund"

> *F-071 | Tour cancellation & refund | MOB | M-068 (promise only) | P2 | U | «إلغاء مجاني خلال 24 ساعة» is stated but no cancellation UI, deadline calculation or refund rule is drawn. Not committed until `Q-24`.*

**Exact ambiguity.** The deadline's reference point (slot start vs booking date), the refund basis (full vs partial vs gateway-fee-deducted), whether the customer self-cancels or requests cancellation, and what happens to a paid but uncancelled slot when the tour is cancelled by the company.

**Business impact.** High — it is a public promise in the UI. Shipping the promise without the rule is a consumer-protection and chargeback risk; shipping neither is a missing flow for a P2 feature.

**Database impact.** Deferring requires **nothing**. Implementing per-tour variation would eventually need a policy field on `tours`; the launch rule needs none, because `tour_slots` already holds the start time and `bookings`/`refunds` already exist.

**API impact.** A cancel endpoint on the booking (already planned in F-049's pattern) plus an eligibility check: `now <= slot.start − 24h`. After the deadline the only path is a partner/platform decision, not self-service.

**Recommended interpretation.** Full refund when the customer cancels **at least 24 hours before the slot start time**; no self-service cancellation inside 24 hours (support/partner decision). Any fee reported by the gateway is stored, never computed by Traway (DEC-01). No per-tour policy field until a second policy exists.

**User clarification essential?** **Advisable** — the 24-hour rule is quoted from the UI, but the refund basis and the inside-24h behaviour must be confirmed.

#### Multi-city flights (C-13)

`FEATURES-CATALOG.md` F-022 offers multi-city as a trip type; BR-036 forbids partial implementation; Q-08 defers it; `flight_bookings.trip_type` already constrains `one_way|round_trip`.

**Recommended interpretation.** Remove multi-city from scope for v1 and hide the option. The enum already rejects it, so no schema or API change is needed. **User clarification essential? No** — the documents already defer it; only the UI decision needs confirming.

---

## 6. Conflict resolution status

Every conflict from `IMPLEMENTATION-GAP-ANALYSIS.md` §9, with its status after this register. Nothing is deleted from the analysis; superseded conflicts are marked resolved and linked here.

| ID | Conflict | Status | Resolved by / next step |
|---|---|---|---|
| C-01 | Audit required vs permanently removed | **RESOLVED** | `G0-DEC-01` — audit and activity logs permanently excluded; `platform.audit.view` removed |
| C-02 | Permission counts 83/84/78/80 | **RESOLVED (evidence) / awaiting approval (content)** | `G0-DEC-02` + `PERMISSION-CATALOG-RECONCILIATION.md`; canonical 85 keys needs approval |
| C-03 | `properties.*` vs hotels-as-profile | **RESOLVED** | `G0-DEC-03` — `hotels` is the property entity; `properties.*` canonical; `org.profile.publish` removed |
| C-04 | Visa transition authority | **RESOLVED** | `G0-DEC-04` — platform-only decision; `update_status` removed; company review retained |
| C-05 | Role naming/count (`org_owner` vs `company_owner`, R-18/R-19) | **Partially resolved** | Canonical names are the seeded `company_owner`/`hotel_owner`/`service_provider_owner`. Role **count** still differs (docs list 19, seeder creates 5 platform + 11 templates = 16). Tracked as a documentation sub-item under `G0.2`; no code impact |
| C-06 | `bookings.view` organization scope | **OPEN** | Sub-item P-09 of `G0.2` — recommend `['company','hotel']` until the service-provider catalogue exists |
| C-07 | Tenant context from `X-Organization-Id` header | **OPEN — not a Gate 0 decision** | Phase A item A3 (remove the header fallback). Docs and `TenantResolver` still disagree |
| C-08 | Empty scope = all vs none | **RESOLVED** | `G0-DEC-05` — empty means nothing; fail closed |
| C-09 | Membership transfer mutates `organization_id` | **OPEN — not a Gate 0 decision** | Phase A item A4 (remove + create, clear scopes) |
| C-10 | Invitation resend semantics | **OPEN — not a Gate 0 decision** | Phase A item A5 |
| C-11 | One property vs many per hotel org | **RESOLVED** | `G0-DEC-03` — many properties; Q-49 answered |
| C-12 | Global NULL-team owner templates | **OPEN — not a Gate 0 decision** | Q-50 remains; recommend accepting copy-on-create (already implemented) and documenting it |
| C-13 | Multi-city offered vs forbidden | **RECOMMENDED** | §5.6 — remove from v1 scope; awaiting approval under `G0.7` |
| C-14 | F-027, F-043, F-071 unresolved | **RECOMMENDED** | §5.6 / `G0.7` — defer F-027 and F-043 usage, implement F-071 eligibility |
| C-15 | Phantom feature references F-113/F-115 | **RESOLVED** | Documentation correction — matrix rows re-pointed to F-101/F-106/F-096/F-108 in the reconciliation (§8 P-11, P-12) |
| C-16 | Settlement schema absent | **RECOMMENDED** | `G0.8` / §5.5 — `settlement_periods` + `settlement_items`; awaiting approval |
| C-17 | Settings authority missing | **RESOLVED** | `G0-DEC-02` — `platform.settings.manage` added; `platform_settings` table already exists |
| C-18 | Three legal documents vs generic document table | **OPEN — not a Gate 0 decision** | Requires a document-type enum decision; Phase H.3 (partner onboarding). `BR-081` names tourism licence, commercial registration, tax card |

**Summary (18 conflicts total):** **8 resolved** — C-01, C-02 (evidence for the count conflict; catalogue content still needs approval), C-03, C-04, C-08, C-11, C-15, C-17. **3 recommended, awaiting approval** — C-13, C-14, C-16. **1 partially resolved** — C-05 (names settled, 19-vs-16 role count still a documentation sub-item). **6 carried to Phase A or later** because they are implementation decisions rather than Gate 0 contracts — C-06, C-07, C-09, C-10, C-12, C-18. 8 + 3 + 1 + 6 = 18.

---

## 7. Documentation corrections required

These are documentation-only corrections identified while producing this register. The ones already applied to `IMPLEMENTATION-GAP-ANALYSIS.md` and `phase-02/OPEN-QUESTIONS.md` were made in the same task; the rest belong to the phase-02 documents and must be applied when `G0.2` is approved, because they change the permission catalogue text itself.

| # | Document | Correction | Status |
|---|---|---|---|
| D-01 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §3 header and totals | 83 → 85 (37 platform + 48 organization) | **Pending `G0.2` approval** |
| D-02 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §9 row 1 | 78 (33+45) is stale; must match the approved catalogue | **Pending `G0.2`** |
| D-03 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §9 note | Remove `platform.audit.view` from the "decision-backed" list; the count is also wrong (more than 5 keys are `D`) | **Pending `G0.2`** |
| D-04 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §3.1 | Remove the `update_status` row; narrow the `review` description | **Pending `G0.2`** |
| D-05 | `ROLE-PERMISSION-SCOPE-MATRIX.md` §3.2 | Add the 4 `properties.*` rows as canonical (already present), add `visa_requests.review`, add `settlements.accounts.manage` to the non-delegable list, remove `org.profile.publish` if it appears | **Pending `G0.2`** |
| D-06 | `AUTHORIZATION-ARCHITECTURE.md` §7.2 | `org.delete` is an owner-only **action**, not a permission — reword | **Pending** |
| D-07 | `AUTHORIZATION-ARCHITECTURE.md` §11 | No change required — §11 already states audit is removed, which is now confirmed policy under `G0-DEC-01`. Editing that file was outside the required deliverable list, so the cross-reference lives here instead | **No change required** |
| D-08 | `FEATURES-CATALOG.md` F-112 | Retitle to "Dynamic roles and permissions"; drop the audit clause (G0-DEC-01) | **Pending** |
| D-09 | `BUSINESS-LOGIC.md` BR-107, BR-111, BR-124 | Mark as superseded by `G0-DEC-01` (audit permanently excluded); keep the original text | **Pending** |
| D-10 | `BUSINESS-LOGIC.md` BR-121 | Now consistent with `G0-DEC-05` (empty scope = no access); add a pointer | **Pending** |
| D-11 | `phase-02/OPEN-QUESTIONS.md` Q-38, Q-46, Q-49 | Q-38 → answered by `G0-DEC-01`; Q-46 → answered by `G0-DEC-04`; Q-49 → answered by `G0-DEC-03` | **Applied** |
| D-12 | `IMPLEMENTATION-GAP-ANALYSIS.md` §9 | Mark C-01, C-03, C-04, C-08, C-11, C-17 resolved and link to this register | **Applied** |
| D-13 | `IMPLEMENTATION-STATUS.md` | Record the Gate 0 state and the pending approvals | **Applied** |

---

## 8. Final approval checklist

Approve each line individually. Nothing in Phase A starts until every line the implementation depends on is approved.

### Already approved — no action needed

- [x] **G0-DEC-01** Audit and activity logs permanently excluded; `status_histories` restricted to business status transitions.
- [x] **G0-DEC-03** A hotel organization owns multiple hotel properties; `hotels` is the property entity; `membership_hotel_scopes` scopes staff; bookings carry `hotel_id`.
- [x] **G0-DEC-04** Only platform staff with `platform.visa_requests.decide` may approve or reject a visa; company users review documents only; the six customer statuses are unchanged.
- [x] **G0-DEC-05** An empty scope grants nothing; every scoped query fails closed.

### Awaiting your approval

- [ ] **G0.2 (content)** — adopt the **85-key** catalog (37 platform + 48 organization) exactly as listed in `PERMISSION-CATALOG-RECONCILIATION.md`: 9 keys added, 3 removed, 0 renamed, 1 description narrowed, `settlements.accounts.manage` added to the non-delegable list.
- [ ] **G0.2 (P-09)** — keep `bookings.*` and `customers.view` closed to `service_provider` until its service catalogue is defined (a deliberate, documented narrowing of the matrix's "All").
- [ ] **G0.5** — notifications launch on database + e-mail over the database queue, deterministic notification IDs for dedupe, transactional categories always on, push/SMS/WhatsApp deferred behind adapters.
- [ ] **G0.6 (architecture)** — gateway/flight/eSIM adapter contracts with fakes; hotels are first-party inventory and need no provider; unconfigured flight/eSIM search returns `provider_unavailable`; add `payment_webhooks`.
- [ ] **G0.6 (providers)** — *which* payment gateway, flight provider and eSIM provider to integrate, and whether the gateway supports partner payouts (DEC-10's assumption). **Requires your answer.**
- [ ] **G0.7** — defer F-027 seat selection; defer F-043 live usage (show allowance only); implement F-071 as "full refund when cancelled ≥24h before slot start"; remove multi-city from v1.
- [ ] **G0.7 (clarification)** — confirm the F-043 display decision (allowance vs live remaining data) and the F-071 refund basis.
- [ ] **G0.8** — add `settlement_periods` + `settlement_items` keyed uniquely to `partner_ledger_entries`; balance stays derived; `payout_accounts` remains a draft pending change-control design.

### Carried to Phase A (not Gate 0, listed so nothing is lost)

- [ ] A1 empty-scope semantics (decision approved; implementation pending)
- [ ] A3 remove the `X-Organization-Id` header fallback
- [ ] A4 membership transfer as remove + create
- [ ] A5 invitation resend/rotation
- [ ] C-06 `bookings.view` scope, C-12 global templates, C-18 legal document types

### Explicit non-goals of this task (confirmed)

- No Phase A work started.
- No application code, migration, model, service, controller, policy, middleware, route, seeder or test modified.
- No `.env` change, no migration run, no database change.
- Nothing committed or pushed.

