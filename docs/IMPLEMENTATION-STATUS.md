# Traway — Implementation Status

Engine: MySQL 8.4 LTS (development, production, integration tests). USD only, integer minor units.
Status labels: **Not Started**, **In Progress**, **Implemented**, **Tested**, **Blocked**.

Last updated: 2026-11-24

---

## Gate 0 — Decision Alignment

**Status: split into two approval gates. Gate 0A is ready for owner review. Gate 0B is pending.**

Registered in [`GATE-0-DECISION-REGISTER.md`](./GATE-0-DECISION-REGISTER.md) and [`PERMISSION-CATALOG-RECONCILIATION.md`](./PERMISSION-CATALOG-RECONCILIATION.md). Produced by the gap analysis in [`IMPLEMENTATION-GAP-ANALYSIS.md`](./IMPLEMENTATION-GAP-ANALYSIS.md). Documentation map: [`README.md`](./README.md).

### Gate 0A — Authorization (blocks Phase A)

| Item | Subject | Status |
|---|---|---|
| `G0-DEC-01` | Audit and activity logs | **Approved** — permanently excluded |
| `G0-DEC-02` | One centralized permission catalog | **Approved (principle)** |
| `G0-DEC-03` | Hotel properties | **Approved** — one hotel organization, many properties |
| `G0-DEC-04` | Visa decision authority | **Approved** — platform staff only |
| `G0-DEC-05` | Empty scope semantics | **Approved** — empty means no access |
| Service-aware permission catalog | Exact keys, service applicability, organization eligibility, scope requirements and delegation metadata | **Awaiting owner approval** — historical 85-key proposal withdrawn as final target; see `SERVICE-AWARE-PERMISSION-CATALOG.md` |
| Multi-service provider model | One provider organization may operate multiple Traway-enabled services; membership service scopes remain separate | **Product direction confirmed; implementation design pending Gate 0A** |
| P-09 / P-10 | Blanket exclusion of provider bookings/customer context | **Superseded** — use service-specific permission families and contextual customer visibility; do not restore blanket grants |
| Delegation | Family rule, non-delegable list, owner-only actions, owner templates | **Awaiting final Gate 0A approval** |
| Empty-scope enforcement | Query-layer filter, ownership as the explicit grant, fail-closed service/property/resource scopes | **Principle approved (`G0-DEC-05`); code enforcement still pending** |
| Organization service enablement | Traway-controlled enabled services, distinct from employee membership scopes | **Blocking Gate 0A implementation design; Q-54 open** |
| Resource scope by service | Hotel property, tour/resource, visa assignment/offering boundaries | **Blocking Gate 0A implementation design; Q-56 open** |
| Provider eSIM responsibilities | Provider-side package/order/activation/support/refund lifecycle | **No provider permissions approved; Q-53 open** |
| Unified provider dashboard | Multi-service shell, queues and reports | **Provider UI requirements incomplete; Q-55 open** |

### Gate 0B — Business and integration (does not block Phase A)

| Item | Subject | Status |
|---|---|---|
| G0.5 | Notification architecture and delivery | Recommended — awaiting approval |
| G0.6 | Payment / flight / hotel / eSIM provider architecture | Recommended — awaiting approval |
| G0.6 | Provider selection (gateway, flight, eSIM; partner-payout capability) | **Requires clarification** |
| G0.7 | F-027 seat selection, F-043 eSIM usage, multi-city | Recommended — awaiting approval |
| G0.7 | F-071 tour cancellation policy | **Pending — no policy approved** |
| G0.8 | Settlement schema and lifecycle | Recommended — awaiting approval |

**No code, migration, seeder or `.env` change was made for Gate 0.** **Phase A does not start until Gate 0A is approved in full.** The confirmed multi-service provider direction adds service enablement, service applicability and resource-boundary decisions to Gate 0A; it supersedes the old blanket P-09 recommendation and the fixed 85-key target. Gate 0B items block only the phases that depend on them.

### Effects on the previously recorded state

- **Permission catalogue.** The "80 permissions (36 platform + 44 organization)" figure in Phase 0 below is the *seeded* count, not the canonical one. The proposed catalogue is **85 keys** (37 + 48), pending approval. Against the seeder that is **add 6, remove 1, rename 1** — not "9 additions and 3 removals", which was the wider union comparison (88 keys) and is explained separately in the reconciliation document §3.
- **Audit.** The Phase 0 decision to delete the audit architecture is now confirmed as permanent policy (`G0-DEC-01`), not just a repository cleanup.
- **Visa.** `platform.visa_requests.decide` is confirmed as the only final-decision right (`G0-DEC-04`); no `visa_requests.update_status` key exists in either family, and `platform.visa_requests.update_status` is permanently removed from the catalogue.
- **Status history.** The organization read key is renamed `status_history.view` (formerly `team_activity.view`) so it cannot be read as a general activity feed. `status_histories` remains the only history table (G0-DEC-01).
- **Properties.** `hotels` is confirmed as the hotel-property entity, and `properties.*` becomes the canonical permission family for it (`G0-DEC-03`).
- **Known defect recorded, not fixed:** the `membership_hotel_scopes` migration comment still says "No rows = every property the organization owns", which contradicts `G0-DEC-05`. To be corrected when Phase A touches the file.

---

## Phase 0 — Repository Baseline and Architecture Alignment

**Status: Tested**

### Baseline report

The repository contained a Laravel 13.43 foundation plus 72 staged-but-uncommitted files
(models, migrations, seeders) written against the earlier documentation. Nothing had ever been
migrated, so the schema had never been executed. All user work was preserved; corrections were
applied in place rather than rewritten from scratch.

Findings and corrections:

| # | Finding | Correction |
|---|---------|-----------|
| 1 | `config/permission.php` had `teams => false` and `team_foreign_key => team_id` while every migration/model assumed `organization_id`. | Teams enabled; `team_foreign_key` set to `organization_id`. |
| 2 | `config/permission.php` declared the `models` array twice (package models won over the custom ones). | Single declaration pointing at `App\Models\Role` / `App\Models\Permission`. |
| 3 | Audit Log architecture still present (migration, model, service, event, listener, controller, resource, policy, 2 tests). | All deleted. No replacement. `status_histories` retained and restricted to entity lifecycle transitions. |
| 4 | `.env.example` and `config/database.php` defaulted to SQLite. | MySQL 8.4 documented as the default; InnoDB engine set; `phpunit.xml` points at a dedicated `traway_testing` database. |
| 5 | Hotel property staff scoping had no storage. | `membership_hotel_scopes` table + `MembershipHotelScope` model + `Membership::hotelScopes()/hotelIds()/isRestrictedToHotel()`. |
| 6 | Payouts had no destination account. | `payout_accounts` table (encrypted account number, one default per organization) + `payout_requests.payout_account_id`. |
| 7 | `bookings` had no hotel property reference. | `bookings.hotel_id` (nullable, restricted) + index + `Booking::hotelProperty()`. |
| 8 | `is_delegable` was duplicated in storage and in documentation. | Column removed; delegation is defined once in `config/authorization.php` and read via `Permission::isDelegable()`. |
| 9 | Service Provider had no permissions and no Owner template. | Generic (non-service) permissions opened to `service_provider`; protected `service_provider_owner` template added. No provider service category was invented. |
| 10 | Company staff could record visa decisions (`visa_requests.update_status`). | Removed. Visa decisions are `platform.visa_requests.decide`, platform-only. Company staff keep document review (`visa_requests.review`). |
| 11 | Company owner template was named `org_owner`. | Renamed to `company_owner`; all three owner templates are `is_system` and listed in `config/authorization.php`. |
| 12 | R-17 "Service Owner" risk. | Not created as a template; remains a custom organization role. |
| 13 | `membership_hotel_scopes` was placed in the memberships migration but references `hotels`, which is created later. | Extracted into `2026_11_23_100910_create_membership_hotel_scopes_table.php` after the hotel catalog. |

### Key decisions encoded

- `config/authorization.php` is the single source of truth for: protected owner templates, non-delegable
  permissions, owner-only structural actions, permission families, service scopes, coupon limits,
  account-deletion grace period, currency.
- Permission catalogue: 80 permissions (36 platform + 44 organization), 5 platform roles, 11 organization templates.

---

## Phase 1 — Database Foundation

**Status: Tested**

### Commands executed (MySQL 8.4.3, databases `traway` / `traway_testing`)

```
php artisan migrate:fresh --seed --force     # 33 migrations + 3 seeders, all DONE
php artisan db:seed --force                  # idempotent re-run, all DONE
php artisan migrate:rollback --force         # every down() succeeded, only `migrations` remained
php artisan migrate --seed --force           # clean re-apply, all DONE
php artisan test tests/Feature/Database/SchemaIntegrityTest.php   # 11 passed (45 assertions)
php artisan test                                                 # 18 passed (52 assertions)
```

### Guarantees now proven by tests (`tests/Feature/Database/SchemaIntegrityTest.php`)

1. Every expected table exists and `audit_logs` does not.
2. A user cannot hold two memberships in one organization.
3. An organization can only have one **active** owner; an invited owner does not reserve the slot.
4. Coupon codes are unique per issuer (platform `scope_key = 0`, organization = its id) and reusable across issuers.
5. One rate row per room type per date.
6. Booking references are unique.
7. Partner ledger entries are append-only and idempotent by `entry_key`.
8. `status_histories` is append-only.
9. An organization with hotels cannot be force-deleted (FK restrict).

### Schema notes

- Monetary columns are unsigned/big integers in USD cents; `currency` is `char(3)` defaulting to `USD`.
- `bookings.reference` (customer-facing, random) is separate from `bookings.provider_reference`.
- Financial tables (`payments`, `refunds`, `partner_ledger_entries`, `status_histories`) have no soft deletes;
  ledger/history models throw `LogicException` on update/delete.
- One-coupon-per-booking is enforced by `coupon_redemptions.booking_id` unique.

### Database credentials used locally

- MySQL 8.4.3 (Laragon), user `root`, empty password, host `127.0.0.1:3306`.
- Development database `traway`; test database `traway_testing`. Existing unrelated databases were not touched.

---

## Phase 2 — Authorization and Organization Management

**Status: In Progress** (authorization core implemented and tested; organization/employee management remaining)

- [x] Spatie Teams enabled on `organization_id`.
- [x] Central `config/authorization.php` (delegation, owner-only actions, scopes).
- [x] Protected owner templates seeded for company / hotel / service_provider.
- [x] Platform-only visa decision permissions.
- [x] `AuthorizationService`: effective permissions from team-scoped roles + direct grants, active-membership check,
      organization-type ceiling, service scope, hotel property scope, resource ownership, delegation boundary,
      `canGrant()` / `delegablePermissions()`, and `permissions_version` cache invalidation.
  Implemented in `app/Services/Authorization/AuthorizationService.php`.
- [x] Authorization tests: `tests/Feature/Authorization/AuthorizationServiceTest.php` — 14 passed (27 assertions).
  Covers cross-tenant denial, suspended memberships, foreign-team roles, direct grants, non-delegable
  permissions, service scope, hotel property scope, cross-tenant resource access, platform/tenant family
  separation, delegation ceiling and cache invalidation.
- [x] Policies for every protected business resource — 21 policy classes exist. **Caveat:** no controller calls them yet, so nothing is enforced end-to-end.
- [x] Organization management (applications, approval, suspension, ownership lifecycle) — `OrganizationService`, `OrganizationApplicationService`, `RoleTemplateService`. **Caveat:** service layer only, no HTTP. Known defects: membership transfer mutates `organization_id` instead of remove + create; invitation resend is rejected rather than rotated.
- [x] Employee management (invitation, acceptance, roles, direct grants, transfer, removal, token revocation) — `MembershipService`. **Caveat:** service layer only, no HTTP.
- [ ] Authorization and IDOR test suite.
- [ ] Phase A repairs (from the gap analysis): empty-scope semantics (`G0-DEC-05`), assigned-only filters, removal of the `X-Organization-Id` tenant fallback, membership transfer, invitation rotation, policy enforcement at the HTTP boundary.

---

## Remaining phases

Phases 3–15: **Not Started**.

### Latest full-suite result

```
php artisan test                 # 32 passed (79 assertions)
```

---

## Open questions (blocking nothing yet)

1. Coupon management is granted to `company` organizations only. Hotels sell their own rooms and may need
   their own coupons; a decision is required before widening `coupons.*` (Q-41).
2. Service Provider service catalogue and its permissions are undefined, so `service_provider_owner` holds
   structural permissions only and commission stays at 0 BP. This is why `bookings.*` and `customers.view`
   stay closed to service providers in the canonical catalog (reconciliation sub-item P-09).
3. No payment gateway is approved. Payments/refunds will ship as an adapter contract plus a fake provider
   until credentials and gateway documentation exist (`G0.6`). Whether the chosen gateway can execute
   partner payouts is still unverified and DEC-10 depends on it.
4. No eSIM provider contract exists (`G0.6`). The catalogue can be maintained manually until an API is
   contracted; no usage API is assumed (`G0.7`, F-043).
5. No flight provider exists. Flight search must return an explicit `provider_unavailable` state rather than
   sample availability or prices (gap analysis §11, register §5.3).
6. Audit and permission-count questions are now closed: see `G0-DEC-01` and the permission reconciliation.
   The remaining Gate 0 approvals do not change the schema already built.


## Multi-service authorization status update — 2026-11-24

This section supersedes stale Gate 0A and open-question summaries above where they describe the old type-only provider model.

- **Confirmed product direction:** a single `service_provider` organization may operate multiple service lines.
- **Authorization rule:** effective access is the intersection of organization ownership, Traway-enabled services, membership service scopes, permission applicability, resource/property scope and assignment/workflow constraints.
- **Flights:** platform-only in the current product scope.
- **Visa decisions:** final approve/reject authority remains platform-only under `G0-DEC-04`; partner-side document review remains distinct.
- **Customer data:** contextual and minimum-necessary; no unrestricted partner customer directory is implied.
- **Open blockers:** Q-53 (provider eSIM lifecycle), Q-54 (organization service enablement workflow), Q-55 (unified provider dashboard), Q-56 (resource scopes by service).
- **Implementation state:** no implementation change is recorded by this update. The next work is Gate 0A decision closure and a source-to-source code verification before any migration or policy changes.

The implementation baseline and historical command/test results above are preserved as recorded evidence; they were not rerun during this documentation update.
