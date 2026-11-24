# Implementation Gap Analysis — Phase 1 & Phase 2

**Audit date:** 2026-11-24
**Audited by:** coding agent (read-only audit)
**Sources of requirements:** `docs/phase-01/SCREEN-COVERAGE-REPORT.md`, `docs/phase-01/UI-SCREENS-INVENTORY.md`, and all files in `docs/phase-02/` (`FEATURES-CATALOG.md`, `BUSINESS-LOGIC.md`, `AUTHORIZATION-ARCHITECTURE.md`, `ROLE-PERMISSION-SCOPE-MATRIX.md`, `USER-ROLES-AND-PERMISSIONS.md`, `WORKFLOWS.md`, `OPEN-QUESTIONS.md`, `DB-DECISION-BRIEF.md`).
**Implementation inspected:** `app/`, `routes/`, `database/migrations/`, `database/seeders/`, `config/`, `tests/`, `composer.json`.

**Working rules honoured for this audit:** no tests were run, no database or migration command was executed, `.env` was not changed, no existing work was deleted or reset, no Phase 3 work was started, and no implementation was performed. This document is the only artefact produced.

> **Update — 24 Nov 2026 (Gate 0).** The blocking decisions in §11 are now recorded in [`GATE-0-DECISION-REGISTER.md`](./GATE-0-DECISION-REGISTER.md), and the permission catalogue in §6 has been reconciled in [`PERMISSION-CATALOG-RECONCILIATION.md`](./PERMISSION-CATALOG-RECONCILIATION.md). Four conflicts (C-01, C-03, C-04, C-08) and three further items (C-11, C-15, C-17) are resolved; the rest are tracked in §9.1 below. **The findings in this report are preserved as audit history and are not rewritten.** Still no implementation code was changed: the Gate 0 work was documentation and decision-alignment only.

---

## 1. Status definitions used

| Status | Meaning in this report |
|---|---|
| **Implemented** | The documented behaviour exists and works end-to-end in the backend (data + service/domain logic + HTTP/API or equivalent trigger). |
| **Partial** | Meaningful pieces exist (schema, model, policy, config or service), but a required part is missing — usually the HTTP endpoint, the domain service, the provider integration, or an enforcement point. |
| **Not Implemented** | No meaningful implementation exists beyond incidental scaffolding. |
| **Needs Clarification** | The documentation is internally ambiguous or contradicts another documented decision, so implementation cannot be judged or started safely. |

> **Important:** a model, migration, policy or service class existing does **not** make a feature implemented. This report treats "feature" as the documented user/system capability, not the presence of a class.

---

## 2. Executive verdict

### 2.1 Headline

The repository is a **well-shaped Phase 0/Phase 2 data-and-authorization foundation**, not a working application backend.

- 61 `Schema::create` calls (application, Spatie and framework tables) and ~50 Eloquent models exist, with careful tenant keys, money-as-integer-cents, reference columns, state fields, append-only ledger tables and one-owner DB constraints.
- A substantial **authorization core** exists: `AuthorizationService`, `TenantResolver`, `config/authorization.php`, 21 policies, 5 platform roles, 11 organization role templates and 80 permission keys, plus organization lifecycle/application/membership services.
- The **entire HTTP layer is empty**: `routes/api.php` exposes only `GET /api/user`; all 10 controllers are empty classes; all 12 form requests have `authorize(): false` and empty `rules()`; all 4 API resources are empty; the 4 auth services and 2 admin services are empty stubs.
- There are **no Events, Listeners, Jobs, Mailables, Notifications, console commands or scheduled tasks**.
- There are **no provider integrations** (flights, hotels, eSIM, payments/refunds, SMS, social login, OCR, file storage).
- Therefore **no documented feature is implemented end-to-end**.

### 2.2 Feature counts (Phase 2 catalogue, F-001–F-112)

| Status | Count |
|---|---:|
| Implemented | **0** |
| Partial | **80** |
| Not Implemented | **29** |
| Needs Clarification | **3** |

The three clarification items are F-027 (seat selection), F-043 (eSIM usage/balance) and F-071 (tour cancellation), which the documentation itself leaves unresolved; F-022 also has an internal docs conflict (multi-city offered vs "must not be partially implemented").

### 2.3 Direct answers to the six requested questions

1. **What from Phase 1 is fully implemented?**
   None at the backend level. Phase 1's *documentation deliverables* are complete, and the database/authorization foundation covers parts of the Phase 1 data model, but no Phase 1 screen or journey has a working backend API.

2. **What from Phase 1 is partially implemented or missing?**
   Partial: authentication/profile data model, travel-catalogue schema (flights/visas/eSIM/hotels/tours), bookings/payments/refunds schema, partner-organization schema, tenant authorization. Missing entirely: every mobile API, every dashboard API, search, pricing, checkout, payment capture, ticketing/e-ticket, visa processing endpoints, notifications delivery, reports/exports, and all provider integrations.

3. **What from Phase 2 is fully implemented?**
   None end-to-end. At service layer only (no HTTP, no notifications, no scheduling): organization application submit/approve/reject/activate, organization create/suspend/reinstate/close/ownership transfer, employee invitation/acceptance/role assignment/direct grants/scope assignment/suspend/remove, and tenant-permission evaluation with policy classes. These still contain the conflicts and defects listed in §6 and §9.

4. **What from Phase 2 is partially implemented or missing?**
   Nearly everything. 80 of 112 features are schema/model/policy-only. The largest missing blocks are: auth endpoints and verification services, catalogue read APIs, quote/pricing engines, checkout and payment, booking state machine and status history writer, notification generation/delivery, cancellation/refund workflows, visa review workflow, eSIM provisioning, partner/admin CRUD, settlements/payouts, reports/exports, and account-deletion execution.

5. **Which requirements have not been started?**
   29 features (§5), all 15 workflows end-to-end, all BR rules marked **Not Implemented** in §8, all provider integrations, all scheduled jobs, all API endpoints, and the Phase 1 mobile/dashboard surfaces themselves (this repository contains no mobile or dashboard frontend).

6. **What should be implemented next, in the correct order?**
   See §11. The order is: (1) resolve blocking documentation conflicts, (2) repair authorization/scope defects, (3) build the HTTP foundation and auth/profile APIs, (4) notification/event infrastructure, (5) catalogue read APIs, (6) quote/pricing engines, (7) checkout/payment/booking state machine, (8) post-booking/refund/visa/eSIM flows, (9) partner/admin portals, (10) settlements/reports/exports, (11) account deletion and hardening.

---

## 3. Verification method and evidence baseline

### 3.1 What was verified by inspection

| Area | Evidence found | Consequence |
|---|---|---|
| Framework | Laravel `^13.17`, PHP `^8.3`, Sanctum 4, Socialite 5, Spatie Permission 8 | Baseline only; no queue Horizon, no payment/SMS/storage SDKs |
| Routes | `routes/api.php` = only `GET /api/user` behind `auth:sanctum`; `routes/web.php` default; `routes/console.php` only `inspire` | No documented endpoint exists |
| Controllers | 10 classes, 11 lines each, empty bodies | No request handling |
| Form requests | 12 classes, 29 lines each, `authorize(): false`, empty `rules()` | No validation anywhere |
| Resources | 4 classes, 19 lines each, empty | No response contract |
| Auth services | `AuthService`, `EmailVerificationService`, `PhoneVerificationService`, `SocialAuthService` = 14-line stubs | No login/OTP/social logic |
| Admin services | `RolePermissionService`, `UserManagementService` = 14-line stubs | No role/user administration |
| Middleware | `EnsureAccountIsActive`, `EnsureAdminAccess` implemented; `EnsureEmailIsVerified`, `EnsurePhoneIsVerified` pass-through; aliases registered | 2 of 4 real |
| Domain services | `AuthorizationService` (417 lines), `TenantResolver` (101), `MembershipService` (613), `OrganizationService` (307), `OrganizationApplicationService` (272), `RoleTemplateService` (96), `InvitationResult` | Only implemented domain logic |
| Policies | 21 policy classes (Booking, Hotel, RoomType, RoomRate, Tour, VisaOffering, VisaApplication, Coupon, Membership, Role, Permission, User, Organization, OrganizationApplication, OrganizationDocument, PayoutAccount, PayoutRequest, EsimPackage, Payment, Refund + `TenantPolicy`) | Authorization rules exist for resources that have no endpoints |
| Schema | 61 tables including catalogues, bookings, payments, refunds, ledger, payout requests, notifications, deletion requests, favorites, documents, checkout sessions | Data model is ahead of behaviour |
| Missing schema | `settlements` / settlement items, `reviews`, `airports`/`cities`/`destinations`, search history, coupon `minimum_spend`, notification preferences | Several features cannot be built without new tables |
| Async | No `app/Events`, `app/Listeners`, `app/Jobs`, no Mailables/Notifications, default queue tables only | BR-104 event-driven notifications impossible today |
| Tests | `tests/Feature/Auth/*`, `tests/Feature/Admin/*` are placeholder tests asserting `GET /` returns 200; real coverage exists only for schema, policies/authorization and organization services | Passing suite does **not** prove auth or admin features |

### 3.2 Last known test state (not re-run)

Per the audit instruction, tests were not executed. Last recorded runs before this audit: full suite 32 passed (79 assertions); `PolicyTest` 17 passed (49 assertions); organization suite 36 passed / 5 failed, with fixes applied afterwards but never re-run. No conclusion in this report depends on tests passing.

### 3.3 Repository scope note

This repository is the Laravel backend only. Phase 1's mobile app (68 screen states) and dashboards (40 states) are not present as code here. This report therefore assesses the **backend capability each screen/document requires**, not the UI implementation.

---

## 4. Phase 1 assessment (screens → backend capability)

**Sources:** `docs/phase-01/UI-SCREENS-INVENTORY.md`, `docs/phase-01/SCREEN-COVERAGE-REPORT.md`.

Phase 1 is a screen inventory, so it does not itself define API contracts. Its backend implication is: every interactive state needs endpoints, validation, state and data. Because `routes/api.php` contains a single `/user` route, **no screen has a working backend**.

| Phase 1 module | Screens | Backend schema | Backend behaviour | Status | Main gap |
|---|---|---|---|---|---|
| Onboarding (M-001–M-004) | 4 | n/a | None | **Not Implemented** | Client-only feature; no backend requirement, but also no app code in repo |
| Authentication & account creation (M-005–M-013) | 9 | users (+phone/status/profile flags), email/phone codes, social accounts, devices, Sanctum tokens | Services/controllers/requests/routes empty | **Partial** | OTP/login/social/verify/reset/session endpoints |
| Home & discovery (M-014–M-018) | 5 | No airport/city/destination/search tables | None | **Not Implemented** | Reference data, home feed, global search, offers endpoints |
| Flights (M-019–M-026) | 8 | flight bookings/segments/passengers only | None | **Partial** | Flight search/provider, filters/sorting, seat selection, pricing, checkout |
| Visas (M-027–M-032) | 6 | offerings/documents/applications/status histories | None | **Partial** | Catalogue read API, upload, submission, tracker, lifecycle transitions |
| eSIM (M-033–M-037) | 5 | packages/orders | None | **Partial** | Country/package read APIs, device check, provider purchase, QR issuance, usage |
| Bookings management (M-037–M-042) | 6 | bookings + service sub-tables + refunds/ledger | None | **Partial** | List/detail APIs, e-ticket, reschedule, baggage, cancellation/refund |
| Profile/travellers/payments/settings (M-043–M-056) | 14 | travelers, documents, favorites, payment methods, terms, deletion requests | None | **Partial** | CRUD APIs, OCR, settings/preferences, support, deletion workflow |
| Offers & destinations (M-057–M-058) | 2 | coupons only; no destinations | None | **Partial** | Offers API, featured destinations data/API |
| Hotels (M-059–M-064) | 6 | hotels, room types, rates/availability | Model bounds scope only | **Partial** | Search, filters, map, detail, booking, voucher |
| Tours (M-065–M-068) | 4 | tours, itinerary, pickup points, slots | None | **Partial** | Catalogue, detail, booking, voucher, cancellation |
| Partner onboarding & portal login (D-01) | 2 | organizations, applications, memberships | Application/activation services, no routes | **Partial** | Portal auth, account-type routing, password reset |
| Join requests (D-02) | 3 | organization applications/documents | `submit()` only; no document handling/HTTP/email | **Partial** | Uploads, reference, acknowledgment, tracking API |
| Administration portal (D-03) | ~19 states | organizations, platform settings, deletion requests, ledger/payouts, coupons, visas/eSIM | Policies + a few org services | **Partial** | Every admin API, KPIs, notifications, settlement execution, role/permission CRUD |
| Tourism-company portal (D-04) | 10 | tours, visa apps, bookings, ledger | Policies + membership services | **Partial** | Overview, notifications, tour authoring/review, bookings, visa workspace, reports, settings |
| Hotel portal (D-05) | 7 | hotels/rooms/rates, bookings | Policies only | **Partial** | Overview, notifications, calendar, check-in/out, employees, publish, reports |
| Restricted employee portal (D-06) | 3 | memberships/scopes/assignments | Policies, scope services; assignment filter missing | **Partial** | Assigned-only queries, read-only enforcement, notifications |

**Phase 1 conclusion:** documentation coverage is complete; backend coverage is foundation-only. No Phase 1 journey is usable yet.

---

## 5. Phase 2 feature audit (F-001–F-112)

**Source:** `docs/phase-02/FEATURES-CATALOG.md` (each ID below is the catalogue ID; Phase 1 screen references are in that catalogue). Where a row says "schema", the corresponding tables/models were verified in `database/migrations/` and `app/Models/`.

### 5.1 Authentication, onboarding and session (F-001–F-013)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-001 | Brand splash & boot routing | **Not Implemented** | — | Client feature; no backend contract required |
| F-002 | Onboarding carousel | **Not Implemented** | — | Client feature; no backend contract required |
| F-003 | Guest browsing | **Not Implemented** | — | Public read endpoints and guest policy; BR-028 not enforced anywhere |
| F-004 | Phone login with SMS OTP | **Partial** | `users.phone/phone_verified_at/status`; `phone_verification_codes` (code_hash, channel, expires_at, attempts, verified_at); `PhoneVerificationCode` model; `PhoneVerificationService` stub; `SendPhoneOtpRequest`/`VerifyPhoneOtpRequest` empty | OTP issue/verify with single-use + attempt limit + resend invalidation, SMS provider, user provisioning, Sanctum token with `membership_id`, endpoint |
| F-005 | Social login (Apple/Google) | **Partial** | `social_accounts` table/model; Socialite installed; `SocialAuthService` stub; `SocialAuthController` empty | Provider config, callback/token verification, account linking, neutral errors, endpoint |
| F-006 | Email & password login | **Partial** | `users`, Sanctum, `AuthService` stub, `LoginRequest` (`authorize(): false`, no rules), `AuthController` empty | Credentials validation, blocked/suspended checks, token issuance, throttle, endpoint |
| F-007 | Email signup with password policy | **Partial** | `users` table; `RegisterRequest` empty | Validation (BR-022), neutral response (BR-021), verification code issue, endpoint |
| F-008 | Email verification | **Partial** | `email_verification_codes` (hash, expiry, attempts, verified_at); `EmailVerificationService` stub; middleware `EnsureEmailIsVerified` is pass-through | Verify/resend logic, middleware enforcement, endpoint |
| F-009 | Account-ready & optional enrichment | **Not Implemented** | `profile_completed_at` field | Readiness response and gating endpoint |
| F-010 | Profile completion | **Partial** | `users` name/email/phone fields + `profile_completed_at`; `CompleteProfileRequest` empty; profile controller empty | Validation, completeness rule, update endpoint/resource |
| F-011 | Forgot password (request) | **Partial** | `password_reset_tokens` table; `PasswordResetController` empty | Neutral request handling, token/mail, endpoint |
| F-012 | Reset password | **Partial** | `password_reset_tokens` table | Token validation, policy, session revocation, endpoint |
| F-013 | Session & identity guard | **Partial** | Sanctum; `personal_access_tokens.membership_id`; `TenantResolver`; `EnsureAccountIsActive`; `EnsureAdminAccess`; policy layer | Auth routes/middleware group, logout/logout-everywhere, device binding (`user_devices` table unused), session revocation on suspension (partially in services), BR-027/030 |

### 5.2 Discovery (F-014–F-021)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-014 | Home feed | **Not Implemented** | — | Feed aggregation endpoint (offers, destinations, recent searches) |
| F-015 | Global search | **Not Implemented** | — | Searchable entities and endpoint |
| F-016 | City & airport picker | **Not Implemented** | No airports/cities tables; no search history table | Reference data + recent/popular queries |
| F-017 | Date range & price calendar | **Not Implemented** | — | Calendar/price endpoint |
| F-018 | Passengers & cabin selector | **Not Implemented** | Passenger data stored only inside bookings | Search-parameter validation (BR-032/034/035) |
| F-019 | Offers list | **Partial** | `coupons` table (code, type, value, expiry, max uses, service scope, active) | Read endpoint, applicability rules; `minimum_spend` column missing (BR-015) |
| F-020 | Featured destinations | **Not Implemented** | No destinations table | Data source + endpoint |
| F-021 | Notifications inbox | **Partial** | Laravel `notifications` table (`read_at`); no Notification classes or endpoints | Generation, list, read/read-all, preferences |

### 5.3 Flights (F-022–F-030)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-022 | Flight search (one-way/round/multi-city) | **Not Implemented** | Booking schema only; no search/provider/cache. Docs conflict: catalogue offers multi-city, BR-036 says it must not be partially implemented, Q-08 defers it | **Clarify multi-city**; then supplier integration, search criteria validation, normalized result DTOs |
| F-023 | Flight filters | **Not Implemented** | — | Filter contract and server-side filtering |
| F-024 | Flight results & sorting | **Not Implemented** | — | Deterministic sorting (BR-037), result cards with whole-party pricing (BR-004) |
| F-025 | Return-leg selection | **Not Implemented** | `flight_segments.leg` supports outbound/return | Combined-itinerary validation and pricing |
| F-026 | Passenger details & validation | **Partial** | `travelers`, `flight_passengers` (snapshot, encrypted passport), `flight_bookings` | Validation rules (BR-032), duplicate-booking check (BR-046), endpoint |
| F-027 | Seat selection | **Needs Clarification** | — | BR-048 says pricing/entitlement/availability undefined; docs have only a fragment. Do not build until decided |
| F-028 | Checkout price revalidation | **Partial** | `checkout_sessions` (quote snapshot, quoted/revalidated totals, price change/accepted timestamps), `CheckoutSession` helpers | Revalidation service, idempotency use (BR-020), price-change acceptance flow, endpoint |
| F-029 | Payment, promo & checkout | **Partial** | `payments`, `payment_methods` (tokenized, one-default constraint), `coupons`, `coupon_redemptions` (one per booking), `bookings` money breakdown | Payment provider, capture/authorize, coupon validation/atomic use, fee breakdown, endpoint |
| F-030 | Flight booking confirmation | **Partial** | `bookings.reference` unique, statuses, commission/net fields; `status_histories` | Reference generator, confirmation payload, PNR/ticket storage flow, voucher/e-mail |

### 5.4 Visas (F-031–F-038)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-031 | Visa country catalogue | **Partial** | `visa_offerings` (country, type, localized name/description, price, expected days, validity, active, org nullable); policy | Public read endpoint, only-active filtering |
| F-032 | Visa types per country | **Partial** | `visa_offerings.visa_type` | Grouped read endpoint |
| F-033 | Visa type detail & fee display | **Partial** | Offering price + `visa_offering_documents` (mandatory flag, sort) | Detail endpoint with document requirements |
| F-034 | Visa document upload | **Partial** | `visa_application_documents` schema; `filesystems` config | Upload validation (BR-050), storage/scanning, endpoint |
| F-035 | Visa request submission | **Partial** | `visa_applications` + documents; policies | Mandatory-document gate (BR-051), price snapshot (BR-054), submission service/endpoint |
| F-036 | Visa document tracker | **Partial** | `status_histories`, application statuses | Read endpoint, document-level states |
| F-037 | Visa application lifecycle | **Partial** | Status columns on application; `VisaApplicationPolicy` | State machine, transition permissions (docs conflict in §9), history writer, rejection reason rules |
| F-038 | Reusable saved documents | **Partial** | `user_documents` (type, expiry, owner, traveler), `UserDocument::isExpired()` | Reuse validation (BR-053) and document CRUD endpoints |

### 5.5 eSIM (F-039–F-043)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-039 | eSIM country selection & device check | **Partial** | `esim_packages` (country, provider, cost, markup, sale price, staleness); policy | Country list, device compatibility check, endpoint |
| F-040 | eSIM package selection | **Partial** | `esim_packages` active/stale flags | Read endpoint and availability rules |
| F-041 | eSIM checkout & payment | **Partial** | `esim_orders` (booking, provider order, encrypted activation payload, status) | Checkout/payment integration, one-package-per-order rule (BR-056), endpoint |
| F-042 | eSIM QR issuance & activation | **Partial** | `esim_orders.activation_payload`, `provider_order_id`, statuses | Provider purchase, single-use token, QR/LPA generation, delivery |
| F-043 | eSIM usage & balance | **Needs Clarification** | — | Catalogue flags usage as partial and provider/usage API unresolved; decide scope before implementation |

### 5.6 Booking management (F-044–F-049)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-044 | My bookings list & tabs | **Partial** | `bookings` (service, status, dates, org, assigned membership) | Query endpoint with tabs/status definitions (BR-112) |
| F-045 | Booking detail hub | **Partial** | Booking + flights/hotel/tour/eSIM/visa sub-tables; `CheckoutSession` | Detail resource, authorization policy use, endpoint |
| F-046 | E-ticket / boarding pass | **Partial** | `flight_passengers.ticket_number`; `provider_reference` on booking | Ticket issuance after capture (BR-049), e-ticket rendering/delivery |
| F-047 | Reschedule flight | **Not Implemented** | No change/reschedule schema | Fare-difference rules (BR-044), provider revalidation, endpoint |
| F-048 | Add baggage | **Not Implemented** | No baggage schema | Add-on catalogue, limit rules (BR-045), payment, endpoint |
| F-049 | Cancel booking & refund | **Partial** | `refunds`, `payments`, `partner_ledger_entries`, booking status; `RefundPolicy` | Cancellation policy engine (BR-077), refund execution (BR-078/079), original-instrument enforcement (BR-019), endpoint |

### 5.7 Account, travellers and payments (F-050–F-060)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-050 | Account hub | **Partial** | `users` profile fields; `ProfileResource` empty | Aggregated account endpoint |
| F-051 | Saved travellers CRUD | **Partial** | `travelers` (soft deletes, encrypted passport, expiry) | CRUD service/validation/endpoints |
| F-052 | Passport OCR scan | **Not Implemented** | — | OCR provider integration, confirmation-only flow (BR-024) |
| F-053 | Edit profile & completeness | **Partial** | `profile_completed_at`; `CompleteProfileRequest` empty | Validation/completeness computation/endpoint |
| F-054 | Favourites | **Partial** | `favorites` (polymorphic, unique) | Toggle/list endpoints and policy |
| F-055 | Settings: language, currency, notifications, password | **Not Implemented** | Only `users` locale-agnostic fields; no preference storage | Decide storage (columns vs settings table), preference endpoints, password change |
| F-056 | Terms & privacy | **Partial** | `terms_acceptances` (versioned, unique per user/type/version) | Version catalogue, acceptance endpoint, re-acceptance gate (BR-025) |
| F-057 | Delete account | **Partial** | `account_deletion_requests` (grace, blocking reasons, one-open constraint), model scopes; config grace = 7 days | Request/cancel service, blocking checks (BR-029), anonymization job (BR-109), endpoint |
| F-058 | Customer service | **Not Implemented** | — | Support channel contract (e-mail/WhatsApp/tickets) undefined; docs only show a screen |
| F-059 | Payment methods | **Partial** | `payment_methods` (token only, brand/last4/expiry, one-default key), model hook | Provider tokenization, set-default atomic API, delete endpoint |
| F-060 | Payment history | **Partial** | `payments`/`refunds` tables | Read endpoint and receipt totals |

### 5.8 Hotels (F-061–F-066)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-061 | Hotel search & results | **Partial** | `hotels` (city, lat/lng, star, status, rating), `room_types`, `room_rates` (date, price, units, stop-sell); `Hotel` bounds scope | Search service, availability-per-stay rule (BR-061/066), results API |
| F-062 | Hotel filters | **Not Implemented** | — | Filter contract and query implementation |
| F-063 | Hotels on map | **Partial** | `Hotel::scopeWithinBounds` and lat/lng columns | Viewport endpoint, bookable-only pins (BR-065) |
| F-064 | Hotel detail & room selection | **Partial** | Full hotel/room/rate schema, cancellation policy, occupancy limits | Detail resource, nightly quote (BR-062/063), endpoint |
| F-065 | Hotel booking & payment | **Partial** | `hotel_bookings` (dates, nights, guests, rooms, payment timing, guest name) | Inventory decrement/locking, pay-now discount (BR-067), checkout/refund rules |
| F-066 | Hotel confirmation & voucher | **Partial** | Booking reference + hotel booking snapshot | Voucher generation/e-mail and partner delivery (BR-068) |

### 5.9 Tours (F-067–F-071)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-067 | Tours catalogue | **Partial** | `tours` (localized content, price, child price, status, rating), destination index | Public catalogue endpoint restricted to published (BR-090) |
| F-068 | Tour programme detail | **Partial** | `tour_itinerary_days`, `tour_pickup_points`, `tour_slots` | Detail resource, slot availability endpoint |
| F-069 | Tour booking & payment | **Partial** | `tour_bookings`, `tour_slots` (capacity/booked_count), pickup point | Atomic seat reservation, capacity/group limits (BR-070/071), pickup requirement (BR-072) |
| F-070 | Tour confirmation & voucher | **Partial** | Booking reference + snapshot | Voucher generation/delivery |
| F-071 | Tour cancellation & refund | **Needs Clarification** | — | Follow each tour company's policy; no universal 24-hour rule. Terms/refund basis must be explicit before booking; workflow remains unresolved (Q-24) |
### 5.10 Partner onboarding and portal access (F-072–F-077)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-072 | Partner portal login with account-type chooser | **Partial** | Organizations/memberships, `TenantResolver`, policies, `EnsureAdminAccess` | Partner auth endpoints, account-type validation (BR-080), portal guards |
| F-073 | Partner forgot password | **Not Implemented** | `password_reset_tokens` only; `PasswordResetController` empty | Request/reset service and endpoints |
| F-074 | Company join request | **Partial** | `organization_applications` + documents; `OrganizationApplicationService::submit()` validates type/terms/e-mail and duplicate pending request | HTTP endpoint, document upload, rate limiting, acknowledgment e-mail |
| F-075 | Hotel join request | **Partial** | Same as F-074 (`type=hotel`) | Same gaps |
| F-076 | Join request acknowledgment | **Partial** | Application row created with submitted status | Human reference (docs promise a reference), e-mail, status-tracking endpoint |
| F-077 | Partner application review & activation | **Partial** | `approve()` (creates org, token hash + 7-day expiry), `reject(reason)`, `activate(token)` (creates user/owner membership), platform permission check | Document verification, commission/services capture, notification e-mail, HTTP endpoints, activation page flow |

### 5.11 Platform administration (F-078–F-090)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-078 | Platform overview & KPIs | **Not Implemented** | — | KPI definitions (BR-112) and aggregation endpoint |
| F-079 | Admin notifications | **Not Implemented** | `notifications` table only | Event generation, bell/list API |
| F-080 | All-bookings oversight | **Partial** | `platform.bookings.view/export` seeded; `BookingPolicy` | Query/filter/export endpoints |
| F-081 | Partner directory | **Partial** | `organizations`, `OrganizationPolicy`, platform partner permissions | Paginated directory endpoint with metrics |
| F-082 | Add partner | **Partial** | `OrganizationService::create()` (type validation, commission rate, slug) | Owner account/invitation, services/documents capture, endpoint |
| F-083 | Partner detail & suspension | **Partial** | `OrganizationService::suspend()/reinstate()` with platform permission; `OrganizationPolicy` | Detail payload (sales/tours/visa counts/documents/team), automatic delisting (BR-094), endpoint |
| F-084 | Visa catalogue management | **Partial** | `visa_offerings`, `visa_offering_documents`, platform permissions | CRUD service/endpoints, document requirement editor |
| F-085 | eSIM catalogue & margin management | **Partial** | `esim_packages` (cost/markup/sale/margin fields), platform permissions | CRUD, provider sync, stale handling |
| F-086 | Account-deletion request queue | **Partial** | `account_deletion_requests`, platform permissions, model scopes | Queue endpoint, process/block/anonymize service (BR-108/109) |
| F-087 | Customer directory | **Partial** | `UserPolicy`, platform customers permissions | Directory query/export endpoint |
| F-088 | Customer detail & account actions | **Partial** | `users.status`, platform `customers.block/update`, `EnsureAccountIsActive` | Block/unblock service with session revocation, detail endpoint |
| F-089 | Finance & commissions | **Partial** | `partner_ledger_entries`, `payout_accounts`, `payout_requests`, platform finance permissions, policies | Commission computation, partner-due aggregation, settlement execution, endpoints |
| F-090 | Roles & permissions (platform) | **Partial** | Seeded platform roles/permissions, `RolePolicy`, `PermissionPolicy`, authorization service | Role CRUD/assignment service (stubs), permission sync/validation, endpoints |

### 5.12 Tourism-company portal (F-091–F-100)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-091 | Company overview & today queue | **Not Implemented** | — | Aggregation endpoint |
| F-092 | Company notifications | **Not Implemented** | `notifications` table only | Event generation and API |
| F-093 | Tour portfolio management | **Partial** | `TourPolicy`, tours schema, `tours.*` permissions | CRUD/list endpoints |
| F-094 | Tour editor & review submission | **Partial** | Tour content/itinerary/pickup/slot schema, statuses, rejection reason, `TourPolicy` | Validation (BR-091), submit/review workflow (BR-092/093), endpoints |
| F-095 | Company bookings | **Partial** | `BookingPolicy`, `bookings.view/update/cancel/export`, `assigned_membership_id` column | Scoped list/detail/actions; assignment rules (BR-087/115) |
| F-096 | Visa request review workspace | **Partial** | `VisaApplicationPolicy`, application/document schema | Review actions (document accept/reject), notes, status transitions, assignment |
| F-097 | Team management | **Partial** | `MembershipService`: invite, accept, assign/revoke role, grant/revoke permission, update scopes, suspend/reinstate/remove, transfer; role templates; owner protection | HTTP endpoints, invitation e-mail, resend semantics |
| F-098 | Custom role editor | **Partial** | `RolePolicy`, `canGrant()`, delegable permission list, permission families | Role CRUD service, permission selection validation, role deletion rules, endpoints |
| F-099 | Financial reports & settlements | **Partial** | `partner_ledger_entries`, payout tables, finance permissions, policies. **No `settlements` tables** | Period aggregation, settlement states (BR-099), report/export endpoints |
| F-100 | Company settings | **Partial** | `OrganizationService::update()`, org documents table/policy, org profile permissions | Settings API, document verification, publishing rules |

### 5.13 Hotel portal (F-101–F-107)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-101 | Hotel overview | **Not Implemented** | — | Aggregation endpoint |
| F-102 | Hotel notifications | **Not Implemented** | `notifications` table only | Event generation and API |
| F-103 | Rooms & rates calendar | **Partial** | `room_types`, `room_rates` (price/units/stop-sell), `RoomRatePolicy`, `RoomTypePolicy`, hotel scope tables | Bulk calendar read/write service and endpoints (BR-106) |
| F-104 | Hotel bookings & check-in/out | **Partial** | `BookingPolicy` check-in/out abilities; `hotel_bookings`; booking status | Check-in/out service, allowed states, status history writer, endpoints |
| F-105 | Hotel employee management | **Partial** | `MembershipService` with hotel scope sync; `membership_hotel_scopes`; hotel team permissions | Endpoints, property-scope UI contract, role panel |
| F-106 | Hotel profile & publishing | **Partial** | `HotelPolicy` publish ability, hotel content schema, org documents | Publication gate (BR-101/110), document approval, profile update endpoint |
| F-107 | Hotel reports | **Not Implemented** | — | Report definitions and endpoints |

### 5.14 Restricted employee portal (F-108–F-110)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-108 | Assigned visa-request queue | **Partial** | `VisaApplicationPolicy`, `visa_requests.view`, `assigned_membership_id` column | Assignment filter at query level (BR-087/115) is **not enforced**; endpoint missing |
| F-109 | Read-only bookings | **Partial** | Permission keys separate view from update; `BookingPolicy` | Employee write rejection not implemented at HTTP/service layer; endpoints missing |
| F-110 | Employee notifications | **Not Implemented** | `notifications` table only | Generation and API |

### 5.15 Coupons and dynamic access control (F-111–F-112)

| ID | Requirement | Status | What exists | What is missing / next |
|---|---|---|---|---|
| F-111 | Coupon management | **Partial** | `coupons`, `coupon_redemptions` (one per booking, release timestamp), `CouponPolicy`, org coupon permissions | `minimum_spend` column missing; CRUD service, atomic redemption/usage counters, release-on-refund, endpoints |
| F-112 | Dynamic roles and permissions | **Partial** | Permission catalog and families, role templates, direct grants, versioned permission cache, `canGrant`, policies, 21 policy classes | Role CRUD, permission-change reconciliation/revocation semantics, owner template protection service, endpoints |

---

## 6. Authorization, tenancy and permissions audit

**Sources:** `AUTHORIZATION-ARCHITECTURE.md`, `ROLE-PERMISSION-SCOPE-MATRIX.md`, `USER-ROLES-AND-PERMISSIONS.md`, `BUSINESS-LOGIC.md` BR-084–BR-089, BR-115–BR-125.

### 6.1 Enforcement layers (L1–L5) versus implementation

The architecture defines seven enforcement layers and an L1–L5 scope model. Current coverage:

| Layer (docs) | Requirement | Status | Evidence / gap |
|---|---|---|---|
| Route authentication | Sanctum guard, active account, membership binding | **Partial** | Guard and middleware exist; only one route uses them. No auth routes/portal guards |
| Account state | Blocked/suspended user or org cannot authenticate/transact | **Partial** | `EnsureAccountIsActive` implemented; `AuthorizationService::activeMembership` checks user+org active; not wired to APIs |
| Permission check | `domain.action` permission evaluated per request | **Partial** | `AuthorizationService::allows()` and 21 policies implemented; **no controller invokes authorization** (controllers empty) |
| Scope narrowing | Service scope and property scope narrow, never widen | **Partial/defective** | `withinServiceScope()` treats **empty scopes as unrestricted**; `Membership::isRestrictedToHotel()` returns true when no hotel scope exists. This is the opposite of BR-121/MT-11 |
| Resource ownership | Cross-tenant access denied via `organization_id` | **Partial** | `ownsResource()` implemented, denies null-org resources; policies use it; no endpoints |
| Delegation boundary | Cannot grant what you do not hold | **Partial** | `canGrant()` + non-delegable list + role assignment assertions exist; no role/role-permission CRUD service |
| Audit/activity logging | Intentionally excluded | **Out of scope (G0-DEC-01)** | No audit table/service is expected. `status_histories` is limited to business entity status transitions. |

### 6.2 Protected effects (PE-01–PE-12)

| Effect | Status | Notes |
|---|---|---|
| PE-01 View bookings | **Partial** | Permission keys + `BookingPolicy`; no query or assigned-only enforcement |
| PE-02 Mutate bookings | **Partial** | Update/cancel/refund keys, policy; no endpoint/state machine |
| PE-03 View visa requests | **Partial** | Keys + policy; company review key and platform review/decide keys exist; assignment filter missing |
| PE-04 Move visa status | **Partial/Conflict** | `platform.visa_requests.decide` exists; documented `platform.visa_requests.update_status` is missing; DEC-16 vs Q-46 conflict |
| PE-05 Manage tours/slots | **Partial** | Keys + `TourPolicy`; no service/CRUD |
| PE-06 Manage rooms/rates/stop-sell | **Partial** | Keys + policies; no service |
| PE-07 Manage employees/roles/grants | **Partial** | Service layer implemented; no HTTP; resend semantics differ |
| PE-08 Manage organization profile/documents | **Partial** | `OrganizationService::update`, document policy; no API/document verification |
| PE-09 View/execute settlements | **Partial** | Keys + ledger/payout tables; no settlements schema/execution |
| PE-10 Platform partner lifecycle | **Partial** | Organization services + policies; no endpoints |
| PE-11 Platform customer actions | **Partial** | Keys + `UserPolicy`; no service/endpoints |
| PE-12 Platform catalogue/settings | **Partial** | Catalogue tables + most keys; `platform.settings.manage` missing; no CRUD |

### 6.3 Permission catalogue differences (docs vs seeder)

At the time of this audit the docs stated **83 keys (36 platform + 47 organization)**, but the matrix tables actually contained **84 distinct keys (37 platform + 47 organization)** — the 83/36/47 numbers were themselves inconsistent. `RolesAndPermissionsSeeder` contained **80 keys (36 platform + 44 organization)**. These figures are the audit's evidence, not the current catalog: the historical 85-key proposal has since been withdrawn as an implementation target. The active service-aware candidate is `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md` (37 platform + 48 organization candidate keys, with service/resource metadata still pending approval).

**Documented keys missing from implementation (8):**

| Key | Documented purpose | Consequence |
|---|---|---|
| `platform.audit.view` | View the audit trail | No audit tables/service exist; architecture §11 removes audit (§9 C-01) |
| `platform.settings.manage` | Platform settings, global payment rules, commission defaults | No settings API/authority |
| `platform.visa_requests.update_status` | Move visa application through DEC-07 statuses | Replaced by `decide`; docs conflict with DEC-16 (§9 C-04) |
| `properties.view` | View hotel properties | Hotel scope model uses hotels, not properties |
| `properties.create` | Create a property | No property entity/permission |
| `properties.update` | Update a property | No property entity/permission |
| `properties.publish` | Publish/unpublish a property | Replaced by `org.profile.publish` |
| `settlements.accounts.manage` | Manage payout accounts (owner-only, non-delegable) | Payout-account authority cannot be expressed |

**Implementation-only keys (4):** `platform.visa_requests.assign`, `platform.visa_requests.decide`, `org.profile.publish`, `visa_requests.review`.
**Additional note:** `visa_requests.review` (organization family) is not in the matrix at all; the matrix only has `platform.visa_requests.review`.

### 6.4 Roles and templates

| Requirement | Implementation | Status |
|---|---|---|
| Platform roles R-03…R-06, R-18 | `platform_super_admin`, `platform_finance`, `platform_content`, `platform_support`, `platform_visa_officer` | **Implemented (seed)** |
| Owner templates R-07/R-12/R-19 | `company_owner`, `hotel_owner`, `service_provider_owner`, protected in config, all org permissions | **Implemented (seed/service)** |
| Employee templates | `bookings_employee`, `visa_employee`, `accountant`, `content_manager`, `restricted_employee`, `reception`, `hotel_bookings_manager`, `hotel_accountant` | **Partial** |
| Documented `visa_offerings_manager` | Missing | **Not Implemented** |
| Documented `company_service_manager` | Missing | **Not Implemented** |
| Exactly one active owner | DB unique `active_owner_org_id` + model hook + transfer service | **Implemented (DB/service)** |
| Role creation/deletion per tenant | No role service (stub class) | **Not Implemented** |
| Permission changes effective next request | `permissions_version` + cache invalidation in `AuthorizationService` | **Partial** (no HTTP middleware binding) |

### 6.5 Specific implementation defects / doc violations found

These are code-vs-documentation problems, listed for approval rather than fixed during the audit:

1. **D-01 — Empty scope means "all", docs say "none".** `AuthorizationService::withinServiceScope()` returns `true` when `membership->services() === []`; `Membership::isRestrictedToHotel()` returns `true` for any hotel when the scope list is empty. BR-121 and MT-11 require a non-owner with **no** service/property scope to see **nothing**.
2. **D-02 — Assigned-only visibility not enforced.** BR-087/BR-115 require employee queries filtered by `assigned_membership_id`; no query scope, policy ability or service filter implements this.
3. **D-03 — Tenant context accepted from a header.** `TenantResolver` falls back to `X-Organization-Id` after the token membership. BR-120/MT-02 state tenant context is never derived from a request header. Token-first mitigates forgery, but the rule is still violated.
4. **D-04 — Cross-organization transfer mutates the membership.** `MembershipService::transfer()` rewrites `organization_id` on the existing row, keeps `membership_service_scopes`, and only deletes roles/hotel scopes. ARCHITECTURE §8.4 requires remove + create, and BR-125 says scopes never carry across organizations.
5. **D-05 — Invitation resend semantics differ.** Docs say resending invalidates the previous token; `invite()` throws on an existing pending invitation instead of reissuing.
6. **D-06 — "Delete tenant" action cannot be enforced.** `config/authorization.php` lists `organization.delete`, but `OrganizationService` exposes `close()` only and no permission/ability maps to deletion; docs BR-086 requires owner-only deletion.
7. **D-07 — Roles/permission revocation leaves grants.** `revokeRole()` detaches the role but does not reconcile direct permissions or role-derived grants; docs require a grantor to hold what is granted and access to disappear on next request (BR-088/122/124).
8. **D-08 — Policy layer unused.** Policies exist but no controller/service calls `authorize()`; the only enforcement path is direct service assertions. Until APIs exist, BR-089's "API rejects restricted action" is untestable and unimplemented.

### 6.6 Implemented service surface (the only working domain logic)

| Service | Capabilities actually implemented | Gaps |
|---|---|---|
| `AuthorizationService` | Active membership resolution (user+org+membership active), team switching, effective permissions with family check, cache/version invalidation, service/hotel scope checks, ownership check, `canGrant`, delegable permission list, platform staff detection | Scope semantics (D-01), assignment rule (D-02), no HTTP integration |
| `TenantResolver` | Token membership → validated header → single active membership; memoization + forget | Header violates docs (D-03); no middleware applies it |
| `RoleTemplateService` | Copies a template role into a team; owner template mapping | No role CRUD/custom role permissions |
| `OrganizationService` | Create (type validation, commission, unique slug), update, suspend, reinstate, close (cuts tenant access), transfer ownership, attach/detach role | No owner invite/e-mail, no services/documents, no delete, no HTTP |
| `OrganizationApplicationService` | Submit (type/terms/e-mail/duplicate guard), approve (org + activation token), reject (reason), activate (owner user/membership) | No documents/commission/services, no e-mail, no tracking reference, no endpoints |
| `MembershipService` | Invite/accept, assign/revoke role, grant/revoke permission, update scopes, suspend/reinstate/remove, transfer, owner protections, token revocation (`cutAccess`) | Defects D-04/D-05/D-07; no HTTP/mail/scheduler |

---

## 7. Workflow audit (WF-01–WF-15)

**Source:** `docs/phase-02/WORKFLOWS.md`.

| WF | Workflow | Status | Implemented steps | Missing steps |
|---|---|---|---|---|
| WF-01 | Registration, verification & first session | **Partial** | Tables/models for users, OTP, social, devices | All endpoints, OTP service, neutral responses, password policy, token issuance |
| WF-02 | Guest browsing → authenticated purchase | **Not Implemented** | — | Guest read policy, auth gate on commerce, cart/checkout continuity |
| WF-03 | Flight booking (search → ticket) | **Not Implemented** | Booking/passenger schema only | Search, quote, revalidation, payment, ticket issuance |
| WF-04 | Flight post-booking (e-ticket, reschedule, baggage, cancellation) | **Not Implemented** | Booking/refund schema | E-ticket, reschedule, baggage, cancellation/refund rules |
| WF-05 | Visa application | **Partial** | Offering/application/document tables | Catalogue, upload, submission gate, tracker |
| WF-06 | Visa review & processing (company → platform → outcome) | **Partial** | Status fields, policy, platform permissions | Review actions, assignment, transition rules, reason handling, notifications |
| WF-07 | eSIM purchase & activation | **Partial** | Package/order tables | Country/package APIs, payment, provider purchase, QR/token delivery |
| WF-08 | Hotel booking | **Partial** | Hotel/room/rate/booking schema | Search, availability locking, payment, voucher, partner delivery |
| WF-09 | Tour booking | **Partial** | Tour/slot/booking schema | Catalogue, capacity reservation, payment, voucher |
| WF-10 | Partner onboarding, activation & team setup | **Partial** | Application submit/approve/activate; team invite/roles/scopes; org create | Documents, commission/services, e-mails, HTTP, role templates parity |
| WF-11 | Catalogue & pricing administration → mobile display | **Partial** | Catalogue schemas | Admin CRUD, publish rules, mobile read APIs |
| WF-12 | Tour authoring, review & publication | **Partial** | Tour schema + statuses; policy | Validation, submit/review/republish service and endpoints |
| WF-13 | Hotel rate, stay & settlement operations | **Partial** | Rates/stop-sell/bookings; ledger/payout tables | Calendar service, check-in/out, settlement computation/execution |
| WF-14 | Support, refund request & account deletion | **Partial** | Refund/deletion schema, models | Support channel, refund workflow, deletion request/cancel/anonymize |
| WF-15 | Role, permission & employee administration | **Partial** | Membership/role/grant/scope services; policy layer | HTTP APIs, role CRUD, resend semantics, assignment filters |
---

## 8. Business rule audit (BR-001–BR-125)

**Source:** `docs/phase-02/BUSINESS-LOGIC.md`.
Status reflects end-to-end enforcement. "Schema" evidence means columns/tables exist but no service enforces the rule.

| ID | Rule (abridged) | Status | Evidence / gap |
|---|---|---|---|
| BR-001 | USD, integer minor units, 2 decimals everywhere | **Partial** | `*_cents` columns + `config/authorization.php` currency; no money service/formatting |
| BR-002 | No multi-currency/FX | **Implemented** | Single-currency schema and config; no FX code |
| BR-003 | Flight total = fare + taxes + fees | **Not Implemented** | No pricing engine |
| BR-004 | Result price is whole-party total | **Not Implemented** | No results/search |
| BR-005 | Fees itemised, never first shown at payment | **Not Implemented** | Booking fee columns exist; no quote flow |
| BR-006 | No fare hold; revalidate before payment | **Partial** | `checkout_sessions` fields; no revalidation service |
| BR-007 | Visa fee administered per offering | **Partial** | `visa_offerings.price_cents`; no admin read/write API or snapshot |
| BR-008 | eSIM price = cost + markup; stored sale price is charged | **Partial** | `esim_packages.cost_cents/markup_bp/sale_price_cents`; no sync/pricing service |
| BR-009 | Hotel total = nightly rates + taxes/fees | **Partial** | Hotel/room/rate schema; no totals |
| BR-010 | Tour total = per-person price × participants (+ child) | **Partial** | Tour prices + participant columns; no totals |
| BR-011 | Partner net excludes commission; customer price includes it | **Partial** | Booking commission/net fields; no computation |
| BR-012 | Half-up rounding, no drift | **Not Implemented** | No money arithmetic |
| BR-013 | Priced-input change invalidates quote | **Partial** | `CheckoutSession::priceChanged()` only |
| BR-014 | One currency per booking | **Implemented** | Single `bookings.currency` column, no carts |
| BR-015 | One coupon/booking; server validation incl. minimum spend | **Partial** | Coupon + redemption tables; `minimum_spend` column missing; no validation service |
| BR-016 | Tokenized card data only | **Partial** | Token-only columns and hidden field; no provider |
| BR-017 | Exactly one default payment method | **Partial** | Unique `default_owner_key` + model hook; no atomic API |
| BR-018 | Enabled methods configuration-driven | **Not Implemented** | No settings/method config |
| BR-019 | Refund to original instrument only | **Partial** | Refund schema; no execution |
| BR-020 | Idempotent payment per checkout session | **Partial** | `checkout_sessions.idempotency_key`; no payment service |
| BR-021 | Neutral signup/password-reset responses | **Not Implemented** | No auth handlers |
| BR-022 | Server-side password policy | **Not Implemented** | `RegisterRequest` empty |
| BR-023 | Changed e-mail/phone must be re-verified | **Not Implemented** | No change flow |
| BR-024 | OCR never auto-saves; confirm required | **Not Implemented** | No OCR |
| BR-025 | Versioned terms acceptance | **Partial** | `terms_acceptances` unique per version; no gate |
| BR-026 | OTP single-use/expiry/attempts/resend invalidation | **Partial** | OTP tables have hash/expiry/attempts/verified_at; no service |
| BR-027 | One principal + device session; revoke all sessions | **Partial** | Sanctum + `membership_id`; token deletion in membership services; no logout-all API |
| BR-028 | Guest read-only for commerce | **Not Implemented** | No policies on routes |
| BR-029 | Delete = request → 7-day grace → anonymize; blocked when active obligations | **Partial** | Table/model/config; no workflow |
| BR-030 | Blocked/suspended cannot auth or transact | **Partial** | Status fields, `EnsureAccountIsActive`, token revocation on membership cut; not wired |
| BR-031 | Date validity | **Not Implemented** | No search/booking validation |
| BR-032 | Passenger mix rules | **Partial** | Passenger tables; no rules |
| BR-033 | Origin ≠ destination | **Not Implemented** | — |
| BR-034 | Cabin availability per leg | **Not Implemented** | — |
| BR-035 | Round trip same mix/cabin, reprice on change | **Not Implemented** | — |
| BR-036 | Multi-city must not be partially implemented | **Implemented** | Excluded from booking schema (`trip_type` one_way/round_trip) — but F-022 promises it (§9 C-13) |
| BR-037 | Deterministic sorting | **Not Implemented** | — |
| BR-038 | Refundability attributes displayed | **Not Implemented** | No fare attributes |
| BR-039 | AND filters with explicit empty state | **Not Implemented** | — |
| BR-040 | Date-strip fares per passenger/cabin | **Not Implemented** | — |
| BR-041 | No inventory hold; revalidate at capture | **Partial** | Checkout schema only |
| BR-042 | Revalidation failure = no charge, return to results | **Partial** | Status fields only |
| BR-043 | Changed fare must be explicitly accepted | **Partial** | `price_change_accepted_at`; no flow |
| BR-044 | Reschedule rules | **Not Implemented** | No schema/service |
| BR-045 | Baggage limits/prices | **Not Implemented** | No schema |
| BR-046 | No overlapping duplicate flight bookings | **Not Implemented** | No check |
| BR-047 | Post-ticketing name correction policy | **Needs Clarification** | BR says unresolved |
| BR-048 | Seat pricing/entitlement undefined | **Needs Clarification** | Explicitly undefined |
| BR-049 | Ticket issued only after provider capture; PNR external | **Not Implemented** | Fields exist; no issuance |
| BR-050 | PDF/JPG/PNG, 10 MB limit | **Not Implemented** | No upload endpoint |
| BR-051 | Visa submit gated by mandatory documents | **Not Implemented** | No submission service |
| BR-052 | Processing duration configured per offering | **Partial** | `expected_days_min/max`; no display/API |
| BR-053 | Reuse saved doc only if same applicant/type/unexpired | **Partial** | `UserDocument::isExpired()`; no reuse rule |
| BR-054 | Visa fee snapshotted at submission | **Partial** | Application price fields; no snapshot logic |
| BR-055 | Visa price/eligibility independent of nationality | **Implemented** | No nationality-based pricing fields or logic |
| BR-056 | One eSIM package per order | **Implemented** | `esim_orders.booking_id` unique; second package requires second order |
| BR-057 | eSIM checkout price stable/tax-inclusive | **Partial** | Stored sale price; no checkout |
| BR-058 | Single-use activation token bound to order, QR=LPA | **Partial** | `activation_payload`; no generation/binding |
| BR-059 | Activated eSIM non-refundable | **Not Implemented** | No refund policy logic |
| BR-060 | Hotel search inputs + lat/lng | **Partial** | Hotel lat/lng and fields; no search service |
| BR-061 | Availability per room type/date for full stay | **Partial** | `room_rates.available_units`; no availability engine |
| BR-062 | Hotel total consistent across screens | **Partial** | Price schema; no quote |
| BR-063 | Occupancy limits enforced | **Partial** | `max_adults/max_children/max_occupancy`; no enforcement |
| BR-064 | Cancellation terms per rate and matching badge | **Partial** | Rate policy fields; no policy engine |
| BR-065 | Map query by viewport, bookable only | **Partial** | `Hotel::scopeWithinBounds`; no endpoint |
| BR-066 | Room bookable only if full stay available and not stop-sold | **Partial** | Availability/stop-sell columns; no check |
| BR-067 | Pay-now vs pay-at-hotel mutually exclusive; 5% on subtotal | **Partial** | `pay_now_discount_bp`, `payment_timing`; no calculation |
| BR-068 | Hotel confirmation delivered to portal + voucher e-mail | **Partial** | Booking ref; no delivery |
| BR-069 | Tour lifecycle states, only published saleable | **Partial** | Tour status/rejection fields; no lifecycle service |
| BR-070 | Per-slot capacity, never shared counter | **Partial** | `tour_slots.capacity/booked_count`; no enforcement |
| BR-071 | Participant bounds vs seats and group max | **Partial** | Columns; no validation |
| BR-072 | Pickup required when offered | **Partial** | Pickup tables; no rule |
| BR-073 | Tour cancellation follows the applicable tour company's policy; no universal 24-hour rule | **Decision-backed** | Policy and refund basis must be explicit before booking; workflow remains open (Q-24) |
| BR-074 | Rating = approved reviews only | **Partial** | Rating columns; no reviews table/moderation |
| BR-075 | Slot offered only if future, seats left, tour published | **Partial** | Columns; no rule |
| BR-076 | One canonical booking state machine | **Partial** | Status constants; no transitions/service |
| BR-077 | Cancellation eligibility per service policy | **Not Implemented** | No policy engine |
| BR-078 | Refund = captured − gateway retained, floored at zero | **Not Implemented** | Refund schema only |
| BR-079 | Refund window and status only after provider confirmation | **Not Implemented** | Refund schema only |
| BR-080 | Portal account type must match actual role | **Partial** | Membership/org type data; no portal auth |
| BR-081 | Join request required fields incl. 3 legal docs | **Partial** | Application fields; documents table exists but submit ignores uploads/legal docs |
| BR-082 | 2-business-day acknowledgment is not approval | **Not Implemented** | No acknowledgment/e-mail |
| BR-083 | Accepting creates tenant + owner + activation link; login before activation denied | **Partial** | Approve/activate services; no e-mail, no owner login gate (no auth) |
| BR-084 | No granting beyond held permissions/scopes | **Partial** | `canGrant`, non-delegable list, service assertions; no role CRUD |
| BR-085 | Tenant data isolation | **Partial** | Policies + `ownsResource`; no endpoints |
| BR-086 | Only owner transfers ownership/deletes tenant | **Partial** | Ownership transfer service + config; no delete action/endpoint |
| BR-087 | Restricted employee sees only assigned records | **Not Implemented** | No assignment filter |
| BR-088 | Permission changes apply next request | **Partial** | Version/cache in `AuthorizationService`; no request binding |
| BR-089 | API rejects restricted actions, not just hidden UI | **Not Implemented** | No API |
| BR-090 | Only published tours visible | **Partial** | Status column; no public query |
| BR-091 | Tour submission mandatory content | **Not Implemented** | No validation |
| BR-092 | Platform review before publication | **Not Implemented** | No workflow |
| BR-093 | Rejected tour editable/resubmittable with reason | **Not Implemented** | Reason column only |
| BR-094 | Suspension delists tours/rooms/offers, keeps bookings | **Partial** | Org suspend service; no delisting |
| BR-095 | Visa cannot advance with rejected/missing mandatory docs | **Not Implemented** | No transition service |
| BR-096 | Per-partner commission 10%/15%, prospective changes | **Partial** | `organization.commission_rate_bp`; no history/effective dating |
| BR-097 | Commission = rate × net sale excluding taxes/refunds | **Not Implemented** | Fields only |
| BR-098 | Partner due = sales − commission − refunds; negative carries | **Not Implemented** | No aggregation |
| BR-099 | Settlement states pending→processing→transferred/failed | **Not Implemented** | No settlements table/service |
| BR-100 | Post-settlement refund reduces next settlement once | **Not Implemented** | No settlement linkage |
| BR-101 | Document re-verification on replacement/expiry; blocks payouts/publication | **Not Implemented** | Documents + status fields; no verification service |
| BR-102 | Append-only transaction log | **Partial** | `partner_ledger_entries` structure (entry key, actor); no service enforcement |
| BR-103 | KPIs and exports share one aggregation | **Not Implemented** | No reports |
| BR-104 | Notifications generated by domain events | **Not Implemented** | No events/listeners/jobs |
| BR-105 | Per-user read state; mark-all scoped | **Partial** | `notifications.read_at`; no API |
| BR-106 | Stop-sell blocks new bookings only | **Partial** | `is_stop_sell` column; no enforcement |
| BR-107 | General audit/activity records | **Out of scope (G0-DEC-01)** | Status transitions only in `status_histories`; no audit table/service. |
| BR-108 | Deletion requests enter a review queue | **Partial** | Table/model; no queue processor |
| BR-109 | Deletion removes personal data but retains financial records | **Not Implemented** | No anonymization service |
| BR-110 | Hotel publish requires approved docs/profile | **Not Implemented** | `HotelPolicy` publish ability only |
| BR-111 | General audit entries for admin/access-control mutations | **Out of scope (G0-DEC-01)** | No general audit entries are written. |
| BR-112 | One KPI definition across portals | **Not Implemented** | No KPI service |
| BR-113 | One customer reference across app/admin/partner; random | **Partial** | Unique `reference` column; no generator |
| BR-114 | Partner settlement equals admin partner-due to the cent | **Not Implemented** | No settlement computation |
| BR-115 | Employee sees only assigned visa requests/bookings | **Not Implemented** | No query-level filter |
| BR-116 | Employee has no booking writes | **Partial** | Permission split; no API/service rejection |
| BR-117 | Locked modules inaccessible server-side | **Partial** | Keys exist; no server module gate |
| BR-118 | Exports apply active filters/definitions | **Not Implemented** | No exports |
| BR-119 | Separate platform/organization permission families | **Partial** | Prefix checks in `AuthorizationService`; no route enforcement |
| BR-120 | Tenant-owned rows carry non-null org; context never from header | **Partial** | Org ids on tenant tables; payments/refunds/eSIM derive via booking; header fallback violates rule (D-03) |
| BR-121 | Scope only narrows; no scope = no service/property access | **Not Implemented** | Implementation does the opposite (D-01) |
| BR-122 | Grant only held permissions/scopes; families never mix | **Partial** | `canGrant`/non-delegable; no role service |
| BR-123 | Exactly one active owner; atomic transfer; last owner protected | **Partial** | Unique constraint + service; no endpoint/audit |
| BR-124 | Changes effective next request; no audit record | **Partial** | `permissions_version` supports invalidation; no general audit record is required under G0-DEC-01. |
| BR-125 | Deactivation revokes sessions; roles/scopes never carry over | **Partial** | Token deletion implemented; transfer carries service scopes (D-04) |

### 8.1 Business rule totals

| Status | Count |
|---|---:|
| Implemented | 5 |
| Partial | 65 |
| Not Implemented | 49 |
| Needs Clarification | 3 |
| **Total** | **125** |

---

## 9. Documented conflicts and ambiguities (not silently resolved)

These must be decided before the related code is built. Nothing here was changed during the audit. The original findings below are preserved unchanged; §9.1 records what has since been decided and where.

| ID | Conflict | Sources | Consequence / decision needed |
|---|---|---|---|
| C-01 | **Audit trail required vs permanently removed.** BR-107/BR-111/BR-124, F-112, PE and matrix "audit" columns require an audit log with actor/before/after; `AUTHORIZATION-ARCHITECTURE.md` §11 says audit logs are permanently removed and the UI shows none. Implementation has no audit and seeds no `platform.audit.view`. | BUSINESS-LOGIC, AUTHORIZATION-ARCHITECTURE, ROLE-PERMISSION-SCOPE-MATRIX, FEATURES-CATALOG | Decide whether audit is in scope. This affects schema (audit table), permissions (83→84 keys), F-112 and every admin mutation |
| C-02 | **Permission counts.** Docs prose says 83 keys (36 platform + 47 org); the matrix's matrix tables contain 84 (37 platform + 47 org); its own totals row says 78 (33 + 45); implementation has 80 (36 + 44). | AUTHORIZATION-ARCHITECTURE, ROLE-PERMISSION-SCOPE-MATRIX | Pick one catalog as source of truth and reconcile docs + seeder |
| C-03 | **Property permissions vs profile permissions.** Docs define `properties.view/create/update/publish` for hotels; implementation instead has `org.profile.publish` and models hotels directly under a hotel organization. | ROLE-PERMISSION-SCOPE-MATRIX, DEC-14, implementation | Decide whether a "property" entity is required or hotels are properties |
| C-04 | **Visa status permission.** Matrix defines `platform.visa_requests.update_status`; ARCHITECTURE and DEC-16 say the company key was removed and platform staff "review/decide"; Q-46 still says company employees may update status with `visa_requests.update_status`. Implementation has `platform.visa_requests.review`, `decide`, `assign` and org `visa_requests.review`, no `update_status`. | MATRIX, ARCHITECTURE, OPEN-QUESTIONS (Q-46), DEC-16 | Decide who may transition visa states and with which keys |
| C-05 | **Role naming/count.** Docs alternate `org_owner` and `company_owner`; role list has R-01–R-17 in USER-ROLES but R-18/R-19 elsewhere (19 roles); implementation uses `company_owner`, `hotel_owner`, `service_provider_owner`. | USER-ROLES-AND-PERMISSIONS, MATRIX | Canonical role names/count |
| C-06 | **`bookings.view` organization scope.** Matrix lists organization type "All"; implementation's `BookingPolicy` limits company/hotel contexts. | MATRIX, app/Policies/BookingPolicy | Decide whether service providers see bookings |
| C-07 | **Tenant context.** BR-120 and MT-02 say tenant context is never derived from a request header; `TenantResolver` accepts `X-Organization-Id` (validated, token first). | BUSINESS-LOGIC, AUTHORIZATION-ARCHITECTURE, implementation | Remove header fallback or amend docs |
| C-08 | **Empty scope semantics.** BR-121/MT-11: a non-owner with no service/property scope sees nothing. Implementation: empty service/hotel scope means **all**. | BUSINESS-LOGIC, AUTHORIZATION-ARCHITECTURE, implementation | Security decision; implementation currently contradicts docs |
| C-09 | **Membership transfer.** Architecture §8.4: memberships are never moved (remove + create). Implementation mutates `organization_id` and keeps service scopes. | ARCHITECTURE, implementation | Fix implementation or amend architecture; BR-125 forbids carrying scopes |
| C-10 | **Invitation resend.** Docs: resend invalidates previous token. Implementation: duplicate pending invitation is rejected. | AUTHORIZATION-ARCHITECTURE §8.1, implementation | Decide resend UX/behaviour |
| C-11 | **Properties per hotel org.** Q-49 says one property per hotel organization at launch; DEC-14 supports multiple properties/hotels; implementation supports many hotels per org. | OPEN-QUESTIONS Q-49, DB-DECISION-BRIEF DEC-14, implementation | Confirm multi-property launch scope |
| C-12 | **Global templates.** Q-50 warns against shared NULL-organization owner templates until validated; implementation seeds owner templates with NULL team and copies them on activation. | OPEN-QUESTIONS Q-50, seeder/service | Confirm copy-on-create approach is accepted |
| C-13 | **Multi-city flights.** F-022 offers multi-city; BR-036 says it must not be partially implemented; Q-08 defers it; implementation excludes it. | FEATURES-CATALOG, BUSINESS-LOGIC, OPEN-QUESTIONS | Decide whether to remove the option or implement fully |
| C-14 | **Unresolved features.** F-027 seat selection, F-043 eSIM usage, F-071 tour cancellation are explicitly unresolved/partial in the catalogue; BR-047/048/073 also open. | FEATURES-CATALOG, BUSINESS-LOGIC | Keep out of the next build until answered |
| C-15 | **Phantom feature references.** Matrix references F-113/F-115, but the catalogue ends at F-112. | MATRIX vs FEATURES-CATALOG | Correct references or add missing features |
| C-16 | **Settlements schema.** DEC-10/BR-099/BR-114 require settlement periods/states and partner-due reconciliation; no `settlements`/settlement-items tables exist, only payout requests and the ledger. | DB-DECISION-BRIEF, BUSINESS-LOGIC, implementation | Approve schema design before finance work |
| C-17 | **Settings authority.** F-112 requires platform settings/global payment rules; `platform.settings.manage` is in docs but missing from implementation; `platform_settings` table exists unused. | FEATURES-CATALOG, MATRIX, implementation | Add key + settings service |
| C-18 | **Documented 3 legal documents vs schema.** BR-081 requires tourism licence, commercial registration and tax card; `organization_documents` is a generic type table. | BUSINESS-LOGIC, migration | Confirm document type enum/requirements per partner type |

### 9.1 Resolution status (added 24 Nov 2026)

Each conflict above is resolved, recommended, or explicitly carried forward. Decisions live in `GATE-0-DECISION-REGISTER.md`; nothing here was silently reinterpreted.

| Conflict | Status | Resolution |
|---|---|---|
| C-01 Audit | **RESOLVED** | `G0-DEC-01` — audit and activity logs permanently excluded; `status_histories` limited to business status transitions |
| C-02 Permission counts | **RESOLVED (historical evidence); revised content awaiting approval** | `G0-DEC-02` + `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md` — former 85-key proposal withdrawn; current 37+48 candidate set needs service/resource eligibility approval |
| C-03 Properties vs profile | **RESOLVED** | `G0-DEC-03` — `hotels` is the property entity; `properties.*` is canonical; `org.profile.publish` removed |
| C-04 Visa authority | **RESOLVED** | `G0-DEC-04` — platform-only final decision; `update_status` removed; company `review` retained |
| C-05 Role names/count | **PARTIAL** | Canonical names settled (`company_owner`, `hotel_owner`, `service_provider_owner`); the 19-vs-16 role count is a documentation sub-item of `G0.2` |
| C-06 `bookings.view` scope | **SUPERSEDED** | Service-aware eligibility in `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md`; no blanket provider allow/deny; enforce organization-enabled service + membership/resource scope |
| C-07 Header tenancy | **OPEN — Phase A (A3)** | Not a Gate 0 contract; remove the `X-Organization-Id` fallback |
| C-08 Empty scope | **RESOLVED** | `G0-DEC-05` — empty scope grants nothing; fail closed |
| C-09 Membership transfer | **OPEN — Phase A (A4)** | Remove + create, clear scopes |
| C-10 Invitation resend | **OPEN — Phase A (A5)** | Rotation semantics |
| C-11 One property | **RESOLVED** | `G0-DEC-03` — a hotel organization owns many properties; Q-49 answered |
| C-12 Global templates | **OPEN** | Q-50 still open; recommend documenting copy-on-create as accepted |
| C-13 Multi-city | **RECOMMENDED** | `G0.7` — remove from v1 scope; the `trip_type` enum already excludes it |
| C-14 F-027/F-043/F-071 | **RECOMMENDED** | `G0.7` — defer F-027 and F-043 usage; implement F-071 eligibility only |
| C-15 Phantom features | **RESOLVED** | Documentation correction — F-113/F-115 references re-pointed in the reconciliation (§8 P-11, P-12) |
| C-16 Settlements schema | **RECOMMENDED** | `G0.8` — `settlement_periods` + `settlement_items` keyed to `partner_ledger_entries` |
| C-17 Settings authority | **RESOLVED** | `G0-DEC-02` — `platform.settings.manage` added; `platform_settings` table already exists |
| C-18 Legal document types | **OPEN — Phase H.3** | Requires a document-type enum decision before partner onboarding |

**Net effect on §6:** the eight documented-but-missing permission keys and four undocumented keys are itemised in `PERMISSION-CATALOG-RECONCILIATION.md` §6–7. Against the current seeder the catalog change is **add 6, remove 1, rename 1**; against the 88-key union of documented and code-defined keys it is **retain 9 single-source keys, remove 3**. Both baselines are stated separately in that document's §3 and must not be mixed.

---

## 10. Missing schema required by documented behaviour

These are not "nice to have"; documented requirements cannot be implemented without them.

| Missing data | Required by | Notes |
|---|---|---|
| `settlement_periods` + `settlement_items` | BR-098–BR-100, BR-114, F-099, DEC-10 | Payout requests exist but no settlement calculation/state. Design proposed in `GATE-0-DECISION-REGISTER.md` §5.4 (Gate 0B, awaiting approval) |
| `notification_deliveries` | BR-104/105, F-021 | Dedupe of notification creation is solved by a deterministic UUID; per-channel retry tracking is not. Proposed in the register §5.1 (Gate 0B) |
| `payment_webhooks` | BR-016–020 | Every gateway delivers at-least-once; replay cannot be diagnosed without a raw-event record. Proposed in the register §5.2 (Gate 0B) |
| `reviews` (approved moderation) | BR-074, F-061/F-067 | Hotels/tours only cache `rating_avg/count` |
| `airports`, `cities`, `destinations`, recent searches | F-015/F-016/F-020 | No reference data for pickers/search |
| `coupons.minimum_spend_cents` | BR-015, F-111 | Validation impossible today |
| Notification preferences + push device tokens | F-055, BR-104/105 | `user_devices` exists but no preference/token semantics verified |
| Flight reschedule/change records, baggage add-ons | F-047/F-048, BR-044/045 | No tables |
| Audit log | BR-107/111/124, F-112 | **Resolved — not required.** `G0-DEC-01` permanently excludes audit and activity logs; the three business rules are superseded. No table will be added |
| Commission rate history/effective dates | BR-096 | Only current rate on organization; prospective changes not representable |
| Terms document/version catalogue | BR-025, F-056 | Acceptance table exists; versions must come from somewhere |
| Support request model (if in scope) | F-058 | Docs show screen only; channel/contract undecided |

---

## 11. Implementation checklist (proposed order — requires approval)

No implementation below has been started. Gate 0 is recorded in `GATE-0-DECISION-REGISTER.md`: **Gate 0A (authorization)** blocks Phase A, **Gate 0B (business and integration)** blocks only dependent phases. Five architectural decisions are approved; DEC-11–DEC-19 delegation and owner-template decisions are also already approved. The revised service-aware candidate catalog, organization service enablement model, resource scopes and provider eSIM workflow remain pending. **Phase A has not begun.** Order is dependency-driven: security defects and HTTP foundations first, money/booking engines after contracts exist, portals after domain flows, finance last because it depends on settled booking data.

### Gate 0A — Authorization approval (blocks Phase A)

Status as of 24 Nov 2026. Detail, database/API impact and risks: `GATE-0-DECISION-REGISTER.md` §4.

- [x] **G0.1** Resolve C-01 (audit in/out). → **APPROVED (`G0-DEC-01`)**: audit and activity logs permanently excluded. Determines schema and permission catalog.
- [x] **G0.2 (principle)** One centralized permission catalog (C-02, C-03, C-04, C-17). → **APPROVED (`G0-DEC-02`)** as a principle only.
- [ ] **G0.2 (revised catalog)** Approve the exact service-aware catalog, organization eligibility, service applicability and scope metadata in `phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md`. The former 85-key draft is withdrawn as an implementation target.
- [ ] **G0.2 (provider authorization)** Approve service-aware eligibility and tenant/service/resource filtering. The old blanket P-09 exclusion is superseded; do not grant all provider permissions by organization type alone.
- [ ] **G0.2 (service enablement/resources)** Define the platform-controlled enabled-service relationship and non-hotel resource scopes before Phase A.
- [x] **G0.2 (delegation)** Apply the already-approved family rule, non-delegable list, owner-only actions and owner-template rules (DEC-11–DEC-19). → **APPROVED; implementation in Phase A.**
- [x] **G0.3** Decide property model vs hotels-as-properties (C-03, C-11). → **APPROVED (`G0-DEC-03`)**: `hotels` is the property entity, many per hotel organization, staff scoped by `membership_hotel_scopes`.
- [x] **G0.4** Decide visa transition authority and keys (C-04). → **APPROVED (`G0-DEC-04`)**: platform staff only decide; company users review documents.
- [x] **G0.5 (empty scope)** Empty scope means access to nothing. → **APPROVED (`G0-DEC-05`); implementation and verification in Phase A, no additional business approval.**

### Gate 0B — Business and integration approval (does not block Phase A)

- [ ] **G0.5** Decide notification architecture and delivery behaviour (BR-104, F-021/079/092/102/110). → **RECOMMENDED**: database + e-mail over the database queue; per-channel `notification_deliveries`; at-least-once delivery (no exactly-once e-mail claim); push/SMS/WhatsApp deferred.
- [ ] **G0.6** Decide payment/refund, flight, hotel, eSIM provider(s) and sandbox credentials. → **ARCHITECTURE RECOMMENDED; PROVIDER CHOICE REQUIRES CLARIFICATION.**
- [ ] **G0.7** Decide unresolved features F-027, F-043 and multi-city (C-13/C-14). → **RECOMMENDED**: defer F-027, show an allowance instead of live eSIM usage, drop multi-city.
- [ ] **G0.7 (F-071)** Tour cancellation policy (C-14). → **PENDING — NO POLICY APPROVED.** Eligibility follows the tour company's policy; the platform must not invent a mandatory full-refund rule; terms and refund basis must be explicit before booking.
- [ ] **G0.8** Approve settlement schema and lifecycle (C-16). → **RECOMMENDED**: `settlement_periods` + `settlement_items` derived from the ledger, with the eligibility, closing, reopening, failure, timezone and double-settlement rules in register §5.4.

### Phase A — Authorization and tenancy repairs (P0 security)

- [ ] **A1** Fix empty-scope semantics (D-01) to match BR-121/MT-11; add explicit "all services/all properties" representation if needed.
- [ ] **A2** Enforce assigned-only visibility for restricted employees at query level (D-02, BR-087/115).
- [ ] **A3** Remove or formally approve the `X-Organization-Id` fallback (D-03, BR-120).
- [ ] **A4** Replace membership mutation in `transfer()` with remove + create, clearing service scopes (D-04, BR-125).
- [ ] **A5** Implement invitation resend/rotation semantics (D-05).
- [ ] **A6** Add role/permission reconciliation and revocation semantics; enforce owner template immutability (D-07).
- [ ] **A7** Add tenant middleware that resolves membership and binds it to the request/token; make policies the single authorization gate (D-08).
- [ ] **A8** Add any missing documented permission keys/templates once G0.2 is decided.

### Phase B — HTTP/API foundation

- [ ] **B1** Versioned API routing (`routes/api.php` v1 groups), auth/guest/tenant/verified middleware, rate limiters.
- [ ] **B2** Error contract: `BusinessRuleException`/authorization/validation → consistent JSON (422/403/401/409), no leaked internals.
- [ ] **B3** Base FormRequest/Resource conventions, pagination, filtering/sorting envelope, idempotency handling.
- [ ] **B4** Fill the empty requests, resources and controllers for the first vertical slice; remove `authorize(): false` stubs.
- [ ] **B5** API documentation/contract for mobile and dashboards (OpenAPI or equivalent) so Phase 1 screens have a target.

### Phase C — Identity, account and profile (F-003–F-013, F-050–F-057, F-072/073)

- [ ] **C1** OTP services + SMS channel; email verification; password policy/neutral responses; reset flow.
- [ ] **C2** Email/password and social login; token issuance with `membership_id`; logout/logout-everywhere; device sessions.
- [ ] **C3** Profile completion/readiness, terms acceptance + re-acceptance gate.
- [ ] **C4** Travellers CRUD, saved documents (+ reuse rules), favourites, payment methods with atomic default.
- [ ] **C5** Partner portal auth and account-type enforcement; partner password reset.
- [ ] **C6** Account deletion request/cancel, blocking checks, 7-day scheduled anonymization (retain financial records).

### Phase D — Notifications and domain events

- [ ] **D1** Event/listener infrastructure for booking/visa/eSIM/account events (BR-104).
- [ ] **D2** Database + e-mail channels at launch (push deferred behind an adapter), per-channel delivery tracking with at-least-once retries, per-user read state, mark-all-read (BR-105).
- [ ] **D3** Notification inbox APIs for customer/admin/company/hotel/employee.

### Phase E — Reference data and public catalogues

- [ ] **E1** Airports/cities/destinations/search-history schema + admin data.
- [ ] **E2** Public visa catalogue (countries/types/details/documents), hotel search/detail/map, tour catalogue/detail, eSIM countries/packages, offers/destinations.
- [ ] **E3** Home feed and global search endpoint.
- [ ] **E4** Guest read policy (BR-028) and visibility rules (`active`, `published`, stop-sell, future slots).

### Phase F — Pricing, quotes and checkout

- [ ] **F1** Money/rounding utilities (BR-001/012) and quote DTOs.
- [ ] **F2** Pricing engines: flight (BR-003–006/013), hotel (BR-009/061–067), tour (BR-010/070–072), eSIM (BR-008/056–058), visa fee snapshot (BR-007/054).
- [ ] **F3** Checkout sessions with revalidation, price-change acceptance, idempotency (BR-006/013/020/041–043).
- [ ] **F4** Coupon validation incl. minimum spend + atomic redemption/release (BR-015, F-111).

### Phase G — Payments, bookings and post-booking

- [ ] **G1** Payment provider integration (tokenization, capture, webhooks), payment/refund records, method enablement config (BR-016–020).
- [ ] **G2** Canonical booking state machine + status history writer + reference generator (BR-076/113).
- [ ] **G3** Flight booking: passenger validation, duplicate check, ticketing/PNR (BR-032/046/049).
- [ ] **G4** Hotel booking with inventory locking, pay-now discount, voucher/partner delivery (BR-061/066/067/068).
- [ ] **G5** Tour booking with atomic slot capacity, pickup, voucher (BR-070–072).
- [ ] **G6** eSIM order → provider purchase → single-use activation/QR (BR-056–059).
- [ ] **G7** Booking list/detail APIs with status tabs, e-ticket, cancellation/refund, reschedule, baggage (F-044–F-049).

### Phase H — Visa workflow and partner portals

- [ ] **H1** Visa document upload (types/size/scanning), submission gate, price snapshot, tracker.
- [ ] **H2** Company review workspace + platform decision workflow with transitions, reasons, assignment.
- [ ] **H3** Partner onboarding end-to-end: legal documents, commission/services, review, activation e-mail, owner login gate.
- [ ] **H4** Company portal: overview/queue, tour CRUD + submit/review/resubmit, bookings, team/roles, settings, reports.
- [ ] **H5** Hotel portal: overview, calendar/rates/stop-sell, check-in/out, team/hotel scopes, profile/publish gate, reports.
- [ ] **H6** Restricted employee: assigned queues, read-only enforcement, locked modules, notifications.

### Phase I — Platform administration

- [ ] **I1** Partner directory/detail/suspend with automatic delisting; add-partner flow.
- [ ] **I2** Visa/eSIM catalogue CRUD, platform settings, coupons.
- [ ] **I3** Customer directory/detail/block with session revocation.
- [ ] **I4** Platform roles/permissions service + role editor APIs + owner-template protection.
- [ ] **I5** Deletion-request queue processing.
- [ ] **I6** KPIs and admin notifications using shared aggregation definitions (BR-112).

### Phase J — Finance, settlements and reporting

- [ ] **J1** Commission calculation and ledger posting on booking/payment/refund events (BR-096–098/102).
- [ ] **J2** Settlement schema/periods, partner-due aggregation, state transitions, payout integration, refund carry-forward (BR-099/100/114).
- [ ] **J3** Payout accounts/KYC change control (per Q-54 decision).
- [ ] **J4** Reports/exports honouring active filters and one KPI definition (BR-103/112/118).
- [ ] **J5** Document verification/expiry blocking payouts and hotel publication (BR-101/110).

### Phase K — Hardening and operational readiness

- [ ] **K1** Background jobs/scheduler: expiring checkout sessions, OTP cleanup, deletion grace, eSIM sync, stale rates, notification delivery.
- [ ] **K2** Rate limiting/throttling (auth, OTP, uploads, payment), idempotency at every money entry point.
- [ ] **K3** PII/security review: encrypted passport/IBAN/activation payload, hidden fields, storage access, no secrets in responses.
- [ ] **K4** Observability: structured logs, failed-job handling, provider webhook replay safety.
- [ ] **K5** Full regression suite once implementation stabilises (explicitly deferred during this audit).

### Dependency notes

- Phase F cannot start before E's data contracts, and G cannot start before F.
- H depends on G (bookings) and C (auth/team).
- I depends on A (permissions), C, E and G.
- J depends on G's payment/refund events; building it earlier would create unverifiable numbers.
- Audit/activity logging is excluded by `G0-DEC-01`; do not add an audit subsystem. `status_histories` remains limited to business status transitions.

---

## 12. Appendix A — Documentation reviewed

| File | Extent used |
|---|---|
| `docs/phase-01/SCREEN-COVERAGE-REPORT.md` | Full |
| `docs/phase-01/UI-SCREENS-INVENTORY.md` | Full mobile + dashboard inventory (tail of dashboard section read in part) |
| `docs/phase-02/FEATURES-CATALOG.md` | F-001–F-112 |
| `docs/phase-02/BUSINESS-LOGIC.md` | BR-001–BR-125 |
| `docs/phase-02/AUTHORIZATION-ARCHITECTURE.md` | Layers, scopes, PE-01–PE-12, delegation, lifecycle, Q&A/decisions |
| `docs/phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md` | Permission catalogue, role matrices, audit columns, totals |
| `docs/phase-02/USER-ROLES-AND-PERMISSIONS.md` | R-01–R-17 and per-role permission matrices |
| `docs/phase-02/WORKFLOWS.md` | WF-01–WF-15 + coverage table |
| `docs/phase-02/OPEN-QUESTIONS.md` | Q-01–Q-52, blocking classifications |
| `docs/phase-02/DB-DECISION-BRIEF.md` | DEC-01–DEC-10 rationale and tails (DEC-11–DEC-19 answer rows partially read) |

## 13. Appendix B — Implementation files inspected (primary)

`routes/api.php`, `routes/console.php`, `routes/web.php`; all `app/Http/Controllers/**`, `app/Http/Requests/**`, `app/Http/Resources/**`, `app/Http/Middleware/**`; `app/Services/Authorization/{AuthorizationService,TenantResolver}.php`, `app/Services/Organization/{OrganizationService,OrganizationApplicationService,MembershipService,RoleTemplateService,InvitationResult}.php`, `app/Services/Auth/*`, `app/Services/Admin/*`; all `app/Policies/*`; all `app/Models/*`; all `database/migrations/*`; `database/seeders/RolesAndPermissionsSeeder.php`; `config/authorization.php`, `config/permission.php`; `tests/**` (structure and placeholder bodies); `composer.json`.

## 14. Appendix C — Limitations of this audit

- No test suite was run (per instruction), so runtime behaviour of the implemented services is reported from code inspection, not execution.
- No database schema was queried directly; conclusions about tables/columns come from migrations and models. The live DB state (including whether all migrations were applied) was not re-verified in this audit.
- The mobile app and dashboard frontends are not in this repository; Phase 1 assessment covers backend capability only.
- Some documentation tails (notably DEC-11–DEC-19 answers) were only partially read; conflicts involving those decisions are reported only where other docs state them explicitly.
- Provider choices, gateway behaviour and external API capabilities were not researched; they are treated as open decisions.

**End of report. No implementation has been performed, and none will begin until the gap analysis and proposed order in §11 are approved.**


## 12. Multi-service provider authorization update (2026-11-24)

The owner confirmed that a single service-provider organization may operate multiple service lines. The controlling candidate catalog is [`phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md`](./phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md); the previous 85-key type-only eligibility proposal and blanket P-09 exclusion are superseded.

### Newly explicit implementation gaps

1. **Organization-enabled services are not yet represented by the existing membership service-scope relation.** The platform-controlled set of services enabled for an organization must be distinct from the services assigned to each membership. An employee cannot be assigned a service the organization does not have enabled.
2. **Permission-to-service applicability needs authoritative metadata/enforcement.** `allowed_org_types` is not enough to prevent a provider employee with a generic permission from accessing unrelated service records.
3. **Resource scopes beyond hotel properties need definition.** Tours, departures and visa offerings/requests must have resource ownership and assignment rules grounded in their workflows before implementation.
4. **eSIM partner operations are not established by the current customer-side purchase/activation screens.** Do not seed provider eSIM permissions until Q-53 defines responsibility.
5. **Hotel empty-scope enforcement is currently unsafe.** `Membership::isRestrictedToHotel()` returns true when the membership has no hotel-scope rows, which means an empty scope is treated as unrestricted. This conflicts with G0-DEC-03/G0-DEC-05 and must be corrected for non-owner memberships with negative tests.
6. **Service-scope enforcement must be rechecked against G0-DEC-05.** The earlier audit records that `AuthorizationService::withinServiceScope()` treats an empty service list as unrestricted. The implementation must fail closed for non-owners; verify the current source and add negative tests.

No code, migration, Seeder, authorization config or tests were changed in this documentation update.
