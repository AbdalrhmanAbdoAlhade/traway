# Phase 2 — Open Questions

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Decisions recorded — awaiting approval (`APPROVE PHASE 3`)
**Inputs:** all Phase 1 and Phase 2 documents
**Date:** 2026-11-23 · **Decision date:** 2026-11-24

---

## 1. Purpose and rules

This register holds every question that the design cannot answer. It exists so that Phase 3 (data model) is not built on guesses.

Rules:

1. A question is **blocking** if a table, endpoint or state machine cannot be finalised without it.
2. Each question states the **current working assumption** used in Phase 2. If the answer differs, only the listed artefacts must change.
3. "Owner" is the decision-maker, not the implementer.
4. No question is closed by inference alone; it needs an explicit answer from the owner.
5. Questions Q-01 – Q-34 come from features and rules; Q-35 – Q-39 close the remaining Phase 1 inconsistencies and gaps; Q-40 – Q-52 arise from the ten recorded decisions and the authorization requirement.
6. When a question is answered, its original text and working assumption are never deleted. The status changes and the answer is recorded in §2.9 (for the ten decisions), §2.10 (for the authorization questions) or §2.11 (for the Gate 0 decisions recorded on 2026-11-24).

### Status legend

`Open` = awaiting answer · `Answered` = decision recorded · `Blocking-3` = blocks Phase 3 schema (none currently) · `Blocking-4` = blocks Phase 4 API · `Non-blocking` = can be answered later without rework.

**Current position: 0 questions block Phase 3.** All ten former Phase-3 blockers are answered (DEC-01 – DEC-10).

---

## 2. Question register

### 2.1 Money, pricing & refunds

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-01 | Why do the cancellation figures (3,605 refund after a 1,515 fee ≈ 5,120) not reconcile with the payment total on the same trip ($511)? Is another currency (e.g. EGP) or another booking shown? | M-042 vs M-024 | Refund arithmetic (BR-078), ledger design, currency storage | Display/currency bug in the mock, not a business rule; booking total is authoritative | Product owner | **Answered (DEC-01)** |
| Q-02 | After OTP login, does a brand-new user go to Home or to profile completion? Phone signup has no profile step drawn, while e-mail signup ends at M-011. | M-007, M-010, M-011 | Auth routing table, onboarding completion flag | New phone users go to M-011; returning users go to Home | Product owner | Blocking-4 |
| Q-03 | What are the exact auth constraints: minimum password length, OTP validity window, maximum attempts, error copy, and "e-mail already exists" behaviour? | M-007, M-008, M-009, M-013 | Auth validation rules, rate-limit config | 8-char minimum with the four visible character classes; OTP valid 5 minutes, 5 attempts | Security + product | Blocking-4 |
| Q-04 | Which profile fields are mandatory at M-011, and what constraints apply (minimum age, gender options, nationality source)? | M-011 | User profile schema, validation | Names + birth date + nationality mandatory; gender optional | Product owner | Non-blocking |
| Q-14 | What are the promo-code rules: stacking, first-booking definition, per-service scope, expiry, refund interaction? | M-024, M-057 | Promo table, discount engine | One code per booking, first confirmed booking only, percentage off subtotal, no cash refund of discount | Marketing | **Answered (DEC-03)** |
| Q-19 | The app shows only USD with a currency setting. Is multi-currency display in scope, and which currencies/rates? | M-043, M-053 | Money storage, FX table, rounding | Single currency (USD) at launch; selector hidden until FX is defined | Product owner | **Answered (DEC-02)** |
| Q-31 | How is the baggage add-on paid for? M-041 adds baggage to an existing booking but no payment step is drawn. | M-041, M-038 | Post-booking payment flow, add-on ledger | Charge the default instrument immediately, with a confirmation step to be designed | Product owner | Blocking-4 |

### 2.2 Bookings & inventory

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-08 | Multi-city is offered as a trip type. What is its form, leg count, pricing and ticketing rule? | M-019 | Flight search model, fare-combination logic | Out of scope for v1; hide the option | Product owner | Non-blocking |
| Q-09 | Where is the seat map, what does a seat cost, and which seats are free? Only a three-seat confirmation bar exists. | M-026 | Seat inventory, pricing, booking flow | Seats are free and optional at launch; seat map deferred | Product owner | Non-blocking |
| Q-10 | What are the passenger name/passport validation rules, and is name correction possible after ticketing? | M-023, M-039 | Traveller schema, ticket amendment policy | Latin uppercase names matching passport, ≥6 months validity; no name change after ticketing | Operations | Blocking-4 |
| Q-11 | How long is the fare hold (14:32 shown) and who configures it — platform, carrier or route? | M-024 | Hold table, expiry job | 15 minutes platform-wide, provider-overridable | Operations | **Answered (DEC-05)** |
| Q-18 | No empty, error or offline state is designed for any list. What should each show? | all list screens | UI states, API error contract | Generic retry + empty-state pattern with a single CTA | Design | Non-blocking |
| Q-25 | Nine different reference formats appear (`TRW7K2`, `TW-48210`, `TRW-V-2291`, `HTL-90418`, `TR-33107`, `PAY-90121`, `P-2291`, `TW-48197`, `TW-48201`). What is the canonical scheme per entity? | M-025, M-031, M-036, M-064, M-068, D-02.3, D-03.14 | Primary keys/display IDs for every booking type | One prefix per entity + year + sequence; all formats shown are mock values | Product owner | **Answered (DEC-04)** |
| Q-27 | The admin bookings table has no visible detail screen. What does a booking row open? | D-03.3 | Admin booking detail endpoints | Read-only detail reusing the mobile booking data | Product owner | Non-blocking |
| Q-30 | The company bookings table has no visible detail screen. What does a row open? | D-04.5 | Company booking detail endpoints | Read-only detail with customer contact and service snapshot | Product owner | Non-blocking |
| Q-34 | How are no-shows, walk-ins, offline bookings and overbooking handled in the hotel portal? | D-05.4, D-05.3 | Hotel booking states, inventory locking | Not supported at launch; overbooking prevented by atomic inventory decrement | Hotel operations | Non-blocking |

### 2.3 Visas

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-12 | The visa fee is a placeholder «[حسب لوحة التحكم]». Is the fee always country+type based, are visas paid inside the app, and is nationality used for eligibility/pricing? | M-029, M-027, D-03.9 | Visa pricing, payment, eligibility model | Fee = catalogue price per country+type; payment in-app at submission; nationality not a filter | Product owner | **Answered (DEC-06)** |
| Q-13 | After documents are accepted, how is the application submitted to the embassy/authority, who records approval/rejection, and what does the traveller see? | D-04.6, M-031, M-032 | Visa state machine, outcome notifications | External submission; company records the decision; traveller notified and status shown on the tracker | Operations | **Answered (DEC-07)** |
| Q-26 | When a partner application is rejected, is a reason required? Can the same licence re-apply? What happens when the activation link expires? | D-03.6, D-02.3 | Partner onboarding states, notification content | Reason mandatory; re-apply allowed after 30 days; activation link resendable | Operations | Blocking-4 |

### 2.4 eSIM

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-15 | What does «التحقق الآن» (device compatibility) actually check, and does an incompatible device block purchase? | M-033 | Device check integration, purchase gate | Check `eSIM` support via device model API; incompatible devices can browse but not buy | Product owner | Blocking-4 |
| Q-16 | What is the exact eSIM refund window and does it end at activation, installation or first data use? | M-035, M-036 | Refund policy engine | Refundable until activation only; activation is irreversible | Operations | Non-blocking |
| Q-17 | Is live eSIM usage tracking required, or is displaying the purchased allowance sufficient? | M-037 | Usage data source, provider capability, polling and customer display | Show purchased allowance and validity. Show remaining usage only if the selected provider exposes a reliable usage API. No polling or dedicated usage screen in v1 by default. | Product owner | Non-blocking |
| Q-32 | What happens when the eSIM provider API sync fails or returns partial data? | D-03.10 | Availability integrity, sync job | Keep last known good catalogue; flag as stale; never silently delist | Engineering | Non-blocking |

### 2.5 Hotels & tours

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-23 | The hotel search form is missing. What inputs exist — destination, dates, adults, children, rooms, nationality? | M-059 summary only | Hotel search API, occupancy model | Destination + dates + adults + children + rooms | Product owner | **Answered (DEC-08)** |
| Q-24 | What cancellation and refund policy applies to each tour, and how are eligibility, deadlines and refund amounts calculated? | M-068 | Tour cancellation workflow, company policy, refund calculation and customer disclosure | Follow the applicable tour company's policy. No universal 24-hour full-refund rule is approved. The applicable terms and refund calculation must be shown before booking. | Operations / Project owner | Non-blocking — policy approval required before implementing F-071 |
| Q-33 | Who reviews submitted tours, within what SLA, and is a rejection reason mandatory? | D-04.3, D-03.1 | Content moderation workflow | Platform content manager reviews within 2 business days; reason mandatory | Operations | Non-blocking |

### 2.6 Notifications, search & support

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-06 | Where does each global-search result type navigate (destination, hotel, eSIM, visa)? | M-015 | Search result contract | Destination → flight search; hotel → detail; eSIM → packages; visa → types | Product owner | Non-blocking |
| Q-07 | What are the deep-link targets for each notification type, and is push delivery in scope? | M-048, D-03.2, D-04.2, D-05.2 | Notification payload schema | Deep link to the related entity detail; push + in-app inbox at launch | Product owner | Blocking-4 |
| Q-22 | Support shows placeholder hours/phone and only WhatsApp/phone. Is in-app chat required? What are the real contact details? | M-055 | Support module scope | External channels only; details to be supplied before release | Operations | Non-blocking |
| Q-37 | Ratings are displayed on hotels and tours but no review-submission UI exists. Can customers write reviews in v1? | M-062, M-066 | Review table, moderation | Reviews are platform/partner-seeded; submission deferred | Product owner | Non-blocking |

### 2.7 Account, settings & localisation

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-05 | There is no logout control anywhere in the mobile design. Where does logout live, and is multi-device session management required? | all authenticated screens | Session model, settings UI | Logout added to settings; all sessions revocable | Product owner | Non-blocking |
| Q-20 | What exactly can a guest do, and how is the interrupted action resumed after login? | M-005, M-014 | Guest entitlements, auth redirect context | Browse + search only; resume the last selected product | Product owner | Blocking-4 |
| Q-21 | Account deletion: is there a grace period, can it be cancelled, what happens to in-flight visas and upcoming trips, and how long are records retained? | M-056, D-03.11 | Deletion job, retention policy, legal | 14-day grace period; deletion blocked while a trip is within 7 days; financial records retained 5 years | Legal + product | **Answered (DEC-09)** |
| Q-28 | What does «حظر الحساب» do — permanent ban, temporary suspension, with appeal? | D-03.13 | Account state model | Suspension until manually lifted; no appeal flow at launch | Support lead | Non-blocking |
| Q-35 | The bottom tab is labelled «جوازاتي» (my passports) on some screens and «حجوزاتي» (my bookings) on others. Which is canonical? | M-014, M-037, M-043 | Navigation labels, translation keys | «حجوزاتي» — the icon and destination are bookings | Design | Non-blocking |
| Q-39 | Only Arabic mobile layouts exist. Is an English layout in scope for v1, and does RTL/LTR mirroring apply to all screens? | M-053 language toggle | Localisation scope, layout QA | English in scope, mirroring handled by the standard RTL framework | Product owner | Non-blocking |

### 2.8 Platform operations & governance

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-29 | Refund requests appear as notifications, and settlements have «transfer» actions, but neither workflow UI is designed. Who approves a refund, and how is a payout executed? | D-03.2, D-03.14, D-04.9 | Refund approval states, settlement execution, audit | Refund approved by super admin; payout executed manually and recorded as transferred | Finance | **Answered (DEC-10)** |
| Q-36 | The admin board header says «14 شاشات» but 15 full pages (plus 2 modals) are drawn. Which is authoritative? | board header; D-03.1 – D-03.15 | Scope confirmation only | 15 pages are authoritative; header is stale | Design | Non-blocking |
| Q-38 | Export buttons exist across the dashboards but no dedicated reports or audit-log screen is designed. Is an audit log required, and who can read it? | D-03.1, D-03.14, D-04.9, D-05.7 | Audit storage, reporting module | Audit written from day one, readable by super admin; report screens deferred | Product owner | **Answered (G0-DEC-01)** |

---

## 2.9 Recorded decisions (2026-11-24)

These ten answers close every Phase-3 blocker. Each row **supersedes the working assumption** in §2 for that question; the original text is preserved above.

| Q | DEC | Decision recorded | Rules / features updated |
|---|---|---|---|
| Q-01 | DEC-01 | USD only across the entire system. All Figma amounts are illustrative design values. No cancellation or refund rule is derived from them. Cancellation and payment-related fees are applied by the payment gateway; Traway implements no custom cancellation-fee engine. | BR-001, BR-002, BR-078; F-029, F-049 |
| Q-19 | DEC-02 | USD only — all prices, displays, bookings and payment records. No multi-currency functionality. | BR-001, BR-002, BR-014; F-055 |
| Q-14 | DEC-03 | One coupon per booking. No first-booking-only restriction. Coupons are global or restricted to flights, hotels or visas. Platform Admin and company owners may create coupons (company owners only for their own company). Maximum usage limits are enforced, including under concurrent redemption. | BR-015; F-019, F-111 |
| Q-25 | DEC-04 | Separate internal database id and customer-facing reference. Formats `TRW-FLT-{random}` and `TRW-HOT-{random}`; references random, unique, non-sequential. Airline PNRs and provider references remain separate from Traway references. | BR-049, BR-113; F-030, F-066 |
| Q-11 | DEC-05 | No fare hold. Revalidate the fare immediately before payment. Do not promise a fixed fare-reservation period. | BR-006, BR-041, BR-042, BR-043; F-028 |
| Q-12 | DEC-06 | Customers pay visa fees through Traway. Fees are non-refundable when the application is rejected. Pricing does not vary by nationality. Platform Admin or the publishing tourism company sets and updates the price. Every offering carries expected processing duration and validity/expiration information. | BR-007, BR-052, BR-054, BR-055; F-033, F-084 |
| Q-13 | DEC-07 | Manual processing through the dashboard. Submission to the authority happens outside Traway. Employees record decisions and status changes. Customer-visible statuses: Submitted → Under Review → Documents Required → Processing → Approved / Rejected. Processing duration is configured per offering; validity/expiration per offering or per issued visa. | BR-037, BR-052, BR-095; F-037, F-096, F-108 |
| Q-23 | DEC-08 | Search inputs: city/destination, check-in, check-out, adults, children, rooms. Hotels store latitude and longitude for location search and the map. No nationality-based or child-age pricing rules are assumed. | BR-060, BR-063, BR-065; F-061, F-063 |
| Q-21 | DEC-09 | Seven-day grace period; the user may cancel the request during it. Deletion is blocked while the user has upcoming trips, a visa application under review, or a pending refund. Financial and booking records are retained. After anonymisation, companies and hotels cannot access the customer's personal information. | BR-029, BR-108, BR-109; F-057, F-086 |
| Q-29 | DEC-10 | Policy-based automatic refunds where applicable; exceptional refunds require an authorized employee; no fixed monetary threshold. Partner payouts go through the payment gateway, subject to its capabilities, and are requested on demand. Negative partner balances are deducted from future settlements. Partners bear gateway fees and chargeback costs. | BR-096 – BR-100; F-089, F-099 |

---

## 2.10 Authorization and follow-up questions (Q-40 – Q-52)

These questions arise from the recorded decisions and the authorization requirement. **None blocks Phase 3**; each has a documented default and a non-destructive change path.

| ID | Question | Basis | Impact if unanswered | Working assumption / accommodation | Owner | Status |
|---|---|---|---|---|---|---|
| Q-40 | Fees are gateway-applied. What does the customer see as a "cancellation fee" on M-042, and is the breakdown computed by the gateway or shown as informational only? | M-042, DEC-01 | Refund-screen copy, refund breakdown fields | Show the gateway-retained amount when the gateway returns it; otherwise show the paid amount and the provider's refund confirmation. No Traway fee engine. | Product + finance | Blocking-4 |
| Q-41 | Can hotel owners create coupons for their own hotel, or only Platform Admin and company owners? | DEC-03, F-111, D-03.15 | Coupon owning scope and `coupons.*` applicability | Platform Admin and company owners only. Adding hotel scope later adds an owning-organization value; no table change. | Product owner | Non-blocking |
| Q-42 | Which prefixes apply to the entities not named in DEC-04 (visa, tour, eSIM, payment, partner application)? | DEC-04; M-031, M-068, M-036, D-03.14, D-02.3 | Reference generator seed values only | `TRW-VIS-`, `TRW-TUR-`, `TRW-ESIM-`, `TRW-PAY-`, `TRW-PTR-`, same random non-sequential pattern. | Product owner | Non-blocking |
| Q-43 | With no fare hold, what replaces the 14:32 countdown on the payment screen? | M-024, DEC-05 | Payment-screen copy and UX only | Remove the countdown; show "price confirmed at payment". Block only if revalidation fails or the price changed and consent is refused. | Design | Non-blocking |
| Q-44 | If a customer withdraws or cancels a visa application before the authority decides, is the fee refundable? | DEC-06, M-042 | Visa refund policy data | Non-refundable once submitted; refundable before submission. Modelled as a per-offering policy flag. | Product owner | Blocking-4 |
| Q-45 | What is a "Service Owner" — an organization-level manager of a service line, or a platform-level owner of a service? | Authorization requirement item 7 | Role family and scope | **Answered by the requirement:** organization-level member scoped to one or more service lines (R-17). A platform variant is additive. | Product owner | **Answered (requirement)** |
| Q-46 | Which actors may move a visa application between the DEC-07 statuses, and are rejection reasons mandatory and customer-visible? | DEC-07; D-04.6, D-06.1 | Transition permissions, reason fields | Company employees with `visa_requests.update_status`; platform staff may override with the platform visa permission, audited. Rejection reason mandatory; customer sees a reason category and note. **Superseded by `G0-DEC-04`: no `update_status` key exists in either family. Only platform staff holding `platform.visa_requests.decide` record a decision; company staff validate documents with `visa_requests.review`. Rejection reason remains mandatory; the customer sees a reason category and note.** | Operations | **Answered (G0-DEC-04)** |
| Q-47 | How long are financial and booking records retained after anonymisation? | DEC-09, BR-109 | Retention/purge configuration, not table shape | Five years for invoices, payments and refunds; legal to confirm before production. | Legal | Non-blocking |
| Q-48 | Can one person hold both a platform membership and a partner membership, and how is context chosen? | Authorization requirement | Identity/membership model, login context | Allowed; login chooses one active context; roles never mix. | Product owner | Non-blocking |
| Q-49 | Can a hotel organization own more than one property (hotel group / branches)? | D-05.x, DEC-08 | Adds a branch scope level | One property per hotel organization at launch; branch scope is additive. | Product owner | **Answered (G0-DEC-03)** |
| Q-50 | Spatie Laravel Permission (teams) plus a custom layer, or fully custom authorization tables? | AUTHORIZATION-ARCHITECTURE §13 | Storage shape only | Spatie + custom layer; tenant isolation, service scope and delegation ceilings stay in application services. Audit/activity logging is excluded by G0-DEC-01. | Engineering | Non-blocking |
| Q-51 | DEC-10 requires "an authorized employee" for exceptional refunds. Which family approves — platform finance, or partner staff with `bookings.refund`? | DEC-10; D-03.14, D-04.5 | Refund approval table and permission | Policy refunds auto-execute; exceptional refunds require platform finance approval. Partner staff may request, not approve. | Finance | Blocking-4 |
| Q-52 | When a discounted booking is cancelled, is the discount returned as cash or only reversed from the refundable amount? | DEC-03, BR-078 | Refund arithmetic, redemption reversal | Refund is based on the amount actually paid; the discount is reversed, never paid in cash. | Finance | Blocking-4 |

---

## 2.11 Gate 0 decisions (2026-11-24)

These follow the authorization audit in `../IMPLEMENTATION-GAP-ANALYSIS.md`. They are **Gate 0 decisions** and are deliberately numbered `G0-DEC-xx` so they never collide with the business decisions `DEC-01 – DEC-10` in §2.9. Full reasoning, database/API impact and risks: [`../GATE-0-DECISION-REGISTER.md`](../GATE-0-DECISION-REGISTER.md).

| ID | Decision | Status | Resolves |
|---|---|---|---|
| G0-DEC-01 | Audit and general activity logs are permanently excluded. `status_histories` may record business entity status transitions only and must never become a user activity log or a user activity feed. The organization read key is therefore `status_history.view`, not `team_activity.view`. | **Approved** | Q-38; BR-107/111/124; F-112 audit clause; permission `platform.audit.view` |
| G0-DEC-02 | One centralized authoritative permission catalog. Current candidate: `SERVICE-AWARE-PERMISSION-CATALOG.md` (37 platform + 48 organization candidates, service-aware eligibility). | Principle **approved**; exact catalog metadata **awaiting approval** | The 83/84/78/80 historical count conflict and multi-service provider scopes |
| G0-DEC-03 | A hotel organization may own multiple hotel properties; `hotels` is the property entity; `membership_hotel_scopes` scopes staff; bookings reference `hotel_id`; an empty property scope means no access. | **Approved** | Q-49; C-03; C-11 |
| G0-DEC-04 | Only authorized Traway platform staff may make the final visa approval/rejection decision. Company users may view within scope and review documents, not decide. The six customer-visible statuses are unchanged. | **Approved** | Q-46; C-04 |
| G0-DEC-05 | An empty scope means access to nothing. No empty array or null scope is implicit global access; every scoped query fails closed. | **Approved** | C-08; BR-121 |

The decisions are now organised into two approval boundaries (see the register §1.3):

- **Gate 0A — authorization.** Blocks Phase A. Contains `G0-DEC-01`–`G0-DEC-05` (the five approved decisions), approval of the revised service-aware candidate catalog, organization-enabled service metadata, membership service/resource scopes, delegation and owner-template rules, and empty-scope enforcement. The old blanket P-09 exclusion and the pre-multi-service 85-key implementation proposal are superseded.
- **Gate 0B — business and integration.** Does **not** block Phase A. Contains notifications (G0.5), provider architecture and selection (G0.6), F-027/F-043/multi-city, **F-071 (no policy approved)**, and settlements (G0.8). Each blocks only the phase that depends on it.

Q-09, Q-17 and Q-24 remain open and are linked to the `G0.7` items; Q-24 (tour cancellation) stays open until the owner approves the tour-policy model for F-071.

---

## 3. Blocking summary

| Phase blocked | Questions |
|---|---|
| Phase 3 — data model | **None.** Q-01, Q-11, Q-12, Q-13, Q-14, Q-19, Q-21, Q-23, Q-25 and Q-29 are answered (DEC-01 – DEC-10). |
| Phase 4 — API contracts | Q-02, Q-03, Q-07, Q-10, Q-15, Q-20, Q-26, Q-31, Q-40, Q-44, Q-51, Q-52 (12 — Q-46 answered by G0-DEC-04) |
| Answered | Q-01, Q-11, Q-12, Q-13, Q-14, Q-19, Q-21, Q-23, Q-25, Q-29, Q-38, Q-45, Q-46, Q-49 (14) |
| No phase blocked | Q-04, Q-05, Q-06, Q-08, Q-09, Q-16, Q-17, Q-18, Q-22, Q-24, Q-27, Q-28, Q-30, Q-32, Q-33, Q-34, Q-35, Q-36, Q-37, Q-39, Q-41, Q-42, Q-43, Q-47, Q-48, Q-50 (26 — Q-38 and Q-49 now answered) |

**No question blocks Phase 3.** The schema may be designed against the recorded decisions; Phase 4 (API contracts) still depends on the 13 open items above, each with a documented default.

---

## 4. Phase 1 inconsistency closure

| # | Phase 1 inconsistency | Resolution in Phase 2 | Question / status |
|---|---|---|---|
| 1 | Tab label «جوازاتي» vs «حجوزاتي» | Treated as a copy defect; bookings is the destination | Q-35 (open, non-blocking) |
| 2 | Nine booking-reference formats | Separate internal id + random customer reference per entity; airline PNR separate | Q-25 — **Answered (DEC-04)** |
| 3 | Admin header 14 vs 15 pages | 15 pages used as scope | Q-36 (open, non-blocking) |
| 4 | USD shown while a currency selector exists | Single currency confirmed; the selector is hidden | Q-19 — **Answered (DEC-02)** |
| 5 | Visa fee placeholder | Fee is administered per offering; paid through Traway | Q-12 — **Answered (DEC-06)** |
| 6 | Support hours/phone placeholders | Channel scope questioned, placeholders marked non-shippable | Q-22 (open, non-blocking) |
| 7 | `Bottom Bar.png` is a seat fragment | Seat selection marked unresolved, not a feature | Q-09 (open, non-blocking) |
| 8 | `Iframe.png` is the only home capture | Treated as the authoritative home state; no behaviour lost | — (documented fact) |
| 9 | Deletion does not cancel bookings | Deletion is blocked during upcoming trips; records retained | Q-21 — **Answered (DEC-09)** |
| 10 | Employee portal is a permission state | Resolved: R-16, a narrow tenant role, not a separate product | — (resolved in roles doc) |
| 11 | Cancellation figures vs payment total | Figma amounts are illustrative; fees are gateway-applied | Q-01 — **Answered (DEC-01)** |

**Result: 5 inconsistencies closed by decision (2, 4, 5, 9, 11); 2 remain documentation facts (8, 10); 4 remain open as non-blocking design/operations questions (1, 3, 6, 7); none is unrecorded.**

---

## 5. Phase 1 coverage-gap closure

| # | Phase 1 gap | Phase 2 handling | Question / status |
|---|---|---|---|
| 1 | Flight seat map | F-027 marked `U`, excluded from v1 | Q-09 (open, non-blocking) |
| 2 | Hotel search form | Inputs fixed by decision: destination, dates, adults, children, rooms; lat/lng stored | Q-23 — **Answered (DEC-08)** |
| 3 | Tour cancellation | F-071 marked `U`; promise recorded without flow | Q-24 (open, non-blocking) |
| 4 | Visa payment checkout | Fee paid through Traway; non-refundable on rejection | Q-12 — **Answered (DEC-06)**; withdrawal case Q-44 |
| 5 | Notification destinations | Deep-link schema questioned | Q-07 (open, Blocking-4) |
| 6 | Support chat thread | Channel scope questioned | Q-22 (open, non-blocking) |
| 7 | Review submission | Deferred; ratings treated as seeded | Q-37 (open, non-blocking) |
| 8 | Multi-city flights | Option marked out of scope | Q-08 (open, non-blocking) |
| 9 | eSIM usage screen | Balance display only | Q-17 (open, non-blocking) |
| 10 | Admin reports / audit log | Audit is permanently excluded (`G0-DEC-01`); report screens still deferred | Q-38 — **Answered (`G0-DEC-01`)** |
| 11 | Partner document re-upload after rejection | Covered by resubmission rule (BR-093) | Q-26 (open, Blocking-4) |
| 12 | Refund processing UI | Approval model fixed: policy-auto + exceptional approval | Q-29 — **Answered (DEC-10)**; approver family Q-51 |
| 13 | Payout / settlement detail | Gateway payouts, on demand, negative balance carry-forward | Q-29 — **Answered (DEC-10)** |
| 14 | Empty / error / offline states | Generic pattern assumed, design needed | Q-18 (open, non-blocking) |
| 15 | English mobile layouts | Localisation scope as a question | Q-39 (open, non-blocking) |

**Result: 15 of 15 gaps recorded; 4 closed by decision (2, 4, 12, 13); 0 gaps converted into silent assumptions.**

---

## 6. Assumptions register

These are the assumptions Phase 2 used **without** a question (either because the evidence is strong or because the alternative would contradict the design). They are listed so that a reviewer can overturn any of them explicitly.

| ID | Assumption | Basis |
|---|---|---|
| A-01 | A single Laravel-backed API serves all six front-ends. | The task brief specifies a Laravel 12 API backend; the six UIs need shared data. |
| A-02 | Partner tenants are isolated at query level. | Separate portals and role names, plus D-04.6 reviewer queues. |
| A-03 | The employee portal is the company tenant with a narrow role. | D-06.x reuses the company shell and locks modules. |
| A-04 | USD is the only currency for prices, display, bookings and payments. **Confirmed by DEC-02.** | Every capture uses `$`; decision dated 2026-11-24. |
| A-05 | Commission is deducted before partner settlement, not charged to the customer separately. | D-04.4 «net after 10 % commission». |
| A-06 | Visa applications are reviewed by the partner company, not by Traway staff directly. | D-04.6 and D-03.2 routes. |
| A-07 | Notifications are persisted as inbox items and pushed. | Inbox UI with read/unread states. |
| A-08 | Confirmation screens are states of the booking flow, not separate routes with new data. | Same reference and totals. |
| A-09 | Ratings/review counts are read-only to partners. | No edit affordance on any partner screen. |
| A-10 | Account deletion is a back-office workflow (request → 7-day grace → anonymisation), not an immediate hard delete. **Confirmed by DEC-09.** | D-03.11 queue with 14 open requests; decision dated 2026-11-24. |

---

## 7. Validation

| Check | Result |
|---|---|
| Questions registered | 52 (Q-01 – Q-52) |
| Questions with screen or requirement evidence | 52 / 52 |
| Questions with an owner | 52 / 52 |
| Answered by decision | 11 (Q-01, Q-11, Q-12, Q-13, Q-14, Q-19, Q-21, Q-23, Q-25, Q-29, Q-45) |
| Blocking Phase 3 | **0** |
| Blocking Phase 4 only | 13 |
| Non-blocking | 28 |
| Open questions without a documented default | 0 |
| Phase 1 inconsistencies covered | 11 / 11 (5 closed by decision) |
| Phase 1 coverage gaps covered | 15 / 15 (4 closed by decision) |
| Assumptions documented | 10 (2 confirmed by decision) |
| Unresolved item without a question ID | 0 |


## 2.12 Multi-service provider authorization update (2026-11-24)

The owner confirmed that one service-provider organization may operate more than one service line. The previous P-09 recommendation to exclude all `bookings.*` and `customers.view` permissions from service providers is superseded.

The replacement rule is service-aware: organization-enabled services are distinct from each employee's service scope; resource/property scope further narrows access; empty scope grants nothing. Hotel/tour bookings and visa requests must use their own workflow/permission boundaries; eSIM is platform-only under Q-53. Customer contact is contextual and minimal. Flights remain platform-only; final visa decisions remain platform-only.

**New blocking Gate 0A work:** recalculate the exact permission catalog and service eligibility; define provider-side eSIM order/activation responsibilities; document provider dashboard coverage. The 85-key proposal is no longer a final seed target.


## 2.13 Multi-service provider catalog and dashboard blockers (2026-11-24)

| ID | Question | Impact | Working rule / next action | Status |
|---|---|---|---|---|
| Q-53 | Which partner-side eSIM responsibilities exist at launch (package ownership, order fulfillment, activation, failure handling, support, refunds, usage inquiries), and which party owns each action? | Organization permission keys, eSIM order workflow, provider dashboard and provider API integration | **Owner decision A (2026-11-24): Traway alone owns the complete eSIM business workflow** — catalogue/packages, pricing/margin, customer orders, activation orchestration, failure handling, customer support, refund decisions and usage inquiries. No service-provider permissions, resource scopes, queues or dashboard screens for eSIM. External eSIM suppliers/APIs are technical integrations controlled by Traway, not service-provider tenants; vendor selection and credentials remain Gate 0B. | **Answered — owner decision A** |
| Q-54 | What is the authoritative data model and platform workflow for enabling/disabling service lines on an organization? | Organization capability model, onboarding/partner details, employee scope validation, service availability | **Owner decision A (2026-11-24): Traway controls enablement.** Organization requests a service; Platform Admin approves or rejects; only approved services become enabled. The organization owner manages employees only within enabled services. Traway can suspend/disable one service independently without deactivating the whole organization. **Owner decision B (2026-11-24):** block new bookings/orders, resource creation, publishing and new commitments, but allow the organization and authorized staff to view and perform explicitly permitted servicing/resolution actions on existing records under normal tenant/service/resource/assignment checks. No blanket write bypass; exact per-service action allowlist and state-transition rules remain Gate 0A design. Persist service lifecycle separately from membership scopes. | **Answered — owner decisions A+B; schema, lifecycle and action allowlist remain** |
| Q-55 | What provider dashboard screens and queues are required for a multi-service organization and its service-scoped employees? | UI inventory, feature catalog, API contracts, navigation and reports | **Owner decision A (2026-11-24): one unified multi-service provider dashboard.** A shared shell/navigation contains modules only for organization-enabled services; each employee sees only modules and data allowed by permissions and service/resource scopes. The home dashboard aggregates only authorized metrics. Each service retains its own workflows, queues and reports. Exact screens, widgets and queue actions must still be mapped from the supplied UI and confirmed dashboard docs; do not invent screen IDs. | **Direction answered — detailed UI mapping remains** |
| Q-56 | Which resource types need explicit membership-level scope per service beyond hotel properties (for example tours, departures, visa offerings, eSIM packages/orders)? | Scope tables, policies, query filters and assignment rules | **Owner decision A with support for service-only B (2026-11-24): hierarchical service-then-resource scoping.** Membership receives explicit service scope first. Where a service has independently manageable resources, further restrict by selected resources: Hotels → specific hotel properties; Tours → applicable tours/departures/resource groups; Visas → assigned requests or explicitly authorized team queues. A service may use service-level scope only when the workflow has no meaningful narrower resource boundary. Owners see all resources only within Traway-enabled services. Empty required scope denies access. eSIM has no provider scope because Q-53 makes it platform-only. | **Scope principle answered — per-service resource map/schema remains** |

The candidate permission definitions and current scope gaps are maintained in [`SERVICE-AWARE-PERMISSION-CATALOG.md`](./SERVICE-AWARE-PERMISSION-CATALOG.md). Dashboard evidence and candidate screen-to-permission mapping are in [`DASHBOARD-PERMISSION-MAPPING.md`](./DASHBOARD-PERMISSION-MAPPING.md). Q-53–Q-56 now have owner-approved product directions; remaining detailed schema/UI mapping is not permission to seed speculative permissions or invent screen workflows.

| Q-57 | What happens to existing bookings/applications when Traway suspends or disables one organization service? | Service lifecycle, booking/application policies, write permissions and customer continuity | **Owner decision B (2026-11-24):** block new intake and new commitments, but preserve authorized viewing and explicitly permitted servicing/resolution of existing records. Continue enforcing tenant ownership, service/resource/assignment scopes, permissions and business-state rules. No new bookings/orders, resource creation or publishing. Exact per-service action allowlist remains to design. | **Direction answered — action matrix remains** |
