# Phase 2 — Business Logic

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Decisions applied — awaiting approval (`APPROVE PHASE 3`)
**Inputs:** [`FEATURES-CATALOG.md`](./FEATURES-CATALOG.md), [`../phase-01/UI-SCREENS-INVENTORY.md`](../phase-01/UI-SCREENS-INVENTORY.md), [`DB-DECISION-BRIEF.md`](./DB-DECISION-BRIEF.md)
**Date:** 2026-11-23 · **Decisions applied:** 2026-11-24

---

## 1. How to read this document

125 rules, grouped by domain. Each rule carries:

* **ID** — `BR-nnn`, referenced from features and workflows.
* **Rule** — the statement to implement. Formulas are given where the design exposes arithmetic.
* **Evidence** — the screen(s) that prove or imply it.
* **Tag** — `C` confirmed by pixels, `I` inferred (design requires it but does not draw it), `D` decision-backed (records an owner decision where no screen proves the rule), `U` unresolved (must be answered before implementation).

Rules marked `I` are the only place where Phase 2 adds mechanics that the UI does not show. Every `I` rule is mirrored by a question in `OPEN-QUESTIONS.md`. Rules marked `D` implement the ten recorded decisions and the authorization requirement. Rules marked `U` are **not** to be implemented until answered.

### Global precedence

1. Server-side validation always wins over client-side validation.
2. A price, fee or eligibility value is never accepted from the client; it is recomputed from the catalogue or inventory at request time.
3. Any rule that affects money, entitlement or access is enforced in the API, not by hiding UI.
4. Where two screens disagree (see Phase 1 §7), no rule is written until the conflict is resolved; the conflict appears as `U`.

---

## 2. Pricing, currency & promotions

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-001 | All monetary values are stored and computed in USD, in integer minor units, and displayed with 2 decimals. USD is the only currency (DEC-02); no conversion, FX rate or currency selector is implemented. All Figma amounts are illustrative and never define a rule. | M-024, M-043, M-053, all dashboards; DEC-01/DEC-02 | D |
| BR-002 | Multi-currency is out of scope: there is no display conversion, no FX snapshot and no per-currency ledger. This rule supersedes its Phase-2 working form (DEC-02). | M-043, M-053; DEC-02 | D |
| BR-003 | A flight price is composed of base fare + taxes + carrier/airport fees; each component is summed into the total shown before payment. Provider-level component breakdown is not drawn. | M-020, M-021, M-024 | C |
| BR-004 | The price displayed on a result card is the total for the whole passenger mix, not per person, unless the card explicitly says «للفرد». | M-021 (cards), M-024 (total $511 for 3 pax) | C |
| BR-005 | Service/platform fees, if charged, are itemised in the payment breakdown; no fee may appear for the first time on the payment screen without an itemised line. | M-024 breakdown | I |
| BR-006 | No fare hold exists. The fare is revalidated immediately before payment; if the total changed, the new total is shown and must be accepted explicitly (BR-043). | M-024; DEC-05 | D |
| BR-007 | Visa fees are administered per offering (country + visa type) and served to the app; the app never hard-codes a fee. Platform Admin or the publishing tourism company sets and updates the price for its own offering. The fee is snapshotted on the application at submission. | M-029 «[حسب لوحة التحكم]»; D-03.9; DEC-06 | D |
| BR-008 | eSIM sale price per package is set as provider cost + markup (admin shows 35 % markup and per-package cost/sale/margin); the customer price is the stored sale price, not a runtime markup. | D-03.10; M-034, M-035 | C |
| BR-009 | Hotel total = nightly room price × number of nights + taxes/fees, for the selected room type and occupancy; the result screen and the checkout must show the same total. | M-059 (per night + total), M-062 (7 nights), M-063 ($958) | C |
| BR-010 | Tour total = per-person price × paying participants (+ child price where applicable); the detail and checkout totals must match. | M-066 (2 participants), M-067 | C |
| BR-011 | Partner-facing net amounts exclude platform commission; customer-facing prices include it. The two are never shown to the same audience as if they were equal. | D-04.4 «net after 10 %», D-03.7/8 commission | C |
| BR-012 | Rounding is half-up to 2 decimals at the line level; the sum of rounded lines must equal the displayed total (no rounding drift). | M-024, M-063 | I |
| BR-013 | Changing any priced input (dates, passenger count, room type, participants, extras) invalidates the previous quote and forces recomputation. | M-017, M-018, M-040, M-041, M-063, M-067 | C |
| BR-014 | A booking is settled in exactly one currency; mixed-currency carts are not permitted. | implied by M-024 | I |
| BR-015 | At most one coupon per booking. Validation is server-side for existence, active state, expiry, minimum spend, service scope (global, or one of flights/hotels/visas) and usage limits. There is no first-booking restriction. Platform Admin and company owners create coupons (company owners only for their own company). Redemption counters are decremented atomically so concurrent attempts cannot exceed the limit. The discount is recomputed by the server and snapshotted. `TRAWAY10` is illustrative. | M-024, M-057; DEC-03 | D |

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
| BR-029 | Account deletion is a request → 7-day grace → anonymisation workflow. The user may cancel during the grace period. Deletion is blocked while the user has upcoming trips, a visa application under review, or a pending refund. Existing bookings are not cancelled; financial and booking records are retained. After anonymisation, partner-facing views show a masked reference, never personal data. | M-056, D-03.11; DEC-09 | D |
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
| BR-041 | No inventory hold is created at payment. The checkout revalidates availability and price at the moment of capture. The client countdown is removed (Q-43). | M-024; DEC-05 | D |
| BR-042 | If revalidation fails (sold out, fare withdrawn, or price changed and not accepted), no charge is made and the customer is returned to results with an explanation. A capture that nevertheless arrives after a failed revalidation is refunded automatically. | M-024; DEC-05 | D |
| BR-043 | If the fare changed while the user was on payment, the new total must be shown and explicitly accepted before charging; silent repricing is forbidden. | M-024 | I |
| BR-044 | Reschedule is permitted only before departure of the affected leg. Charge = change fee + (new fare − original fare); the difference may be zero but may not be negative unless the fare rules allow residual credit. | M-040 (+$65, total 135) | C |
| BR-045 | Baggage add-ons are limited by carrier allowance and a maximum piece/weight per passenger; the stepper must stop at the carrier limit. Prices shown: 23 kg $24, 32 kg $35, sports equipment $19. | M-041, M-023 (+$19) | C |
| BR-046 | The same passenger must not hold two overlapping bookings on overlapping flight segments; a duplicate-booking check runs before payment. | inferred from M-023/M-024 | I |
| BR-047 | Name corrections after ticketing follow carrier rules (usually not permitted); the design shows no edit path after confirmation. Policy unresolved. | M-025 → M-038 | U |
| BR-048 | Seat selection pricing, free-seat entitlement and availability are undefined; only a three-seat confirmation bar exists. | M-026 | U |
| BR-049 | A ticket is issued only after the provider confirms capture. The airline PNR is assigned by the carrier and stored as an external attribute of the ticket; it is never the Traway booking reference. The Traway reference (`TRW-FLT-…`) is immutable. | M-025 `TRW7K2`, M-038, M-039; DEC-04 | D |

## 6. Visa applications & documents

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-050 | Accepted document formats are PDF, JPG and PNG with a per-file maximum of 10 MB; a document that fails either check is rejected before upload. | M-029, M-030 | C |
| BR-051 | A visa request can be submitted only when every mandatory document is present and accepted; optional documents never block submission. | M-030 (3 of 5, 2 remaining), M-032 | C |
| BR-052 | The 24–48 h figure is an expectation. The authoritative expected processing duration is configured per visa offering and shown to the customer. | M-031, D-03.9; DEC-07 | D |
| BR-053 | A reused saved document is attached only if it belongs to the same applicant, has not expired, and matches the document type required by the application. | M-032 reusable documents | I |
| BR-054 | The visa fee shown is the active price for that offering, snapshotted on the application at submission; later catalogue changes never alter a submitted application. A deactivated offering cannot be applied for. | M-029; D-03.9; DEC-06 | D |
| BR-055 | Visa pricing and eligibility do not vary by the applicant's nationality or residence. Availability depends only on the offering being active. | M-027 – M-029; D-03.9; DEC-06 | D |

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
| BR-060 | A hotel search requires destination/city, check-in, check-out, adults, children and rooms. Hotels store latitude and longitude so search and the map can query by location. No nationality-based or child-age pricing rules apply. | M-059 summary; DEC-08 | D |
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
| BR-078 | Refund = amount actually captured − any amount retained by the payment gateway under its rules, floored at zero. Traway implements no custom cancellation-fee engine; gateway fees and chargebacks are borne per contract (DEC-01, DEC-10). Figma's 3,605 / 1,515 values are illustrative and seed no fee table. | M-042; DEC-01 | D |
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
| BR-084 | No user can grant a permission, role or scope they do not themselves hold. This applies to platform role creation, tenant role creation, employee invitations, direct grants and permission edits. Platform permissions can never attach to organization roles, and vice versa. | D-03.15, D-04.8; authorization requirement | D |
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
| BR-098 | Partner due for a period = partner sales − platform commission − refunds charged to that partner. A negative balance is carried forward and deducted from future settlements; it is never paid out. | D-03.14 partner dues table; DEC-10 | D |
| BR-099 | Settlement states are `pending → processing → transferred`, with `failed` returning to pending. Payouts are executed through the payment gateway where supported and requested on demand. Only `transferred` closes a period. | D-03.14 transfer status, D-04.9 upcoming settlements; DEC-10 | D |
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
| BR-107 | Every change to price, availability, stop-sell, commission, document verification, role, permission, membership or service scope is audited with actor, timestamp, before and after values. **Superseded by `G0-DEC-01`: audit logs and general activity logs are permanently excluded; no such record is written and no audit table exists.** | D-05.3 bulk adjust, D-03.9, D-03.5, D-03.15; DEC-10 | D |
| BR-108 | An account-deletion request enters a review queue (14 open in the capture) and is not executed on user tap alone. | M-056, D-03.11 | C |
| BR-109 | Deletion removes personal data but retains financial and booking records (invoices, payments, refunds) for the legally required period, in a form that no longer identifies the user where possible. Partners lose access to personal data after anonymisation. The exact retention duration is Q-47. | D-03.11, M-051; DEC-09 | D |
| BR-110 | A hotel cannot publish its profile until its legal documents and profile data are approved; publishing a price or availability change does not require re-approval. | D-05.6 «حفظ ونشر» | I |
| BR-111 | Every administrative or access-control mutation (accept/reject partner, suspend, block customer, change role or permission, transfer settlement, approve exceptional refund) writes an audit entry with the acting staff account. **Superseded by `G0-DEC-01`: no audit entry is written. Attribution columns on the affected rows are the only record.** | D-03.15, D-03.13, D-03.14; DEC-10 | D |

## 16. Reporting & cross-portal consistency

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-112 | Each KPI has one definition used by every portal: bookings = confirmed + completed (not cancelled); revenue = captured payments minus refunds; occupancy = occupied room-nights ÷ available room-nights for the period. | D-03.1, D-04.1, D-05.1, D-05.7 | I |
| BR-113 | A booking carries one customer-facing reference that is identical in the mobile app, the admin portal and the owning partner portal. References are random, unique and non-sequential: `TRW-FLT-{random}`, `TRW-HOT-{random}`, plus per-entity prefixes for visa, tour, eSIM, payment and partner application. Internal primary keys and external provider references are separate columns. | M-025, M-036, M-064, M-068, D-03.14, D-02.3; DEC-04 | D |
| BR-114 | The settlement amount shown to the partner equals the corresponding admin partner-due row for the same period, to the cent. | D-03.14, D-04.9 | C |
| BR-115 | An employee sees only visa requests and bookings assigned to them; unassigned records are excluded at query level. | D-06.1, D-06.2 | C |
| BR-116 | The employee role has no write capability on bookings; any write attempt is rejected even if the UI were bypassed. | D-06.2 | C |
| BR-117 | Locked modules (team, finance, settings) are inaccessible to the employee role on the server, not merely hidden in navigation. | D-06.3 | C |
| BR-118 | Every list export applies the filters currently active on screen and uses the same date range and status definitions. | D-03.3, D-03.12, D-04.5, D-05.4, D-05.7 | C |

### 16.1 Authorization rules (new — authorization requirement)

| ID | Rule | Evidence | Tag |
|---|---|---|---|
| BR-119 | Platform and organization permissions are two separate families. A role belongs to exactly one family, and a membership context evaluates only its own family. | D-03.15, D-04.7, D-04.8; authorization requirement | D |
| BR-120 | Every tenant-owned record carries a non-null owning organization id. Tenant context is derived from the authenticated membership, never from a request parameter or header. | D-04.5, D-05.4, D-06.1; authorization requirement | D |
| BR-121 | Service scope only narrows access. A member with service scopes sees only those service lines; organization owners see all; a non-owner with no service scope has no service access. **Confirmed by `G0-DEC-05`: an empty (or null) scope grants nothing; every scoped query fails closed.** | D-04.7, D-05.5; authorization requirement | D |
| BR-122 | A grantor may grant only permissions and scopes they currently hold. Platform permissions never attach to organization roles; organization permissions never attach to platform roles. | D-04.8, D-03.15; authorization requirement | D |
| BR-123 | Exactly one active owner exists per organization. Ownership is transferred atomically, the owner role is immutable, and the last active owner cannot be removed or demoted. | D-04.7, D-05.5; authorization requirement | D |
| BR-124 | Role, permission, membership and service-scope changes take effect on the next request and are audited with before/after values and the actor's membership. **The "take effect on the next request" half stands (`permissions_version`, PE-12). The audit half is superseded by `G0-DEC-01`.** | D-04.8, D-03.15; authorization requirement | D |
| BR-125 | Deactivating or removing a membership revokes its sessions immediately. Roles, grants and service scopes never carry to another organization. | D-04.2, D-04.7; authorization requirement | D |

---

## 17. Rule coverage and validation

| Check | Result |
|---|---|
| Rules defined | 125 (BR-001 – BR-125) |
| Confirmed (`C`) | 63 |
| Decision-backed (`D`) | 28 |
| Inferred (`I`) | 31 |
| Unresolved (`U`) | 3 (BR-036, BR-047, BR-048) |
| Rules referencing a screen or a recorded decision | 125 / 125 |
| Rules without evidence | 0 |
| Rules that depend on an unresolved Phase 1 inconsistency | 0 — BR-078 and BR-113 are resolved by DEC-01/DEC-04 |
| Rules that assume a missing screen | BR-048 (seat map) only |

### 17.1 Rules that cannot be implemented until answered

| Rule | Blocking question | Position |
|---|---|---|
| BR-036 Multi-city | Q-08 | Out of scope for v1; option hidden |
| BR-047 Name correction | Q-10 | Blocking-4 |
| BR-048 Seat selection | Q-09 | Out of scope for v1 |

