# Phase 2 — Open Questions

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Complete — awaiting approval (`APPROVE PHASE 2`)
**Inputs:** all Phase 1 and Phase 2 documents
**Date:** 2026-11-23

---

## 1. Purpose and rules

This register holds every question that the design cannot answer. It exists so that Phase 3 (data model) is not built on guesses.

Rules:

1. A question is **blocking** if a table, endpoint or state machine cannot be finalised without it.
2. Each question states the **current working assumption** used in Phase 2. If the answer differs, only the listed artefacts must change.
3. "Owner" is the decision-maker, not the implementer.
4. No question is closed by inference alone; it needs an explicit answer from the owner.
5. Questions Q-01 – Q-34 come from features and rules; Q-35 – Q-39 close the remaining Phase 1 inconsistencies and gaps.

### Status legend

`Open` = awaiting answer · `Blocking-3` = blocks Phase 3 schema · `Blocking-4` = blocks Phase 4 API · `Non-blocking` = can be answered later without rework.

---

## 2. Question register

### 2.1 Money, pricing & refunds

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-01 | Why do the cancellation figures (3,605 refund after a 1,515 fee ≈ 5,120) not reconcile with the payment total on the same trip ($511)? Is another currency (e.g. EGP) or another booking shown? | M-042 vs M-024 | Refund arithmetic (BR-078), ledger design, currency storage | Display/currency bug in the mock, not a business rule; booking total is authoritative | Product owner | **Blocking-3** |
| Q-02 | After OTP login, does a brand-new user go to Home or to profile completion? Phone signup has no profile step drawn, while e-mail signup ends at M-011. | M-007, M-010, M-011 | Auth routing table, onboarding completion flag | New phone users go to M-011; returning users go to Home | Product owner | Blocking-4 |
| Q-03 | What are the exact auth constraints: minimum password length, OTP validity window, maximum attempts, error copy, and "e-mail already exists" behaviour? | M-007, M-008, M-009, M-013 | Auth validation rules, rate-limit config | 8-char minimum with the four visible character classes; OTP valid 5 minutes, 5 attempts | Security + product | Blocking-4 |
| Q-04 | Which profile fields are mandatory at M-011, and what constraints apply (minimum age, gender options, nationality source)? | M-011 | User profile schema, validation | Names + birth date + nationality mandatory; gender optional | Product owner | Non-blocking |
| Q-14 | What are the promo-code rules: stacking, first-booking definition, per-service scope, expiry, refund interaction? | M-024, M-057 | Promo table, discount engine | One code per booking, first confirmed booking only, percentage off subtotal, no cash refund of discount | Marketing | **Blocking-3** |
| Q-19 | The app shows only USD with a currency setting. Is multi-currency display in scope, and which currencies/rates? | M-043, M-053 | Money storage, FX table, rounding | Single currency (USD) at launch; selector hidden until FX is defined | Product owner | **Blocking-3** |
| Q-31 | How is the baggage add-on paid for? M-041 adds baggage to an existing booking but no payment step is drawn. | M-041, M-038 | Post-booking payment flow, add-on ledger | Charge the default instrument immediately, with a confirmation step to be designed | Product owner | Blocking-4 |

### 2.2 Bookings & inventory

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-08 | Multi-city is offered as a trip type. What is its form, leg count, pricing and ticketing rule? | M-019 | Flight search model, fare-combination logic | Out of scope for v1; hide the option | Product owner | Non-blocking |
| Q-09 | Where is the seat map, what does a seat cost, and which seats are free? Only a three-seat confirmation bar exists. | M-026 | Seat inventory, pricing, booking flow | Seats are free and optional at launch; seat map deferred | Product owner | Non-blocking |
| Q-10 | What are the passenger name/passport validation rules, and is name correction possible after ticketing? | M-023, M-039 | Traveller schema, ticket amendment policy | Latin uppercase names matching passport, ≥6 months validity; no name change after ticketing | Operations | Blocking-4 |
| Q-11 | How long is the fare hold (14:32 shown) and who configures it — platform, carrier or route? | M-024 | Hold table, expiry job | 15 minutes platform-wide, provider-overridable | Operations | Blocking-3 |
| Q-18 | No empty, error or offline state is designed for any list. What should each show? | all list screens | UI states, API error contract | Generic retry + empty-state pattern with a single CTA | Design | Non-blocking |
| Q-25 | Nine different reference formats appear (`TRW7K2`, `TW-48210`, `TRW-V-2291`, `HTL-90418`, `TR-33107`, `PAY-90121`, `P-2291`, `TW-48197`, `TW-48201`). What is the canonical scheme per entity? | M-025, M-031, M-036, M-064, M-068, D-02.3, D-03.14 | Primary keys/display IDs for every booking type | One prefix per entity + year + sequence; all formats shown are mock values | Product owner | **Blocking-3** |
| Q-27 | The admin bookings table has no visible detail screen. What does a booking row open? | D-03.3 | Admin booking detail endpoints | Read-only detail reusing the mobile booking data | Product owner | Non-blocking |
| Q-30 | The company bookings table has no visible detail screen. What does a row open? | D-04.5 | Company booking detail endpoints | Read-only detail with customer contact and service snapshot | Product owner | Non-blocking |
| Q-34 | How are no-shows, walk-ins, offline bookings and overbooking handled in the hotel portal? | D-05.4, D-05.3 | Hotel booking states, inventory locking | Not supported at launch; overbooking prevented by atomic inventory decrement | Hotel operations | Non-blocking |

### 2.3 Visas

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-12 | The visa fee is a placeholder «[حسب لوحة التحكم]». Is the fee always country+type based, are visas paid inside the app, and is nationality used for eligibility/pricing? | M-029, M-027, D-03.9 | Visa pricing, payment, eligibility model | Fee = catalogue price per country+type; payment in-app at submission; nationality not a filter | Product owner | **Blocking-3** |
| Q-13 | After documents are accepted, how is the application submitted to the embassy/authority, who records approval/rejection, and what does the traveller see? | D-04.6, M-031, M-032 | Visa state machine, outcome notifications | External submission; company records the decision; traveller notified and status shown on the tracker | Operations | **Blocking-3** |
| Q-26 | When a partner application is rejected, is a reason required? Can the same licence re-apply? What happens when the activation link expires? | D-03.6, D-02.3 | Partner onboarding states, notification content | Reason mandatory; re-apply allowed after 30 days; activation link resendable | Operations | Blocking-4 |

### 2.4 eSIM

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-15 | What does «التحقق الآن» (device compatibility) actually check, and does an incompatible device block purchase? | M-033 | Device check integration, purchase gate | Check `eSIM` support via device model API; incompatible devices can browse but not buy | Product owner | Blocking-4 |
| Q-16 | What is the exact eSIM refund window and does it end at activation, installation or first data use? | M-035, M-036 | Refund policy engine | Refundable until activation only; activation is irreversible | Operations | Non-blocking |
| Q-17 | The booking card shows «5 GB متبقية» but there is no usage screen. Is usage tracking in scope? | M-037 | Usage/balance data source, polling | Display only, refreshed on screen open; no dedicated screen in v1 | Product owner | Non-blocking |
| Q-32 | What happens when the eSIM provider API sync fails or returns partial data? | D-03.10 | Availability integrity, sync job | Keep last known good catalogue; flag as stale; never silently delist | Engineering | Non-blocking |

### 2.5 Hotels & tours

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-23 | The hotel search form is missing. What inputs exist — destination, dates, adults, children, rooms, nationality? | M-059 summary only | Hotel search API, occupancy model | Destination + dates + adults + children + rooms | Product owner | **Blocking-3** |
| Q-24 | The tour promises free cancellation within 24 h. What is the exact deadline, refund basis (full/partial) and flow? | M-068 | Tour cancellation states, refund rules | Full refund when cancelled ≥24 h before slot start; no cancellation after | Operations | Non-blocking |
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
| Q-21 | Account deletion: is there a grace period, can it be cancelled, what happens to in-flight visas and upcoming trips, and how long are records retained? | M-056, D-03.11 | Deletion job, retention policy, legal | 14-day grace period; deletion blocked while a trip is within 7 days; financial records retained 5 years | Legal + product | **Blocking-3** |
| Q-28 | What does «حظر الحساب» do — permanent ban, temporary suspension, with appeal? | D-03.13 | Account state model | Suspension until manually lifted; no appeal flow at launch | Support lead | Non-blocking |
| Q-35 | The bottom tab is labelled «جوازاتي» (my passports) on some screens and «حجوزاتي» (my bookings) on others. Which is canonical? | M-014, M-037, M-043 | Navigation labels, translation keys | «حجوزاتي» — the icon and destination are bookings | Design | Non-blocking |
| Q-39 | Only Arabic mobile layouts exist. Is an English layout in scope for v1, and does RTL/LTR mirroring apply to all screens? | M-053 language toggle | Localisation scope, layout QA | English in scope, mirroring handled by the standard RTL framework | Product owner | Non-blocking |

### 2.8 Platform operations & governance

| ID | Question | Evidence | Impact if unanswered | Working assumption | Owner | Status |
|---|---|---|---|---|---|---|
| Q-29 | Refund requests appear as notifications, and settlements have «transfer» actions, but neither workflow UI is designed. Who approves a refund, and how is a payout executed? | D-03.2, D-03.14, D-04.9 | Refund approval states, settlement execution, audit | Refund approved by super admin; payout executed manually and recorded as transferred | Finance | **Blocking-3** |
| Q-36 | The admin board header says «14 شاشات» but 15 full pages (plus 2 modals) are drawn. Which is authoritative? | board header; D-03.1 – D-03.15 | Scope confirmation only | 15 pages are authoritative; header is stale | Design | Non-blocking |
| Q-38 | Export buttons exist across the dashboards but no dedicated reports or audit-log screen is designed. Is an audit log required, and who can read it? | D-03.1, D-03.14, D-04.9, D-05.7 | Audit storage, reporting module | Audit written from day one, readable by super admin; report screens deferred | Product owner | Non-blocking |

---

## 3. Blocking summary

| Phase blocked | Questions |
|---|---|
| Phase 3 — data model | Q-01, Q-14, Q-19, Q-25, Q-12, Q-13, Q-23, Q-21, Q-29, Q-11 |
| Phase 4 — API contracts | Q-02, Q-03, Q-10, Q-26, Q-31, Q-07, Q-20, Q-15 |
| No phase blocked | Q-04, Q-05, Q-06, Q-08, Q-09, Q-16, Q-17, Q-18, Q-22, Q-24, Q-27, Q-28, Q-30, Q-32, Q-33, Q-34, Q-35, Q-36, Q-37, Q-38, Q-39 |

**10 questions block Phase 3.** They must be answered (or explicitly deferred with a decision record) before schema design starts.

---

## 4. Phase 1 inconsistency closure

| # | Phase 1 inconsistency | Resolution in Phase 2 | Question |
|---|---|---|---|
| 1 | Tab label «جوازاتي» vs «حجوزاتي» | Treated as a copy defect; bookings is the destination | Q-35 |
| 2 | Nine booking-reference formats | One reference per entity required; all captures treated as mocks | Q-25 |
| 3 | Admin header 14 vs 15 pages | 15 pages used as scope | Q-36 |
| 4 | USD shown while a currency selector exists | Single-currency assumption flagged | Q-19 |
| 5 | Visa fee placeholder | Fee modelled as catalogue-driven; payment model questioned | Q-12 |
| 6 | Support hours/phone placeholders | Channel scope questioned, placeholders marked non-shippable | Q-22 |
| 7 | `Bottom Bar.png` is a seat fragment | Seat selection marked unresolved, not a feature | Q-09 |
| 8 | `Iframe.png` is the only home capture | Treated as the authoritative home state; no behaviour lost | — (documented fact) |
| 9 | Deletion does not cancel bookings | Consequence rule captured; grace period questioned | Q-21 |
| 10 | Employee portal is a permission state | Resolved: R-16, a narrow tenant role, not a separate product | — (resolved in roles doc) |
| 11 | Cancellation figures vs payment total | Refund arithmetic blocked pending answer | Q-01 |

**Result: 9 of 11 inconsistencies need an owner decision; 2 are resolved as documentation facts (8, 10); none remain unrecorded.**

---

## 5. Phase 1 coverage-gap closure

| # | Phase 1 gap | Phase 2 handling | Question |
|---|---|---|---|
| 1 | Flight seat map | F-027 marked `U`, excluded from v1 | Q-09 |
| 2 | Hotel search form | BR-060 marked `U`; hotel search API cannot be finalised | Q-23 |
| 3 | Tour cancellation | F-071 marked `U`; promise recorded without flow | Q-24 |
| 4 | Visa payment checkout | Fee/payment model questioned | Q-12 |
| 5 | Notification destinations | Deep-link schema questioned | Q-07 |
| 6 | Support chat thread | Channel scope questioned | Q-22 |
| 7 | Review submission | Deferred; ratings treated as seeded | Q-37 |
| 8 | Multi-city flights | Option marked out of scope | Q-08 |
| 9 | eSIM usage screen | Balance display only | Q-17 |
| 10 | Admin reports / audit log | Audit required, screens deferred | Q-38 |
| 11 | Partner document re-upload after rejection | Covered by resubmission rule (BR-093); mechanics questioned | Q-26 |
| 12 | Refund processing UI | Refund approval model questioned | Q-29 |
| 13 | Payout / settlement detail | Execution model questioned | Q-29 |
| 14 | Empty / error / offline states | Generic pattern assumed, design needed | Q-18 |
| 15 | English mobile layouts | Localisation scope confirmed as a question | Q-39 |

**Result: 15 of 15 gaps recorded with an owner-facing question; 0 gaps converted into silent assumptions.**

---

## 6. Assumptions register

These are the assumptions Phase 2 used **without** a question (either because the evidence is strong or because the alternative would contradict the design). They are listed so that a reviewer can overturn any of them explicitly.

| ID | Assumption | Basis |
|---|---|---|
| A-01 | A single Laravel-backed API serves all six front-ends. | The task brief specifies a Laravel 12 API backend; the six UIs need shared data. |
| A-02 | Partner tenants are isolated at query level. | Separate portals and role names, plus D-04.6 reviewer queues. |
| A-03 | The employee portal is the company tenant with a narrow role. | D-06.x reuses the company shell and locks modules. |
| A-04 | USD is the accounting currency even if display currency changes. | Every capture uses `$`. |
| A-05 | Commission is deducted before partner settlement, not charged to the customer separately. | D-04.4 «net after 10 % commission». |
| A-06 | Visa applications are reviewed by the partner company, not by Traway staff directly. | D-04.6 and D-03.2 routes. |
| A-07 | Notifications are persisted as inbox items and pushed. | Inbox UI with read/unread states. |
| A-08 | Confirmation screens are states of the booking flow, not separate routes with new data. | Same reference and totals. |
| A-09 | Ratings/review counts are read-only to partners. | No edit affordance on any partner screen. |
| A-10 | Account deletion requires back-office processing rather than immediate hard delete. | D-03.11 queue with 14 open requests. |

---

## 7. Validation

| Check | Result |
|---|---|
| Questions registered | 39 (Q-01 – Q-39) |
| Questions with screen evidence | 39 / 39 |
| Questions with an owner | 39 / 39 |
| Blocking Phase 3 | 10 |
| Blocking Phase 4 only | 8 |
| Non-blocking | 21 |
| Phase 1 inconsistencies covered | 11 / 11 |
| Phase 1 coverage gaps covered | 15 / 15 |
| Assumptions documented | 10 |
| Unresolved item without a question ID | 0 |
