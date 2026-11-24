# Phase 2 — Workflows

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Decisions applied — awaiting approval (`APPROVE PHASE 3`)
**Inputs:** [`FEATURES-CATALOG.md`](./FEATURES-CATALOG.md), [`BUSINESS-LOGIC.md`](./BUSINESS-LOGIC.md), [`USER-ROLES-AND-PERMISSIONS.md`](./USER-ROLES-AND-PERMISSIONS.md), [`AUTHORIZATION-ARCHITECTURE.md`](./AUTHORIZATION-ARCHITECTURE.md)
**Date:** 2026-11-23 · **Decisions applied:** 2026-11-24

---

## 1. Conventions

Each workflow records: actors, trigger, preconditions, numbered main flow, alternates/exceptions, outputs, state changes, the screens that prove it, and the rules/questions attached. Steps marked **[U]** depend on an unresolved item and must not be built until answered.

Booking states used throughout (BR-076): `pending_payment → confirmed → completed`, plus `cancelled`, `refunded`, `expired`.

---

## 2. WF-01 — Registration, verification & first session

**Actors:** Traveller (R-01), Guest (R-02)
**Trigger:** first app launch
**Preconditions:** none
**Screens:** M-001, M-002, M-003, M-004, M-005, M-006, M-007, M-008, M-009, M-010, M-011, M-012, M-013, M-014

**Main flow (phone)**
1. Splash decides route: first run → onboarding, returning session → Home (M-001).
2. Three onboarding steps; skip available on any step (M-002 – M-004).
3. Login screen, phone tab, default country code +20; user enters phone (M-005).
4. Request OTP; OTP screen shows masked phone and a resend countdown (M-007).
5. Enter 4-digit code; confirmation creates the session.
6. Post-auth routing: known user → Home; new user → profile completion (M-011) then Home.
7. Optional enrichment (add phone / add passport) is offered on the success state (M-010).

**Alternate flows**
* A1 — E-mail path: e-mail tab → e-mail + password (M-006) → Home.
* A2 — Signup: e-mail signup with password policy + terms (M-008) → 6-digit e-mail code (M-009) → account ready (M-010) → optional profile completion (M-011).
* A3 — Social: Apple/Google from either tab → session; first-time users → M-011.
* A4 — Forgot password: e-mail request (M-012) → recovery code + new password (M-013) → login.
* A5 — Guest: «تصفح كضيف» → Home with restricted capabilities (WF-02).

**Exceptions**
* E1 — Wrong/expired OTP: stays on M-007; attempts limited (BR-026). Exact copy/limit unknown → Q-03.
* E2 — Resend: invalidates the previous code and restarts the countdown (BR-026).
* E3 — E-mail already registered: signup must route to login rather than leaking existence (BR-021). Screen not drawn → Q-03.

**Postconditions:** authenticated session (or guest session); onboarding flag persisted; optional profile fields saved.
**Rules:** BR-021 – BR-028.
**Open:** Q-02 (post-OTP routing), Q-03 (error/limit copy), Q-04 (profile constraints), Q-05 (logout).

---

## 3. WF-02 — Guest browsing → authenticated purchase

**Actors:** Guest → Traveller
**Trigger:** guest taps a commerce action (book, apply, buy, save)
**Screens:** M-005, M-014, M-019, M-021

**Main flow**
1. Guest browses Home, search and catalogue without an account (M-014).
2. Guest reaches a fare/room/package detail and taps the transactional CTA.
3. App interrupts with authentication (login or signup).
4. After authentication, the user resumes the exact interrupted context (selected fare/room/package).
5. Purchase continues in WF-03 / WF-08 / WF-07.

**Exceptions**
* E1 — Context loss after login is unacceptable; the design does not show the resume mechanism (Q-20).
* E2 — Guest attempts to favourite or contact support with account context → auth required.

**Postconditions:** session created, pending selection preserved.
**Rules:** BR-028.
**Open:** Q-20.

---

## 4. WF-03 — Flight booking (search → ticket)

**Actors:** Traveller
**Trigger:** «بحث عن رحلات»
**Preconditions:** authenticated; origin ≠ destination; valid dates and passenger mix
**Screens:** M-014, M-015, M-016, M-017, M-018, M-019, M-020, M-021, M-022, M-023, M-024, M-025, M-026

**Main flow**
1. Home flight card or tab opens flight search (M-019).
2. Select trip type (one-way / round trip), origin, destination (city picker M-016), dates (M-017), passengers and cabin (M-018), optional direct-only.
3. Search → results with date strip, sort and filters (M-021).
4. Optionally filter (M-020) and re-sort; result count updates.
5. Select outbound flight.
6. Round trip: select return leg from return results (M-022) with validity notice.
7. Passenger details: prefill account holder, add travellers, passport numbers, optional extra baggage (M-023). **[U]** seat selection via M-026 is not a complete flow.
8. Continue to payment: the server revalidates availability and price. **No inventory hold is created** (DEC-05).
9. Apply a coupon (optional), choose payment method, enter card if needed; the server recomputes the total.
10. Pay → provider capture → booking `confirmed`, Traway reference `TRW-FLT-…` issued; the carrier PNR is stored as an external attribute.
11. Confirmation screen with upsells to eSIM/visa (M-025); booking visible in My bookings (M-037) and in admin/partner booking tables.

**Alternate flows**
* A1 — One-way: skip step 6; passenger details follows outbound selection.
* A2 — Saved card: payment step skips card entry (M-052/M-050).
* A3 — Add a new card mid-checkout (M-050) and return.
* A4 — Multi-city: **not implemented** — no form or pricing rule (Q-08, BR-036).

**Exceptions**
* E1 — Revalidation fails before capture (sold out or fare withdrawn): no charge, return to results with an explanation (BR-042).
* E2 — Price changed during checkout: the new total must be accepted explicitly before capture (BR-043).
* E3 — Payment declined: stay on M-024; retry allowed; no duplicate charge (BR-020).
* E4 — Duplicate booking detected: block and explain (BR-046).
* E5 — Passenger passport invalid/expiring: inline warning on M-023; whether it blocks ticketing is unresolved (Q-10).
* E6 — Filters yield zero results: explicit empty state + reset (BR-039). Not designed → Q-18.

**Postconditions:** booking `confirmed`, payment captured, ticket issued, notifications emitted, partner/admin visibility.
**Rules:** BR-031 – BR-049, BR-015, BR-020.
**Open:** Q-08, Q-09, Q-10, Q-18, Q-43 (countdown replacement).

---

## 5. WF-04 — Flight post-booking (e-ticket, reschedule, baggage, cancellation)

**Actors:** Traveller
**Trigger:** opening a booking
**Screens:** M-037, M-038, M-039, M-040, M-041, M-042

**Main flow (happy path)**
1. My bookings lists upcoming trips with countdown and add-on status (M-037).
2. Open booking → detail with flight/passengers/payment tabs (M-038).
3. Actions available: e-ticket, reschedule, add baggage, cancel.

**Branch A — E-ticket**
4a. Open e-ticket, choose passenger, view boarding pass with seat/gate/baggage and QR (M-039).
5a. Download PDF or add to wallet; share.

**Branch B — Reschedule**
4b. Open reschedule, choose new dates from the fare calendar (M-040).
5b. System computes change fee + fare difference; user confirms.
6b. Booking is re-issued with the new itinerary and PNR rules per carrier.

**Branch C — Baggage**
4c. Open baggage, choose 23 kg / 32 kg / sports equipment and quantities (M-041).
5c. Total updates; add to booking; payment for the add-on is implied but not drawn (Q-31).

**Branch D — Cancellation**
4d. Open cancel; review the refund amount, any gateway-retained fee, the refund SLA and the irreversibility notice (M-042).
5d. Select optional reason, type «إلغاء», confirm.
6d. Booking → `cancelled`; refund → `refunded` when the provider confirms; funds to the original instrument (7–14 days). In-policy cancellations refund automatically; out-of-policy cases route to WF-14 (DEC-10).

**Exceptions**
* E1 — Departure already passed: reschedule and cancellation disabled (BR-044).
* E2 — Non-refundable fare: cancellation allowed only if taxes are refundable, with explicit copy (BR-038).
* E3 — Baggage over carrier limit: stepper capped (BR-045).
* E4 — Fee display: the refund is the captured amount minus any amount retained by the gateway; Figma's figures are illustrative (DEC-01). Copy is Q-40.
* E5 — Exceptional refund (outside policy): requires an authorised approver, not the customer flow (Q-51).

**Postconditions:** booking state changed, payment/refund recorded, customer notified, partner/admin tables updated.
**Rules:** BR-038, BR-044, BR-045, BR-076 – BR-079, BR-111.
**Open:** Q-10, Q-31, Q-40, Q-51, Q-52.

---

## 6. WF-05 — Visa application

**Actors:** Traveller
**Trigger:** visa card on Home or visa tab
**Screens:** M-014, M-027, M-028, M-029, M-030, M-031, M-032

**Main flow**
1. Visa countries list with search and region chips (M-027).
2. Select country → visa types with duration, entry type, document count (M-028).
3. Select type → detail: validity, fee (from the active offering, never hard-coded), expected processing duration, required documents and accepted formats (M-029).
4. «ابدأ رفع المستندات» → upload screen; upload each document, see progress (3 of 5, 60 %), optionally reuse a saved document (M-030, M-032).
5. Save; the application persists as a draft while documents remain.
6. When all mandatory documents are present, submit and pay the visa fee through Traway (DEC-06). The fee is snapshotted on the application.
7. Success screen with request number `TRW-VIS-…`, the offering's processing expectation, and the customer-visible status (M-031).
8. Tracker shows per-application checklist, status and any missing documents (M-032).

**Alternate flows**
* A1 — Resume a draft from My bookings / account («2 missing») → tracker → upload screen.
* A2 — Reuse saved passport/photo from a previous application (BR-053).
* A3 — Multiple concurrent applications (UAE 5/5 complete + another in progress) are supported by the tracker.

**Exceptions**
* E1 — Wrong format or >10 MB: rejected before upload (BR-050).
* E2 — Missing mandatory document: submission blocked with the remaining count (BR-051).
* E3 — Payment step: required by DEC-06 but no payment screen is drawn; the fee checkout is a design deliverable (Q-44 covers withdrawal refundability).
* E4 — Offering deactivated: cannot be applied for (BR-054). Eligibility does not depend on nationality (DEC-06).

**Postconditions:** application `submitted` and fee captured; queue entry for the company visa desk; traveller notification.
**Rules:** BR-007, BR-050 – BR-055.
**Open:** Q-44, Q-46.

---

## 7. WF-06 — Visa review & processing (company → platform → outcome)

**Actors:** Company visa employee (R-09), Restricted employee (R-16), Company owner (R-07), Platform admin (R-03)
**Trigger:** new visa application submitted by a customer
**Screens:** D-04.1, D-04.6, D-06.1, D-06.3, D-03.2, D-03.3

**Main flow**
1. New request appears in the company queue and in “required today” (D-04.1).
2. Employee opens the request: applicant data, document list, review progress (1 of 5 completed) (D-04.6).
3. Accept or reject each document; rejected documents require re-upload by the applicant.
4. «قبول كل المستندات» bulk-accepts; «التالي» moves to the next applicant.
5. The application is already `Submitted` (WF-05). When all mandatory documents are accepted, the employee moves it to `Under Review` (DEC-07).
6. Submission to the external authority happens **outside Traway**; the employee may store the authority's reference and any evidence.
7. The employee records each status change: `Documents Required` (with the missing/rejected list) → `Processing` → `Approved` or `Rejected`. A rejection stores a reason category plus a note.
8. The customer is notified of every visible status change and sees it on the tracker.

**Status model (canonical, DEC-07):** `Submitted → Under Review → Documents Required → Processing → Approved / Rejected`. Internal document-level mechanics stay internal; only these states are customer-visible.

**Alternate flows**
* A1 — Restricted employee works only assigned requests and can request extra permission from the owner (D-06.1).
* A2 — Platform admin sees the request in booking/visa oversight and in notifications (D-03.2, D-03.3), with the corresponding platform permission; no general audit entry is written (G0-DEC-01).

**Exceptions**
* E1 — Document rejected → application returns to `Documents Required`; cannot advance (BR-095).
* E2 — Applicant re-uploads → notification to the assigned reviewer (D-04.2 event «passport re-upload»).
* E3 — Application rejected: the customer sees `Rejected` and the reason category; the re-application policy and reason wording are Q-46.

**Postconditions:** documents verified; status history recorded; application state visible to traveller, company and admin.
**Rules:** BR-052, BR-095, BR-104, BR-115.
**Open:** Q-46.

---

## 8. WF-07 — eSIM purchase & activation

**Actors:** Traveller
**Trigger:** eSIM card on Home or eSIM tab
**Screens:** M-014, M-033, M-034, M-035, M-036, M-037

**Main flow**
1. eSIM countries with device-compatibility check and country/region mode (M-033).
2. Select country → packages with data/validity/price; one package selected (M-034).
3. Checkout: plan facts, payment method, promo, tax-inclusive total (M-035).
4. Pay → order `confirmed`, activation token generated.
5. QR screen with order number, QR, manual LPA string, install-on-this-device (M-036).
6. Order appears in My bookings with remaining data (M-037).

**Alternate flows**
* A1 — Promo applied at checkout (BR-015).
* A2 — Re-open QR from My bookings (M-037).

**Exceptions**
* E1 — Device not eSIM-capable: compatibility check should block purchase (rule not drawn → Q-15).
* E2 — Payment failed: no order, no token.
* E3 — Token leak/reuse: single-use enforcement (BR-058).
* E4 — Refund request after activation: refused per displayed policy (BR-059, Q-16).

**Postconditions:** eSIM order fulfilled; usage balance tracked.
**Rules:** BR-008, BR-056 – BR-059.
**Open:** Q-15, Q-16, Q-17.

---

## 9. WF-08 — Hotel booking

**Actors:** Traveller; Hotel partner (downstream)
**Trigger:** hotels tab / Home hotel module
**Screens:** M-059, M-060, M-061, M-062, M-063, M-064, M-037

**Main flow**
1. Search with city/destination, check-in, check-out, adults, children and rooms (DEC-08); results show 186 properties with filter chips (M-059).
2. Refine with filters (M-060) or switch to map (M-061).
3. Open hotel detail: gallery, rating, amenities, room types and prices for the stay, cancellation/breakfast badges (M-062).
4. Select room type → booking screen: stay summary, lead guest, special requests, payment timing (pay now −5 % / pay at hotel), taxes and total (M-063).
5. Pay (or reserve with pay-at-hotel if allowed) → confirmation `confirmed`.
6. Confirmation shows `HTL-90418`, dates, call hotel / voucher / directions, upsell to tours (M-064).
7. Reservation is delivered to the hotel portal; voucher e-mail sent.

**Alternate flows**
* A1 — Edit lead guest → traveller editor (M-045), return to booking.
* A2 — Favourite the hotel (M-054) before booking.
* A3 — Pay at hotel: no capture at booking; amount due at property per rate policy.

**Exceptions**
* E1 — No availability for the full stay: room type not selectable (BR-066).
* E2 — Occupancy exceeds room limit: require another room type (BR-063).
* E3 — Stop-sell on a night: booking rejected (BR-106).
* E4 — No hotel search form is drawn; the six required inputs are fixed by DEC-08 and the form is a design deliverable. Map search uses the stored latitude/longitude (BR-060, BR-065).

**Postconditions:** booking `confirmed`, hotel notified, voucher available, settlement accrues.
**Rules:** BR-009, BR-060 – BR-068, BR-106.
**Open:** Q-49 (multi-property scope, non-blocking).

---

## 10. WF-09 — Tour booking

**Actors:** Traveller; Company content manager (catalogue origin)
**Trigger:** tours tab / Home tour module
**Screens:** M-065, M-066, M-067, M-068, M-037

**Main flow**
1. Tours catalogue with search, category chips and featured programme (M-065).
2. Tour detail: rating, duration, guide, languages, group size, day-by-day programme, participant stepper (M-066).
3. Choose date → booking screen with date strip availability, departure time slot, pickup location, price for participants, pay (M-067).
4. Payment → confirmation with QR voucher, `TR-33107`, pickup instructions, free-cancellation notice, contact guide, add to calendar (M-068).

**Alternate flows**
* A1 — Add to calendar / contact guide from confirmation.
* A2 — View booking in My bookings.

**Exceptions**
* E1 — Slot full or date past: not offering (BR-075).
* E2 — Participants exceed remaining seats or group max: capped (BR-071).
* E3 — Pickup missing when required: payment blocked (BR-072).
* E4 — Cancellation after the 24 h window: no flow exists (F-071, Q-24).

**Postconditions:** booking `confirmed`; seats decremented; company sees the booking; settlement accrues.
**Rules:** BR-010, BR-069 – BR-075, BR-090.
**Open:** Q-24.

---

## 11. WF-10 — Partner onboarding, activation & team setup

**Actors:** Public applicant, Platform admin (R-03), Partner owner (R-07/R-12)
**Trigger:** «طلب فتح حساب» on the portal
**Screens:** D-01.1, D-01.2, D-02.1, D-02.2, D-02.3, D-03.2, D-03.4, D-03.5, D-03.6, D-04.7, D-05.5

**Main flow**
1. Applicant opens the partner login and chooses «طلب فتح حساب» (D-01.1).
2. Company form or hotel form: identity, contact, licence documents, terms (D-02.1/D-02.2).
3. Submit → acknowledgment with request number `P-2291` and a two-business-day expectation (D-02.3).
4. Admin receives a join notification and reviews the request: applicant data, requested services, commission terms, document verification (D-03.6).
5. Accept → partner tenant created; owner account created; activation link e-mailed (inferred mechanics, BR-083).
6. Owner activates the account and logs into the correct portal (D-01.1 routing rule BR-080).
7. Owner invites team members, creates organization roles (or clones a template), assigns them, and may grant bounded per-employee permissions (D-04.7/D-04.8 for companies, D-05.5 for hotels; AUTHORIZATION-ARCHITECTURE §6–§7).
8. Owner completes company/hotel settings and publishes (hotel publication requires approved documents, BR-110).

**Alternate flows**
* A1 — Admin pre-creates a partner instead: add partner with commission, services and owner invite (D-03.5).
* A2 — Rejection: applicant notified with a reason (reason field not drawn, Q-26).
* A3 — Applicant resubmits («تقديم طلب آخر»).

**Exceptions**
* E1 — Missing document: request stays pending verification; admin cannot accept (BR-081).
* E2 — Duplicate application for the same licence: not handled in design (Q-26).
* E3 — Activation link expired: resend mechanism not drawn (Q-26).

**Postconditions:** tenant active, owner authenticated, roles assignable.
**Rules:** BR-080 – BR-083, BR-101.
**Open:** Q-26.

---

## 12. WF-11 — Catalogue & pricing administration → mobile display

**Actors:** Platform content manager (R-05), Platform super admin (R-03), Traveller (consumer)
**Trigger:** price/requirement change
**Screens:** D-03.9, D-03.10, M-027, M-028, M-029, M-034, M-035

**Main flow**
1. Content manager opens the visa catalogue (D-03.9).
2. Add/edit a country, its visa types (duration, entry type, price, documents with mandatory toggles).
3. Toggle a country/type active or inactive.
4. Save → the mobile catalogue serves the new values on next fetch.
5. Similarly, eSIM packages: provider markup, per-package cost/sale/margin, availability toggle, API sync (D-03.10).
6. Mobile detail screens render the stored fee and requirement list (M-029, M-034).

**Alternate flows**
* A1 — Add country modal from the catalogue.
* A2 — Deactivate a type: it disappears from the mobile list but existing applications remain valid.

**Exceptions**
* E1 — Missing price: the mobile placeholder proves the fee is administered here; Platform Admin or the publishing company sets it (DEC-06).
* E2 — Provider sync failure: availability must not silently change (Q-32).

**Postconditions:** published catalogue values; audited change (BR-107).
**Rules:** BR-007, BR-008, BR-054, BR-107.
**Open:** Q-32.

---

## 13. WF-12 — Tour authoring, review & publication

**Actors:** Company content manager (R-11), Company owner (R-07), Platform admin (R-03)
**Trigger:** «+ برنامج جديد»
**Screens:** D-04.1, D-04.3, D-04.4, D-03.1, M-065, M-066

**Main flow**
1. New tour from overview or tours list (D-04.1/D-04.3).
2. Editor: basic info, pricing (price, child price, single supplement, net after 10 %), schedule slots with seats, includes checklist, gallery, day-by-day itinerary (D-04.4).
3. «حفظ وإرسال للمراجعة» — all mandatory fields and ≥1 future slot required (BR-091).
4. Status → in review; platform sees it in the approvals queue (D-03.1) and reviews content.
5. Approve → published; appears in mobile catalogue and search (D-04.3 → M-065).
6. Reject → status rejected with reason; partner edits and resubmits (BR-093).

**Alternate flows**
* A1 — Save as draft without submitting.
* A2 — Cancel a submission back to draft.
* A3 — Expire a tour: it stops selling but remains for history.

**Exceptions**
* E1 — Incomplete mandatory content: submission blocked with the missing fields.
* E2 — No future slot: submission blocked (BR-091).
* E3 — Documents expired: submission blocked (BR-101).

**Postconditions:** tour published/ rejected; customer catalogue updated.
**Rules:** BR-069, BR-090 – BR-094.
**Open:** Q-33 (review SLA, reviewer assignment).

---

## 14. WF-13 — Hotel rate, stay & settlement operations

**Actors:** Hotel owner (R-12), Bookings manager (R-14), Reception (R-13), Hotel accountant (R-15), Platform finance (R-04)
**Trigger:** daily operations
**Screens:** D-05.1, D-05.3, D-05.4, D-05.5, D-05.7, D-03.14, M-059, M-063, M-064

**Main flow**
1. Owner/manager maintains room types and the per-day price/availability calendar; applies stop-sell or bulk ±$10 (D-05.3).
2. Availability and prices feed the mobile results (M-059) and detail (M-062).
3. A customer booking arrives in the hotel bookings table with status upcoming (D-05.4) and in the overview KPIs (D-05.1).
4. On the arrival date, reception checks the guest in; later checks out (D-05.4).
5. Nightly revenue, ADR and occupancy update on the overview.
6. The stay accrues to the settlement period; the accountant reviews reports (D-05.7).
7. Platform finance computes commission and partner due and transfers the settlement (D-03.14).

**Alternate flows**
* A1 — Cancellation: booking moves to cancelled; refund per rate policy; the cancellation rate KPI changes (D-05.2 event).
* A2 — No-show: not designed (Q-34).
* A3 — Walk-in / offline booking: not designed (Q-34).

**Exceptions**
* E1 — Stop-sell applied to a date with existing bookings: new bookings blocked only (BR-106).
* E2 — Overbooking from a race condition: not addressed in the design (Q-34).
* E3 — Document expiry: payouts blocked (BR-101).

**Postconditions:** stay completed, revenue reported, settlement transferred.
**Rules:** BR-061 – BR-068, BR-096 – BR-103, BR-106, BR-110.
**Open:** Q-34.

---

## 15. WF-14 — Support, refund request & account deletion

**Actors:** Traveller, Platform support (R-06), Platform super admin (R-03)
**Trigger:** customer needs help / wants to leave
**Screens:** M-055, M-042, M-056, D-03.2, D-03.11, D-03.13

**Main flow (support)**
1. Customer opens customer service: WhatsApp, phone callback, working hours (M-055).
2. Issue resolved externally, or escalated to a refund request (notification type «refund request» on D-03.2).
3. In-policy refunds execute automatically; exceptional refunds require an authorised approver (platform finance by default, Q-51), are audited, and have no fixed monetary threshold (DEC-10).
4. Customer sees the refund in payment history with status `processing` → `refunded` (M-051).

**Main flow (deletion)**
1. Customer opens delete account, reviews consequences (documents deleted, travellers/favourites lost, bookings not cancelled) (M-056).
2. Selects an optional reason, types «حذف», confirms.
3. Request enters the admin queue (14 open) (D-03.11) and enters a **7-day grace period** during which the customer may cancel it (DEC-09).
4. Deletion is blocked while the user has upcoming trips, a visa application under review, or a pending refund (DEC-09).
5. After the grace period, the admin processes the request: personal data is anonymised, documents deleted, financial and booking records retained (BR-109).
6. Sessions revoked; account cannot authenticate (BR-030).

**Alternate flows**
* A1 — Customer cancels the deletion request during the grace period (supported by DEC-09; the cancel control is a design deliverable).
* A2 — Blocked account by admin (D-03.13) — customer sees an auth error, no UI drawn.

**Exceptions**
* E1 — Upcoming trip, visa under review or pending refund: deletion is blocked until the condition clears (DEC-09).
* E2 — Exceptional refund refused: the approver records a reason; the customer is informed (Q-51).
* E3 — Support hours/phone are placeholders (Q-22) — WF depends on real values.

**Postconditions:** ticket resolved or refund issued; account anonymised/blocked.
**Rules:** BR-019, BR-029, BR-030, BR-107, BR-108, BR-109.
**Open:** Q-22, Q-40, Q-47, Q-51.

---

## 16. WF-15 — Role, permission & employee administration

**Actors:** Platform admin (R-03), Company owner (R-07), Hotel owner (R-12), Service owner (R-17)
**Trigger:** create or change a role, invite an employee, adjust access within the permitted organization, service and resource scopes
**Preconditions:** actor holds the access-control permissions and an equal-or-broader ceiling
**Screens:** D-03.15, D-04.7, D-04.8, D-05.5, D-06.1

**Main flow**
1. Platform admin opens D-03.15 and creates or edits a **platform role** from the platform permission catalog. Tenant roles/permissions may also be assigned through `platform.organization_access.manage`, which follows the no-audit rule in G0-DEC-01.
2. Owner opens Team (D-04.7 for a company, D-05.5 for a hotel) and invites an employee with a role and, when the organization uses service lines, explicit service scopes and, where required, resource scopes.
3. Owner optionally clones a seeded template into a **custom organization role** (D-04.8) containing only permissions the owner holds.
4. Owner optionally adds a **bounded direct grant** to one employee for one extra capability, visible as an exception.
5. The system validates every grant against the grantor's ceiling (BR-084, BR-122) and rejects escalation instead of trimming it silently.
6. Invitation is sent; on acceptance the employee receives the resolved permission set for that membership.
7. Any later change increments the membership version; the new set applies on the next request (BR-124) and the change is audited.

**Alternate flows**
* A1 — Service owner performs steps 2–4 only inside their service scope; a broader scope or permission is rejected.
* A2 — Ownership transfer between two active members is atomic (BR-123); no general audit entry is written (G0-DEC-01).
* A3 — Suspension or removal revokes the membership's sessions immediately; assigned records are reassigned or flagged (BR-125).

**Exceptions**
* E1 — Grant exceeds the grantor's ceiling → rejected with an explicit authorisation error.
* E2 — Platform permission attached to an organization role (or the reverse) → rejected (BR-119).
* E3 — Role deleted while members still hold it → blocked until reassignment (RA-07).
* E4 — Last active owner deactivated, removed or demoted → blocked (BR-123).

**Postconditions:** effective permissions updated, membership version incremented, audit entry written, sessions adjusted.
**Rules:** BR-084, BR-085, BR-087 – BR-089, BR-111, BR-119 – BR-125.
**Open:** Q-41, Q-48, Q-49, Q-50 (all non-blocking).

---

## 17. Workflow coverage

| Workflow | Applications | Screens used | Features |
|---|---|---|---|
| WF-01 | MOB | M-001 – M-014 | F-001 – F-010, F-012 |
| WF-02 | MOB | M-005, M-014, M-019, M-021 | F-003, F-028 |
| WF-03 | MOB | M-014 – M-026 | F-014 – F-030 |
| WF-04 | MOB | M-037 – M-042 | F-044 – F-049 |
| WF-05 | MOB | M-027 – M-032 | F-031 – F-038 |
| WF-06 | MOB, COM, EMP, ADM | M-032, D-04.6, D-06.1, D-03.2 | F-037, F-096, F-108 |
| WF-07 | MOB | M-033 – M-037 | F-039 – F-043 |
| WF-08 | MOB, HTL | M-059 – M-064, D-05.4 | F-061 – F-066, F-104 |
| WF-09 | MOB, COM | M-065 – M-068, D-04.5 | F-067 – F-071, F-095 |
| WF-10 | PUB, ADM, COM, HTL | D-01.1, D-02.1 – D-02.3, D-03.6, D-04.7, D-05.5 | F-072 – F-077, F-097, F-105 |
| WF-11 | ADM, MOB | D-03.9, D-03.10, M-027 – M-035 | F-084, F-085, F-033 |
| WF-12 | COM, ADM, MOB | D-04.3, D-04.4, D-03.1, M-065 | F-093, F-094, F-078 |
| WF-13 | HTL, ADM, MOB | D-05.1, D-05.3, D-05.4, D-05.7, D-03.14 | F-101 – F-107, F-089 |
| WF-14 | MOB, ADM | M-055, M-042, M-056, D-03.2, D-03.11, D-03.13 | F-049, F-057, F-058, F-086, F-088 |
| WF-15 | ADM, COM, HTL, EMP | D-03.15, D-04.7, D-04.8, D-05.5, D-06.1 | F-090, F-097, F-098, F-105, F-108, F-112 |

**Coverage check:** 15 workflows; every application acts in at least one; every P0 feature belongs to at least one workflow; 79 of 108 screens appear inside a workflow. The remaining 29 are supporting or administrative states that are entered from a workflow rather than forming one, and all are traced to features in `FEATURES-CATALOG.md` §3:

* **Account & settings states not stepped through by a workflow:** M-043 – M-054, M-057, M-058 (account hub, travellers, passport scan, profile edit, notifications, terms, cards, payment history, settings, favourites, support detail, offers, destinations). They are reached from Home, account or a booking rather than being part of a purchase flow.
* **Back-office states not stepped through by a workflow:** D-01.2, D-03.3 – D-03.5, D-03.7, D-03.8, D-03.12, D-04.1, D-04.2, D-04.9, D-04.10, D-05.2, D-05.6, D-06.2, D-06.3 (notification inboxes, directories, partner details, settings, read-only booking view). They are supporting screens of the platform/tenant administration workflows.

No screen is untraced; the difference is only whether a screen is a workflow step or a supporting state.
