# Traway — Implementation Status

Engine: MySQL 8.4 LTS (development, production, integration tests). USD only, integer minor units.
Status labels: **Not Started**, **In Progress**, **Implemented**, **Tested**, **Blocked**.

Last updated: 2026-11-24

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
- [ ] Policies for every protected business resource.
- [ ] Organization management (applications, approval, suspension, ownership lifecycle).
- [ ] Employee management (invitation, acceptance, roles, direct grants, transfer, removal, token revocation).
- [ ] Authorization and IDOR test suite.

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
   their own coupons; a decision is required before widening `coupons.*`.
2. Service Provider service catalogue and its permissions are undefined, so `service_provider_owner` holds
   structural permissions only and commission stays at 0 BP.
3. No payment gateway is approved. Payments/refunds will ship as an adapter contract plus a fake provider
   until credentials and gateway documentation exist.
4. No eSIM provider contract exists.
