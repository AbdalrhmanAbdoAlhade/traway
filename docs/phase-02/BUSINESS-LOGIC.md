# Phase 2 — Business Logic

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Complete — awaiting approval (`APPROVE PHASE 2`)
**Inputs:** [`FEATURES-CATALOG.md`](./FEATURES-CATALOG.md), [`../phase-01/UI-SCREENS-INVENTORY.md`](../phase-01/UI-SCREENS-INVENTORY.md)
**Date:** 2026-11-23

---

## 1. How to read this document

118 rules, grouped by domain. Each rule carries:

* **ID** — `BR-nnn`, referenced from features and workflows.
* **Rule** — the statement to implement. Formulas are given where the design exposes arithmetic.
* **Evidence** — the screen(s) that prove or imply it.
* **Tag** — `C` confirmed by pixels, `I` inferred (design requires it but does not draw it), `U` unresolved (must be answered before implementation).

Rules marked `I` are the only place where Phase 2 adds mechanics that the UI does not show. Every `I` rule is mirrored by a question in `OPEN-QUESTIONS.md`. Rules marked `U` are **not** to be implemented until answered.

### Global precedence

1. Server-side validation always wins over client-side validation.
2. A price, fee or eligibility value is never accepted from the client; it is recomputed from the catalogue or inventory at request time.
3. Any rule that affects money, entitlement or access is enforced in the API, not by hiding UI.
4. Where two screens disagree (see Phase 1 §7), no rule is written until the conflict is resolved; the conflict appears as `U`.

---

## 2. Pricing, currency & promotions

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-001 | All monetary values are stored and computed in a single base currency (USD is the only currency shown) and displayed with 2 decimals. A currency setting exists, so conversion is a display concern, not a storage concern. | M-024, M-043, M-053, all dashboards | C |
| BR-002 | If non-USD display is enabled, the conversion rate is captured once per booking and locked at payment; subsequent rate changes never alter a completed booking. | M-053 currency selector; M-024 | I |
| BR-003 | A flight price is composed of base fare + taxes + carrier/airport fees; each component is summed into the total shown before payment. Provider-level component breakdown is not drawn. | M-020, M-021, M-024 | C |
| BR-004 | The price displayed on a result card is the total for the whole passenger mix, not per person, unless the card explicitly says «للفرد». | M-021 (cards), M-024 (total $511 for 3 pax) | C |
| BR-005 | Service/platform fees, if charged, are itemised in the payment breakdown; no fee may appear for the first time on the payment screen without an itemised line. | M-024 breakdown | I |
| BR-006 | A price is only guaranteed during an active inventory hold. After the hold expires, the price must be re-quoted before payment. | M-024 timer | I |
| BR-007 | Visa fees are administered in the admin catalogue per country and visa type and served to the app; the app never hard-codes a fee. The mobile detail screen is a rendered view of that catalogue row. | M-029 «[حسب لوحة التحكم]»; D-03.9 | C |
| BR-008 | eSIM sale price per package is set as provider cost + markup (admin shows 35 % markup and per-package cost/sale/margin); the customer price is the stored sale price, not a runtime markup. | D-03.10; M-034, M-035 | C |
| BR-009 | Hotel total = nightly room price × number of nights + taxes/fees, for the selected room type and occupancy; the result screen and the checkout must show the same total. | M-059 (per night + total), M-062 (7 nights), M-063 ($958) | C |
| BR-010 | Tour total = per-person price × paying participants (+ child price where applicable); the detail and checkout totals must match. | M-066 (2 participants), M-067 | C |
| BR-011 | Partner-facing net amounts exclude platform commission; customer-facing prices include it. The two are never shown to the same audience as if they were equal. | D-04.4 «net after 10 %», D-03.7/8 commission | C |
| BR-012 | Rounding is half-up to 2 decimals at the line level; the sum of rounded lines must equal the displayed total (no rounding drift). | M-024, M-063 | I |
| BR-013 | Changing any priced input (dates, passenger count, room type, participants, extras) invalidates the previous quote and forces recomputation. | M-017, M-018, M-040, M-041, M-063, M-067 | C |
| BR-014 | A booking is settled in exactly one currency; mixed-currency carts are not permitted. | implied by M-024 | I |
| BR-015 | At most one promo code may be applied per booking. The code is validated server-side for: existence, active state, expiry, minimum spend, service scope, and first-booking-only condition; the discount is recalculated by the server, never sent by the client. `TRAWAY10` = 10 % off the first booking, applied to the eligible subtotal (−$24 in the capture). | M-024, M-057 | C |

## 3. Payments, instruments & refunds

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-016 | Card data is tokenised by the payment provider. Traway stores only brand, last 4 digits, expiry month/year and the token; CVV is never stored. | M-050 «PCI note», M-052 **** 1190 | C |
| BR-017 | A customer has exactly one default payment instrument; setting a new default demotes the previous one atomically. | M-050 set-default, M-052 default Visa | C |
| BR-018 | Enabled methods are configuration-driven: card, e-wallet, Apple Pay are shown; a method not enabled for the market must not appear. | M-024, M-035 | C |
| BR-019 | Refunds are issued to the original instrument only; a refund cannot be redirected to another card or wallet. | M-042 «إلى وسيلة الدفع الأصلية» | C |
| BR-020 | Payment submission is idempotent per checkout session: a repeated or double-tapped submission must not create a second charge or a second booking. | M-024 CTA pattern | I |
| BR-021 | Password-recovery and signup responses are neutral: the same message is returned whether or not the e-mail exists, preventing account enumeration. | M-012, M-008 | I |
| BR-022 | Password policy is enforced server-side: minimum length, at least one lowercase, one uppercase, one digit, one symbol — the four checks the UI visualises. Exact minimum length is not drawn (`Q-03`). | M-008, M-013 | C |
| BR-023 | Changing e-mail or phone marks the new value unverified and requires a new verification code before it becomes the login identity. | M-046 verified badge, M-053 | I |
| BR-024 | OCR-extracted passport data is presented for user confirmation and is never written to the profile without an explicit save; low-confidence fields fall back to manual entry. | M-047, M-045 | C |
| BR-025 | Terms and privacy acceptance is versioned and timestamped per user; a material version change requires re-acceptance before the next transaction. | M-049 last-updated, M-008 checkbox | C |
| BR-026 | OTP codes are single-use, time-limited and attempt-limited; resend invalidates the previous code. The visible countdown (00:42 / 00:58) governs the resend gate, not code validity. | M-007, M-009 | C |
| BR-027 | Every authenticated request is bound to one principal and one device session; session invalidation (logout everywhere) revokes all refresh tokens. Logout is not drawn (`Q-05`). | all authenticated screens | I |
| BR-028 | Guest mode is read-only with respect to commerce: browsing and search are allowed, while any booking, visa, eSIM purchase or profile mutation requires authentication. Exact guest limits unresolved. | M-005 «تصفح كضيف» | I |
| BR-029 | Account deletion removes personal data, saved documents, travellers and favourites, but does **not** cancel existing bookings; deletion is blocked or deferred while a trip is in progress per the warning shown. | M-056 | C |
| BR-030 | A blocked or suspended account cannot authenticate or transact on any application; active sessions are revoked. | D-03.13 «حظر الحساب» | I |

## 4. Flight search, fares & filters

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-031 | Departure date ≥ today (in the user's timezone); return date, when present, ≥ departure date. Same-day return is allowed. | M-017, M-019 | C |
| BR-032 | Passenger rules: adults ≥ 1; total passengers ≤ 9; infants ≤ adults; children aged 2–11 counted as children at booking time, infants < 2; each infant occupies an adult's lap unless a seat is purchased (seat rule unresolved). | M-018 | C |
| BR-033 | Origin and destination must differ; swapping is a UI convenience that must not bypass this check. | M-019 swap | C |
| BR-034 | The selected cabin must be available on every leg; inventory is queried per cabin, not per aircraft. | M-018, M-021 | I |
| BR-035 | A round-trip booking applies the same passenger mix and cabin to both legs; changing the mix after leg selection requires re-pricing both legs. | M-021 → M-022 → M-023 | C |
| BR-036 | Multi-city is offered as a trip type but no form or pricing rule exists in the design; it must not be partially implemented. | M-019 «متعددة» | U |
| BR-037 | Sorting is deterministic: cheapest = total price ascending, fastest = total duration ascending, direct = fewest stops then price. Ties break by departure time then flight number. | M-021 sort control | I |
| BR-038 | Refundability is a per-fare attribute displayed on the card; a non-refundable fare can still be cancelled only if the carrier policy allows taxes-only refund, which must be stated before confirmation. | M-021 «قابل للاسترداد», M-042 | C |
| BR-039 | Filters combine with AND semantics; price range and airline selection cannot silently exclude all results — an explicit empty state with a reset action is required. | M-020 (12 offers), M-024 | C |
| BR-040 | The date strip fare is per passenger in the selected cabin; selecting a date re-runs the search and resets filters that no longer apply. | M-021 date strip | I |

## 5. Holds, checkout & ticket issuance

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-041 | Entering payment creates an inventory hold with a visible countdown (14:32 in the capture). The hold duration is configuration, not a client constant. | M-024 | C |
| BR-042 | When the hold expires: seats are released, the checkout session becomes invalid, and payment is refused with a re-quote path. | M-024 timer | I |
| BR-043 | If the fare changed while the user was on payment, the new total must be shown and explicitly accepted before charging; silent repricing is forbidden. | M-024 | I |
| BR-044 | Reschedule is permitted only before departure of the affected leg. Charge = change fee + (new fare − original fare); the difference may be zero but may not be negative unless the fare rules allow residual credit. | M-040 (+$65, total 135) | C |
| BR-045 | Baggage add-ons are limited by carrier allowance and a maximum piece/weight per passenger; the stepper must stop at the carrier limit. Prices shown: 23 kg $24, 32 kg $35, sports equipment $19. | M-041, M-023 (+$19) | C |
| BR-046 | The same passenger must not hold two overlapping bookings on overlapping flight segments; a duplicate-booking check runs before payment. | inferred from M-023/M-024 | I |
| BR-047 | Name corrections after ticketing follow carrier rules (usually not permitted); the design shows no edit path after confirmation. Policy unresolved. | M-025 → M-038 | U |
| BR-048 | Seat selection pricing, free-seat entitlement and availability are undefined; only a three-seat confirmation bar exists. | M-026 | U |
| BR-049 | A ticket is issued only after the payment provider confirms capture. The PNR is generated once and is immutable; the same PNR appears in booking details, e-ticket and partner booking tables. | M-025 `TRW7K2`, M-038, M-039 | C |

## 6. Visa applications & documents

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-050 | Accepted document formats are PDF, JPG and PNG with a per-file maximum of 10 MB; a document that fails either check is rejected before upload. | M-029, M-030 | C |
| BR-051 | A visa request can be submitted only when every mandatory document is present and accepted; optional documents never block submission. | M-030 (3 of 5, 2 remaining), M-032 | C |
| BR-052 | The displayed review SLA (24–48 h) is an expectation shown to the customer; whether it is contractual or merely informational is unresolved. | M-031 | C |
| BR-053 | A reused saved document is attached only if it belongs to the same applicant, has not expired, and matches the document type required by the application. | M-032 reusable documents | I |
| BR-054 | The visa fee rendered on the detail screen comes from the active catalogue row for that country + visa type; a deactivated type cannot be applied for. | M-029; D-03.9 toggles | C |
| BR-055 | Eligibility rules by applicant nationality (which nationalities may apply for which visa) are not shown anywhere; the catalogue exposes country/type without eligibility constraints. | M-027 – M-029; D-03.9 | U |

## 7. eSIM

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-056 | Exactly one eSIM package is purchased per order; adding a second package creates a second order, never a merged line item. | M-034, M-035 | C |
| BR-057 | The eSIM price shown at checkout is the tax-inclusive total ($14 for the 5 GB package in the capture) and must not change between package selection and payment. | M-034, M-035 | C |
| BR-058 | The activation token is generated per order, is single-use, and is bound to that order. The QR code and the manual `LPA:1$…` string encode the same token; revealing one must not invalidate the other. | M-036 | C |
| BR-059 | The displayed refund policy governs refunds: a plan that has been activated is not refundable, while an unactivated plan may be refundable within the stated window. Exact window and provider behaviour unresolved. | M-035 (refund policy), M-036 | C |

## 8. Hotels

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-060 | A hotel search requires destination, check-in, check-out and occupancy (adults, children, rooms). The results screen only proves the summary line, so the input form's exact fields remain unresolved. | M-059 summary; form absent | U |
| BR-061 | Availability is tracked per room type per date; a room type is sellable for a stay only when every night in the range has at least one unit available. | M-062, M-063, D-05.3 | I |
| BR-062 | Hotel total = sum of nightly rates for the selected room type across the stay + taxes/fees; the same total is shown on detail, checkout and confirmation. | M-059, M-062, M-063 | C |
| BR-063 | Occupancy limits per room type (adults/children) are enforced; exceeding them requires selecting another room type or an additional room. Exact limits per type are not drawn. | M-059 (2 adults), M-063 | I |
| BR-064 | Cancellation terms are a per-rate attribute (free cancellation until a deadline, non-refundable, pay-at-hotel). The badge shown on the card must match the policy applied at cancellation time. | M-059 badges, M-062, M-063 | C |
| BR-065 | The map view queries hotels within the current viewport bounds, returns only bookable properties, and clusters or limits pins by zoom. | M-061 | I |
| BR-066 | A room type is bookable only if it has availability for the full stay and is not stop-sold on any night of that stay. | M-062, D-05.3 | I |
| BR-067 | «Pay now (−5 %)» and «pay at hotel» are mutually exclusive. The 5 % discount applies to the room subtotal (not taxes) and is forfeited if the payment timing is switched after confirmation. | M-063 | C |
| BR-068 | A confirmed hotel reservation is delivered to the owning hotel's portal immediately and produces a voucher e-mail; the confirmation reference (`HTL-90418` format) is the same in app and portal. | M-064; D-05.1/D-05.4 | C |

## 9. Tours

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-069 | Tour lifecycle states are: draft → in review → published, plus documents-required, rejected and expired. Only `published` is customer-visible and saleable. | D-04.3 tabs (7 published, 4 review, 1 docs, 1 rejected, 1 expired) | C |
| BR-070 | Each departure date/time slot has its own seat capacity; the tour-level capacity is the sum of its future slots, never a single shared counter. | D-04.4 schedule slots, M-067 date strip | C |
| BR-071 | Participants per booking must be ≥ 1, ≤ remaining seats on the chosen slot, and ≤ the programme's group-size maximum. | M-066 (group size, stepper), M-067 | C |
| BR-072 | A pickup location must be selected before payment when the tour offers pickup; the chosen point is stored on the booking and shown on the voucher. | M-067, M-068 | C |
| BR-073 | Free cancellation is available until 24 hours before the slot start; after that the booking is non-refundable unless the operator cancels. The design states the promise but no cancellation flow (see F-071). | M-068 | C |
| BR-074 | The displayed rating is the average of approved reviews only; the review count must equal the number of approved reviews, and partners cannot edit either value. | M-062, M-066 (4.8 / 612), M-065 | I |
| BR-075 | A departure slot is offering only if its date is in the future, it has remaining seats, and the tour is published. | M-067, M-066 date choice | I |

## 10. Booking lifecycle & refunds

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-076 | One canonical state machine serves all services: `pending_payment → confirmed → completed`, with `cancelled` and `refunded` reachable from confirmed, and `expired` from pending_payment. Service-specific sub-states must map onto these. | M-037 tabs, M-038, D-03.3, D-04.5, D-05.4 | I |
| BR-077 | Cancellation eligibility is evaluated per service policy: flights per fare rules, hotels per rate policy, tours per 24-hour window, eSIM never after activation, visas per application stage. | M-042, M-064, M-068, M-035 | C |
| BR-078 | Refund amount = amount paid − applicable cancellation fee, floored at zero. The fee and refund are both itemised before confirmation. | M-042 (3,605 refund / 1,515 fee) | C |
| BR-079 | Refunds are returned to the original payment instrument within the displayed 7–14 day window, and the booking moves to `refunded` only when the provider confirms. | M-042 | C |

## 11. Partner onboarding & tenancy

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-080 | The account type selected at portal login must equal the account's actual role. A user authenticated as a hotel cannot be routed into the company portal even if they choose that tile. | D-01.1 tiles | C |
| BR-081 | A join request requires: activity/partner type, name, city, responsible person, phone, e-mail, the three legal documents (tourism licence, commercial register, tax card — or the hotel establishment licence), and terms acceptance. No field may be skipped. | D-02.1, D-02.2 | C |
| BR-082 | The application acknowledgment promises review within two business days; this is a service-level statement, not an automatic approval. | D-02.3, D-03.6 | C |
| BR-083 | Accepting an application creates the partner tenant, its owner account and the activation link; the owner cannot log in before activation. Rejection requires a recorded reason (not drawn). | D-03.6, D-02.3 | C |

## 12. Roles & permissions

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-084 | A user can never grant, on any portal, a permission they do not themselves hold. This applies to role creation, employee invitations and permission edits. | D-03.15, D-04.8 | C |
| BR-085 | Partner-side permissions are scoped to the partner's own tenant and data. A company employee can never read or mutate another partner's records, even with an equivalent role name. | D-04.7, D-04.8, D-06.1 | I |
| BR-086 | Only the partner owner can transfer ownership or delete the partner tenant; delegated roles cannot acquire these powers through the custom-role editor. | D-04.7 roles, D-04.8 toggles | C |
| BR-087 | An employee with the restricted role sees only records assigned to them; assignment is the authorisation boundary, not the sidebar. | D-06.1 «صلاحيات محددة» | C |
| BR-088 | Permission changes take effect on the next request. An already-authenticated user must not retain removed capabilities until their token expires. | D-04.8 save | I |
| BR-089 | Hiding a sidebar item is a usability signal only; every restricted action must also be rejected by the API with an authorisation error. | D-06.3 locked entries, D-06.2 read-only | C |

## 13. Partner content & tour governance

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-090 | Only tours in `published` state are visible to customers; in-review, documents-required and rejected tours must not appear in the catalogue or search. | D-04.3, M-065 | C |
| BR-091 | Submission for review requires all mandatory content (name, type, destination, duration, description, price, child price, at least one image, day-by-day itinerary) and at least one future departure slot with seats. | D-04.4 | C |
| BR-092 | A submitted tour is reviewed by the platform before publication; the partner cannot self-publish. | D-04.3 statuses, D-03.1 approvals | C |
| BR-093 | A rejected tour remains editable and can be resubmitted; the rejection reason must be visible to the partner (reason field itself not drawn). | D-04.3 «مرفوض» | C |
| BR-094 | Suspending a partner immediately delists all its tours, rooms and offers while preserving existing confirmed bookings. | D-03.7/D-03.8 «تعليق الحساب» | I |
| BR-095 | A visa request cannot advance to the next stage while any mandatory document is rejected or missing; optional documents do not block advancement. | D-04.6, M-030 | C |

## 14. Commissions, settlements & finance

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-096 | Commission is configured per partner: 10 % default for tourism companies, 15 % for hotels (both visible in admin). Rate changes apply prospectively, never retroactively to settled periods. | D-03.5, D-03.7, D-03.8, D-04.4 | C |
| BR-097 | Commission = commission rate × net sale, where net sale excludes taxes and refunded amounts. | D-03.14, D-04.9 | I |
| BR-098 | Partner due for a period = partner sales − platform commission − refunds charged to that partner. Negative results carry forward rather than being paid. | D-03.14 partner dues table | C |
| BR-099 | Settlement states are `pending → processing → transferred`, with `failed` returning to pending. Only `transferred` closes a period. | D-03.14 transfer status, D-04.9 upcoming settlements | C |
| BR-100 | A refund issued after a settlement reduces the partner's next settlement; it is never deducted twice. | D-03.14 refunds, M-042 | I |
| BR-101 | Legal documents are re-verified whenever they are replaced or reach expiry; expired or unverified documents block payouts and, for hotels, publication. | D-03.7/D-03.8 expiry badges, D-04.10 | C |
| BR-102 | The transaction log is append-only: entries can gain a status but their amount, reference and actor cannot be edited or deleted. | D-03.14 `PAY-xxxxx` log | I |
| BR-103 | Totals shown as KPIs and totals in an export must be produced by the same aggregation for the same filter; an export may not disagree with the screen that produced it. | D-03.1, D-03.14, D-04.9, D-05.7 | I |

## 15. Notifications, audit & data lifecycle

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-104 | Notifications are generated by domain events, not by polling: booking confirmed/cancelled, visa submitted/documents-incomplete/approved, eSIM issued, partner join request, deletion request, refund request, settlement transferred, employee deactivated. | M-048, D-03.2, D-04.2, D-05.2, D-06.3 | C |
| BR-105 | Read/unread state is per user per notification; «mark all read» affects only that user's visible set. | M-048, D-03.2 | C |
| BR-106 | Stop-sell blocks new bookings for the affected room type/date; it never cancels or modifies existing bookings. | D-05.3 stop-sell | C |
| BR-107 | Every change to price, availability, stop-sell, commission or document verification is audited with actor, timestamp, before and after values. | D-05.3 bulk adjust, D-03.9, D-03.5 | I |
| BR-108 | An account-deletion request enters a review queue (14 open in the capture) and is not executed on user tap alone. | M-056, D-03.11 | C |
| BR-109 | Deletion removes personal data but retains financial records (invoices, payments, refunds) for the legally required period, in a form that no longer identifies the user where possible. | D-03.11, M-051 | I |
| BR-110 | A hotel cannot publish its profile until its legal documents and profile data are approved; publishing a price or availability change does not require re-approval. | D-05.6 «حفظ ونشر» | I |
| BR-111 | Every administrative mutation (accept/reject partner, suspend, block customer, change role, transfer settlement) writes an audit entry with the acting staff account. | D-03.15, D-03.13, D-03.14 | I |

## 16. Reporting & cross-portal consistency

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-112 | Each KPI has one definition used by every portal: bookings = confirmed + completed (not cancelled); revenue = captured payments minus refunds; occupancy = occupied room-nights ÷ available room-nights for the period. | D-03.1, D-04.1, D-05.1, D-05.7 | I |
| BR-113 | A booking carries one reference that is identical in the mobile app, the admin portal and the owning partner portal. The inconsistent prefixes in the captures (`TRW7K2`, `TW-48210`, `HTL-90418`, `TR-33107`, `PAY-90121`, `P-2291`) require one scheme per entity. | M-025, M-036, M-064, M-068, D-03.14, D-02.3 | U |
| BR-114 | The settlement amount shown to the partner equals the corresponding admin partner-due row for the same period, to the cent. | D-03.14, D-04.9 | C |
| BR-115 | An employee sees only visa requests and bookings assigned to them; unassigned records are excluded at query level. | D-06.1, D-06.2 | C |
| BR-116 | The employee role has no write capability on bookings; any write attempt is rejected even if the UI were bypassed. | D-06.2 | C |
| BR-117 | Locked modules (team, finance, settings) are inaccessible to the employee role on the server, not merely hidden in navigation. | D-06.3 | C |
| BR-118 | Every list export applies the filters currently active on screen and uses the same date range and status definitions. | D-03.3, D-03.12, D-04.5, D-05.4, D-05.7 | C |

---

## 17. Rule coverage and validation

| Check | Result |
|---|---|
| Rules defined | 118 (BR-001 – BR-118) |
| Confirmed (`C`) | 75 |
| Inferred (`I`) | 37 |
| Unresolved (`U`) | 6 (BR-036, BR-047, BR-048, BR-055, BR-060, BR-113) |
| Rules referencing a screen | 118 / 118 |
| Rules without evidence | 0 |
| Rules that depend on a Phase 1 inconsistency | BR-113 (reference formats), BR-078 (cancellation amounts) |
| Rules that assume a missing screen | BR-060 (hotel search form), BR-048 (seat map) — both `U` |

### 17.1 Rules that cannot be implemented until answered

| Rule | Blocking question |
|---|---|
| BR-036 Multi-city | Q-08 |
| BR-047 Name correction | Q-10 |
| BR-048 Seat selection | Q-09 |
| BR-055 Visa eligibility | Q-12 |
| BR-060 Hotel search inputs | Q-23 |
| BR-113 Reference scheme | Q-25 |
| BR-078 refund arithmetic (conflicting totals) | Q-01 |
| BR-002 currency conversion | Q-19 |

