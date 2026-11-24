# Phase 2 — Features Catalog

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Decisions applied — awaiting approval (`APPROVE PHASE 3`)
**Inputs:** [`../phase-01/UI-SCREENS-INVENTORY.md`](../phase-01/UI-SCREENS-INVENTORY.md), [`../phase-01/SCREEN-COVERAGE-REPORT.md`](../phase-01/SCREEN-COVERAGE-REPORT.md), [`DB-DECISION-BRIEF.md`](./DB-DECISION-BRIEF.md)
**Companion documents:** `BUSINESS-LOGIC.md`, `USER-ROLES-AND-PERMISSIONS.md`, `AUTHORIZATION-ARCHITECTURE.md`, `ROLE-PERMISSION-SCOPE-MATRIX.md`, `WORKFLOWS.md`, `OPEN-QUESTIONS.md`
**Date:** 2026-11-23 · **Decisions applied:** 2026-11-24

---

## 1. Method and conventions

Every requirement in this phase is derived from a Phase 1 screen, except two features that the owner's decisions and the authorization requirement make mandatory (F-111, F-112 — tagged `D`). No other feature is invented; where a screen only proves an entry point, the behaviour behind it is marked unresolved and carried to `OPEN-QUESTIONS.md`.

### 1.1 Evidence tags

| Tag | Meaning |
|---|---|
| **C** | **Confirmed** — directly visible in one or more screens: label, field, state, amount, or workflow step. |
| **C+i** | **Confirmed feature, inferred mechanics** — the screen proves the feature exists and its visible behaviour, but a server-side or non-depicted rule is required for it to work. The inference is named in the row. |
| **D** | **Decision/requirement-backed** — no Phase 1 screen proves the feature, but the owner's recorded decision (DEC-03) or the authorization requirement makes it mandatory. The basis is cited in the row. |
| **U** | **Unresolved** — the design shows a concept, fragment or promise but not the rule, data or outcome. Not a v1 requirement until answered. |

No feature is tagged `U` and simultaneously treated as a committed requirement.

### 1.2 Priority

| Priority | Meaning |
|---|---|
| **P0** | Revenue-critical or required for any transaction to complete. |
| **P1** | Required for a complete, operable product on day one. |
| **P2** | Operational/supporting capability; can ship slightly later. |
| **P3** | Enhancement, not required for first release. |

### 1.3 Application codes

| Code | Application |
|---|---|
| `MOB` | Traway traveller mobile app (Arabic RTL, English toggle) |
| `ADM` | Traway platform administration portal |
| `COM` | Tourism-company partner portal |
| `HTL` | Hotel partner portal |
| `EMP` | Restricted employee portal (same shell as `COM`, reduced permissions) |
| `PUB` | Shared partner login / join-request surface |

### 1.4 Identifier conventions (used across Phase 2 and later phases)

| Prefix | Object | Home document |
|---|---|---|
| `F-nnn` | Feature | this document |
| `BR-nnn` | Business rule | `BUSINESS-LOGIC.md` |
| `R-nnn` | Role | `USER-ROLES-AND-PERMISSIONS.md` |
| `WF-nn` | Workflow | `WORKFLOWS.md` |
| `Q-nn` | Open question | `OPEN-QUESTIONS.md` |
| `M-nnn` / `D-nn.n` | Screen state (Phase 1) | `../phase-01/UI-SCREENS-INVENTORY.md` |

### 1.5 Domain index

| # | Domain | Features | Screens |
|---|---|---|---|
| 1 | Onboarding & access | F-001 – F-003 | M-001 – M-005, M-014 |
| 2 | Authentication & identity | F-004 – F-013 | M-005 – M-013 |
| 3 | Search, discovery & notifications | F-014 – F-021 | M-014 – M-018, M-048, M-057, M-058 |
| 4 | Flights | F-022 – F-030 | M-019 – M-026 |
| 5 | Visas — traveller | F-031 – F-038 | M-027 – M-032 |
| 6 | eSIM | F-039 – F-043 | M-033 – M-037 |
| 7 | Bookings & post-booking | F-044 – F-049 | M-037 – M-042 |
| 8 | Profile, payments & account | F-050 – F-060 | M-043 – M-056 |
| 9 | Hotels | F-061 – F-066 | M-059 – M-064 |
| 10 | Tours | F-067 – F-071 | M-065 – M-068 |
| 11 | Partner onboarding & portal auth | F-072 – F-077 | D-01.1, D-01.2, D-02.1 – D-02.3, D-03.6 |
| 12 | Admin portal | F-078 – F-090 | D-03.1 – D-03.15 |
| 13 | Company portal | F-091 – F-100 | D-04.1 – D-04.10 |
| 14 | Hotel portal | F-101 – F-107 | D-05.1 – D-05.7 |
| 15 | Employee portal | F-108 – F-110 | D-06.1 – D-06.3 |
| 16 | Coupons & authorization administration | F-111 – F-112 | — (decision/requirement-backed; editors on D-03.15, D-04.7, D-04.8, D-05.5) |

---

## 2. Feature register

### Domain 1 — Onboarding & access

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-001 | Brand splash & boot routing | MOB | M-001 | P1 | C | Shows logo, tagline «رحلتك من الفكرة… حتى الوصول», chips (flights, hotels, visas, eSIM). On boot, decides next route: onboarding (first run) or Home (returning session). Session check is `C+i`. |
| F-002 | Onboarding carousel | MOB | M-002, M-003, M-004 | P1 | C | 3 ordered steps (booking, visas, eSIM). Inputs: swipe/next/skip. Output: onboarding-completed flag persisted on device. Skip is available on all three steps; only the last step offers «البدء». |
| F-003 | Guest browsing | MOB | M-005, M-014 | P1 | C+i | «تصفح كضيف» from login enters Home without account. Inferred: guest can browse and search; any booking/visa/eSIM purchase or profile action must trigger auth. Exact guest limits unresolved (`Q-20`). |

### Domain 2 — Authentication & identity

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-004 | Phone login with SMS OTP | MOB | M-005, M-007 | P0 | C | Inputs: country code (default +20), phone, 4-digit OTP. Outputs: session. Resend countdown visible (00:42); alternate channel «أرسل عبر واتساب». Navigation: OTP confirm → Home (M-014) or profile completion (M-011) — target unresolved (`Q-02`). |
| F-005 | Social login (Apple / Google) | MOB | M-005, M-006 | P1 | C+i | Buttons present on both login tabs. Inferred: provider token exchanged for a Traway session; first-time social users land on profile completion. Failure/cancel handling not drawn. |
| F-006 | Email & password login | MOB | M-006 | P0 | C | Inputs: email, password (show/hide), remember-me. Actions: login, forgot password, create account, social login. Validation: required fields; error copy not drawn (`Q-03`). |
| F-007 | Email signup with password policy | MOB | M-008 | P0 | C | Inputs: full name, email, password, confirm password, terms checkbox. Password strength meter explicitly validates length, upper/lower case, symbol, number. Terms checkbox is a blocking gate. |
| F-008 | Email verification | MOB | M-009 | P0 | C | Inputs: 6-digit code (cells), resend (00:58 countdown), open-mail-app. Output: verified account → account-ready state. Wrong/expired code handling not drawn (`Q-03`). |
| F-009 | Account-ready & optional enrichment | MOB | M-010 | P1 | C | Success state; optional add-phone and add-passport prompts; primary CTA «ابدأ الاستكشاف» → Home. Enrichment must remain skippable. |
| F-010 | Profile completion | MOB | M-011 | P1 | C | Inputs: first/last name, email, gender, birth date, nationality, marketing opt-in. Output: completed profile → Home. Age/gender constraints unresolved (`Q-04`). |
| F-011 | Forgot password (request) | MOB | M-012 | P0 | C | Input: email. Output: reset code sent, spam hint shown. Neutral response required to avoid account enumeration (`BR-021`). |
| F-012 | Reset password | MOB | M-013 | P0 | C | Inputs: recovery code, new password, confirm. Rule: confirm must match; strength policy same as signup. Output: password changed → login. |
| F-013 | Session & identity guard | MOB | all authenticated | P0 | C+i | Every personalised screen implies an authenticated principal. Inferred: token issuance, refresh, logout, and per-device session. Logout affordance is **not** visible anywhere (`Q-05`). |

### Domain 3 — Search, discovery & notifications

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-014 | Home feed | MOB | M-014 | P0 | C | Product tabs (flights, hotels, tours) swap the search panel in place. Modules: flight search card, eSIM/visa quick cards, upcoming-trip card with 3 progress indicators (eSIM, visa, ticket), offers carousel, featured destinations, hotels, tours, visas by country, trust section. |
| F-015 | Global search | MOB | M-015 | P1 | C | Input: free text. Filter chips (all, destinations, hotels, eSIM, visas). Output: mixed result list. Result-row → detail mapping is inferred (`Q-06`); no empty-result state drawn. |
| F-016 | City & airport picker | MOB | M-016 | P0 | C | Inputs: text search, recent searches, popular destinations from Cairo (DXB, JED, RUH, IST, AYT, CDG, LHR, KUL). Output: selected city/airport returned to caller (flight search or hotel search). |
| F-017 | Date range & price calendar | MOB | M-017 | P0 | C | Inputs: departure/return, ±3-day flexibility toggle, «أرخص الأسعار» toggle. Output: selected range. Rule: departure ≥ today; return ≥ departure (`BR-031`). Per-day prices imply fare-calendar data availability. |
| F-018 | Passengers & cabin selector | MOB | M-018 | P0 | C | Inputs: adults (12+), children (2–11), infants (<2), cabin (economy/premium/business/first). Visible limits: max 9 passengers, max 1 infant per adult. Output: passenger mix + cabin. |
| F-019 | Offers list | MOB | M-057 | P2 | C | Promo code `TRAWAY10` (10 % first booking), category chips (all, flights, hotels, tours, eSIM), offer cards with discount, conditions, expiry countdown. Rules fixed by DEC-03: one per booking, no first-booking restriction, global or flights/hotels/visas, usage limits (`BR-015`). |
| F-020 | Featured destinations | MOB | M-058 | P2 | C | Region chips (all, Gulf, Europe, Asia, islands/beaches), hero destination with starting price, grid with visa-requirement badges. Destination → flight or hotel results is inferred. |
| F-021 | Notifications inbox | MOB | M-048 | P1 | C | Filter chips (all, bookings, offers, payments), day/week grouping, unread dots, mark-all-read. Item destinations not drawn → deep links unresolved (`Q-07`). Push delivery is inferred (`BR-104`). |

### Domain 4 — Flights

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-022 | Flight search (one-way / round / multi-city) | MOB | M-019 | P0 | C | Inputs: trip type (3 options), from/to with swap, dates, passengers/class, direct-only. Validation: origin ≠ destination, both required, date rules, passenger rules. Multi-city tab exists but **its form is not supplied** (`Q-08`). |
| F-023 | Flight filters | MOB | M-020 | P0 | C | Inputs: stops, departure window, price range ($172–$290 in capture), airlines with per-airline minimum fares, baggage-included toggle, reset/apply. Output: filtered result set with count (12 offers). Filters must not be able to produce an unrecoverable empty state (`BR-039`). |
| F-024 | Flight results & sorting | MOB | M-021 | P0 | C | Date strip with per-date fares, sort (cheapest / fastest / direct), filters entry, result cards (airline, times, duration, stops, price, refundability), hotel cross-sell. Selecting a fare → return leg (round trip) or passenger details (one-way) — routing is `C+i`. |
| F-025 | Return-leg selection | MOB | M-022 | P0 | C | Return results (DXB→CAI) with same card pattern and «اختيار العودة». Visible rule: 18-day validity notice. Output: paired itinerary → passenger details. |
| F-026 | Passenger details & validation | MOB | M-023 | P0 | C | Inputs per traveller: first/family name, passport number (adult), inline validation, add child (2–11), additional baggage (+$19). Output: traveller set + total for 3 passengers → payment. Name/passport format rules unresolved (`Q-10`). |
| F-027 | Seat selection | MOB | M-026 (fragment) | P3 | U | Only a confirmation bar is supplied: 3 seats (12A/12B/12C), «مجاناً», «تأكيد المقاعد». No seat map, pricing or availability design. **Not a committed v1 requirement** until `Q-09` is answered. |
| F-028 | Checkout price revalidation | MOB | M-024 | P0 | C | The 14:32 countdown in the capture is illustrative. **No fare hold exists** (DEC-05): the server revalidates availability and price immediately before payment, and a changed total requires explicit consent (`BR-006`, `BR-041` – `BR-043`). |
| F-029 | Payment, promo & checkout | MOB | M-024 | P0 | C | Methods: card, e-wallet, Apple Pay. Card fields: number, expiry, CVV. Price breakdown with promo `TRAWAY10` (−$24) and total $511. Rule: one promo per booking, validated server-side (`BR-015`). |
| F-030 | Flight booking confirmation | MOB | M-025 | P0 | C | Success overlay, PNR `TRW7K2`, route, duration, date/time, 3 passengers, economy, total; upsells to UAE eSIM and UAE visa; actions «عرض الحجز», «الرئيسية». Inferred: confirmation triggers booking record + notification + ticket issuance. |

### Domain 5 — Visas (traveller side)

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-031 | Visa country catalogue | MOB | M-027 | P0 | C | Search country, region chips (all, Gulf, Europe/Schengen, Asia), country rows with visa-type counts (KSA 3, UAE 3, Turkey 2, France 2, Germany 2). Output: country selection. |
| F-032 | Visa types per country | MOB | M-028 | P0 | C | Country hero + type cards (tourism, business, family visit) each with duration, entry type (multiple/single), document count. Output: type selection. |
| F-033 | Visa type detail & fee display | MOB | M-029 | P0 | C | Shows stay validity (90 days), visa validity (1 year), entry type, fee placeholder «[حسب لوحة التحكم]», required documents (passport, photo, flight booking, hotel booking, bank statement) with accepted formats PDF/JPG/PNG ≤10 MB. Fee is rendered from the active offering and snapshotted on the application (`BR-007`, `BR-054`; DEC-06). |
| F-034 | Visa document upload | MOB | M-030 | P0 | C | Per-document upload with status and progress (3 of 5, 60 %), camera/upload actions, optional document, save with 2 remaining, data-sharing consent note. Rules: file type/size, resumable draft (`BR-050`). |
| F-035 | Visa request submission | MOB | M-031 | P0 | C | Success state with request number `TRW-V-2291`, review SLA 24–48 h, follow-up CTA. Only submittable when all mandatory documents are present (`BR-051`). |
| F-036 | Visa document tracker | MOB | M-032 | P1 | C | Per-application checklist (2 of 5 missing), uploaded/required states with file types, complete-upload CTA, other application (UAE 5/5), reusable saved documents (passport, personal photo). |
| F-037 | Visa application lifecycle | MOB / COM | M-031, M-032, D-04.6 | P0 | C+i | Canonical customer-visible statuses (DEC-07): Submitted → Under Review → Documents Required → Processing → Approved / Rejected. Internal document mechanics stay internal. |
| F-038 | Reusable saved documents | MOB | M-032 | P2 | C | Documents previously uploaded can be attached to a new application. Implied rule: reuse must not bypass validity/expiry checks (`BR-053`). |

### Domain 6 — eSIM

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-039 | eSIM country selection & device check | MOB | M-033 | P0 | C | Search country/region, country-vs-region mode, popular countries with package counts and data ranges, «التحقق الآن» device-compatibility check. Output: country → package list. Device check result rules unresolved (`Q-15`). |
| F-040 | eSIM package selection | MOB | M-034 | P0 | C | Packages: 3 GB/7 d $9; 5 GB/15 d $14 (best value); 10 GB/30 d $23. Shows coverage notes and total; continue → checkout. Rule: one package per purchase (`BR-056`). |
| F-041 | eSIM checkout & payment | MOB | M-035 | P0 | C | Plan facts (coverage, 4G/5G, provider, refund policy), payment method (card / e-wallet), promo code, total incl. tax $14, «ادفع بأمان». Refund policy text is visible but its enforcement rule is unresolved (`Q-16`). |
| F-042 | eSIM QR issuance & activation | MOB | M-036 | P0 | C | Order no. `TW-58210`, QR code, manual `LPA:1$…` string, install-on-this-device, view bookings. Inferred: activation token is single-use and tied to the order (`BR-058`). |
| F-043 | eSIM usage & balance | MOB | M-037 (partial) | P3 | U | Booking card shows «5 GB متبقية» and a progress bar, but no usage screen, refresh rule or depletion behaviour is drawn. Not a committed v1 requirement (`Q-17`). |

### Domain 7 — Bookings & post-booking

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-044 | My bookings list & tabs | MOB | M-037 | P0 | C | Tabs (upcoming, previous, cancelled), trip card with countdown, booking ref, flight summary, eSIM add-on progress, visa-documents warning with upload CTA. Output: booking detail. Empty-state design absent (`Q-18`). |
| F-045 | Booking detail hub | MOB | M-038 | P0 | C | Tabs (flight, passengers, payment); outbound/return cards with PNR, baggage, meals; actions: cancel, add baggage, reschedule; download e-ticket; support. Acts as the parent surface for F-046 – F-049. |
| F-046 | E-ticket / boarding pass | MOB | M-039 | P1 | C | Passenger chips, boarding-pass card (route, duration, flight no. MS 912, date, boarding 07:25, seat 14A, gate, baggage 23 kg), QR, arrival advisory, download PDF, add to wallet. Share action present. |
| F-047 | Reschedule flight | MOB | M-040 | P1 | C | Inputs: new departure/return date. Shows change fee + fare difference (+$65), total difference 135, confirm. Rules: only before departure; fare difference may be zero or negative (`BR-044`). |
| F-048 | Add baggage | MOB | M-041 | P1 | C | Steppers for 23 kg ($24), 32 kg ($35), sports equipment ($19), extra-weight options, running total ($245), add to booking. Rule: allowance must not exceed carrier limits (`BR-045`). |
| F-049 | Cancel booking & refund | MOB | M-042 | P1 | C | Refund breakdown (3,605 refund / 1,515 fee), refund to original method, SLA 7–14 days, irreversibility notice, optional reason chips, typed «إلغاء» confirmation. Figma figures are illustrative; the refund is the captured amount minus any gateway-retained fee (DEC-01, `BR-078`). |

### Domain 8 — Profile, payments & account

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-050 | Account hub | MOB | M-043 | P1 | C | Avatar, name, phone, edit profile, saved travellers (3), visa documents (2 missing), payment methods & history, favourites, language/currency, notifications toggle, customer service. Single entry point for Domain 8. |
| F-051 | Saved travellers (CRUD) | MOB | M-044, M-045 | P0 | C | List with relation, passport number, expiry, status badges (valid / expiring soon), edit, add. Editor captures EN names, email, phone, gender, passport no., expiry with validity warning, OCR entry. |
| F-052 | Passport OCR scan | MOB | M-047 | P2 | C | Camera frame with MRZ detection, extracted data preview, retake/gallery/flash, manual-entry fallback. Inferred: OCR result is user-confirmable and never auto-committed (`BR-024`). |
| F-053 | Edit profile & completeness | MOB | M-046 | P1 | C | Fields: names, email (verified badge), phone, gender, passport no./expiry, completeness 80 %, save. Rule: changing email/phone requires re-verification (`BR-023`). |
| F-054 | Favourites | MOB | M-054 | P2 | C | Tabs (hotels, destinations, tours); hotel cards with price/night and rating; remove. Card → detail is inferred. |
| F-055 | Settings: language, currency, notifications, password | MOB | M-053 | P1 | C | Language toggle (AR/EN), notifications toggle, change password, terms, delete account. Currency label «دولار $» is shown but the selector is hidden because USD is the only currency (DEC-02). |
| F-056 | Terms & privacy | MOB | M-049 | P1 | C | Tabbed terms/privacy, last-updated date, numbered sections, «موافق». Consent must be versioned and stored (`BR-025`). |
| F-057 | Delete account | MOB | M-056 | P1 | C | Consequence list (documents deleted, travellers/favourites lost, existing bookings **not** cancelled), upcoming-trip warning, optional reason, typed «حذف» confirmation. Seven-day grace, cancellable, blocked by upcoming trips / visa under review / pending refunds; financial records retained (DEC-09, `BR-029`). |
| F-058 | Customer service | MOB | M-055 | P1 | C | WhatsApp contact, phone callback, working hours (placeholder «[المواعيد يحددها العميل]»), phone placeholder. No in-app chat drawn (`Q-22`). |
| F-059 | Payment methods | MOB | M-052, M-050 | P0 | C | Default Visa card, Mastercard **** 1190, add card (preview, number, name, expiry, CVV, set-default, PCI note), recent payments. Rule: card data is tokenised; only last-4 stored (`BR-016`). |
| F-060 | Payment history | MOB | M-051 | P1 | C | Totals (paid $657, refunded $65), status chips (all, paid, refunded, processing), month grouping, per-transaction status, export. Row → booking detail is inferred. |

### Domain 9 — Hotels

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-061 | Hotel search & results | MOB | M-059 | P0 | C | Search summary (Dubai, 11–4 Dec, 2 adults), filter chips, 186 properties, sort, cards with rating/reviews, amenities, price/night and total, favourite toggle. **The search form itself is not supplied**, but the required inputs are fixed by DEC-08: destination/city, check-in, check-out, adults, children, rooms. |
| F-062 | Hotel filters | MOB | M-060 | P0 | C | Price-per-night histogram + range, star rating, guest rating, amenity chips, free cancellation, pay-at-hotel, apply (62 offers). |
| F-063 | Hotels on map | MOB | M-061 | P2 | C | Map with price pins, destination selector, list/map toggle, selected-hotel card. Inferred: map bounds query and clustering (`BR-065`). |
| F-064 | Hotel detail & room selection | MOB | M-062 | P0 | C | Gallery, rating/reviews, amenities, room types with prices, cancellation/breakfast badges, total for 7 nights, book now. Rule: only room types with availability for the full stay are bookable (`BR-066`). |
| F-065 | Hotel booking & payment | MOB | M-063 | P0 | C | Stay summary, check-in/out dates and times, lead guest, special-request chips, payment timing (pay now −5 % / pay at hotel), 7 nights + taxes, total $958. Rule: pay-now discount and pay-at-hotel are mutually exclusive (`BR-067`). |
| F-066 | Hotel confirmation & voucher | MOB | M-064 | P0 | C | Success, voucher e-mail notice, confirmation `HTL-90418`, dates, call hotel / voucher / directions, tour upsell, view booking. Inferred: hotel partner receives the reservation in its portal (`BR-068`). |

### Domain 10 — Tours

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-067 | Tours catalogue | MOB | M-065 | P0 | C | Search, category chips (all, day tours, full programmes, family), featured multi-day programme (Maldives 6 nights, $1,160), cards with duration and price. |
| F-068 | Tour programme detail | MOB | M-066 | P0 | C | Hero, rating 4.8 (612 reviews), duration/guide/languages/group size, day-by-day timeline, participant stepper, total for 2, choose date. Rule: participants ≤ remaining seats and ≤ group max (`BR-071`). |
| F-069 | Tour booking & payment | MOB | M-067 | P0 | C | Date strip with availability, departure time slots, pickup location, payment card, price for 2, pay. Output: confirmed booking. |
| F-070 | Tour confirmation & voucher | MOB | M-068 | P0 | C | Success, QR voucher, booking `TR-33107`, pickup instructions, free cancellation 24 h, contact guide, add to calendar, my bookings/home. |
| F-071 | Tour cancellation & refund | MOB | M-068 (promise only) | P2 | U | «إلغاء مجاني خلال 24 ساعة» is stated but no cancellation UI, deadline calculation or refund rule is drawn. Not committed until `Q-24`. |

### Domain 11 — Partner onboarding & portal authentication

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-072 | Partner portal login with account-type chooser | PUB | D-01.1 | P0 | C | Inputs: account type (Traway admin, tourism company, hotel, employee), email, password, remember-me. Output: routed to the correct portal. Rule: chosen type must match the account's actual role (`BR-080`). |
| F-073 | Partner forgot password | PUB | D-01.2 | P1 | C | Email → send reset link, back to login. Neutral response required. |
| F-074 | Company join request | PUB | D-02.1 | P0 | C | Inputs: activity type, company name, city, responsible person, phone, email, 3 uploads (tourism licence, commercial register, tax card), terms. Output: request `P-2291` acknowledgment. |
| F-075 | Hotel join request | PUB | D-02.2 | P0 | C | Same pattern for hotels: hotel name, city, hotel establishment licence, contact, documents, terms. |
| F-076 | Join request acknowledgment | PUB | D-02.3 | P0 | C | Shows request number, review within two business days, approval e-mail + activation link, «دخول الشركاء» / «تقديم طلب آخر». |
| F-077 | Partner application review & activation | ADM | D-03.6, D-02.3 | P0 | C+i | Admin reviews applicant data, requested services, commission terms (10 %), legal documents with per-document verification, accept/reject. Inferred: accept creates the partner + owner account and sends the activation link; reject reason is not drawn (`Q-26`). |

### Domain 12 — Admin portal (APP-ADM)

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-078 | Platform overview & KPIs | ADM | D-03.1 | P0 | C | KPIs: sales $96.4K, bookings 3,914, visa requests 612, eSIM sales 1,208, all with MoM deltas. Charts: monthly sales 2026, sales-by-service donut (flights 42 %, hotels 27 %, tours 16 %, visas 10 %, eSIM 5 %). Pending-approval queue, latest bookings table, export. |
| F-079 | Admin notifications | ADM | D-03.2 | P1 | C | All/unread filter (3 unread), time grouping, event types: partner join, hotel join, incomplete documents, eSIM bundle upload, account deletion request, refund request. Mark all read. Item → module is inferred. |
| F-080 | All-bookings oversight | ADM | D-03.3 | P0 | C | Tabs (all 8, flights 2, hotels 3, tours 1, visas 1, eSIM 1) — note the counts sum to 8 for 5 service tabs. Table: ref, customer, service, partner, amount, status. Export. Row detail is inferred (`Q-27`). |
| F-081 | Partner directory | ADM | D-03.4 | P0 | C | Tabs (tourism companies 3, hotels 4, join requests 3), add partner, table: partner, team size, active tours, tours/month, monthly sales, commission, status, actions. Rows → company/hotel detail; review → join review. |
| F-082 | Add partner | ADM | D-03.5 | P0 | C | Inputs: commission (default 10 %), partner type, company/hotel data, owner invite e-mail, phone, licence no., supported services (tours; UAE/Saudi/Turkey/Georgia visas). Output: owner invitation sent, partner created in pending state. |
| F-083 | Partner detail & suspension | ADM | D-03.7, D-03.8 | P0 | C | Company: sales $16.8K, tours 18, visa requests 142, rating 4.8, commission, allowed services, documents with expiry, team with owner flag, suspend. Hotel: revenue $22.4K, occupancy 82 %, 4 room types, rating 4.7, commission 15 %, services, documents, team, suspend. |
| F-084 | Visa catalogue management | ADM | D-03.9 | P0 | C | 48 countries with active toggles; per-country visa types (tourist 30 d $599, business 3–5 y $156, residence 10–15 y $290); required-document editor with mandatory/optional toggles; add document; add country modal. Platform Admin or the publishing company sets the price, which is then snapshotted on the application (DEC-06). |
| F-085 | eSIM catalogue & margin management | ADM | D-03.10 | P0 | C | Provider markup 35 %, total margin, wallet balance $4,210, provider block (146 countries, API-linked, term shows 12), packages with cost/sale/margin/availability, sync from API, price comparison, CSV export. |
| F-086 | Account-deletion request queue | ADM | D-03.11 | P1 | C | Filters (all, this month), count 14, table with request state. Row → customer is inferred. The queue is the operational control for the 7-day grace workflow (DEC-09). |
| F-087 | Customer directory | ADM | D-03.12 | P1 | C | KPIs: 58,214 customers, 21,930 active, $246 average booking, 14 deletion requests. Searchable table: customer, city, bookings, spend, last activity, status. Export. Row → customer detail. |
| F-088 | Customer detail & account actions | ADM | D-03.13 | P1 | C | KPIs ($657 spend, 5 bookings, 2 visa requests, 3 travellers), contact/profile, payment method, bookings table, saved travellers, block account, send report. Blocking rule unresolved (`Q-28`). |
| F-089 | Finance & commissions | ADM | D-03.14 | P0 | C | KPIs: $96.4K payments, $12.2K platform commission, $34.2K partner dues, $1.7K refunds. Partner-dues table (period, sales, commission, due, transfer status), transaction log (`PAY-xxxxx`, method, customer, amount, status), commission-by-service chart, export, transfer actions. Partner payouts go through the gateway on demand; negative balances offset future settlements (DEC-10). |
| F-090 | Roles & permissions (platform) | ADM | D-03.15 | P1 | C | Permission hierarchy: system admin, finance, content, support; partner owner (company/hotel); employees. Section × action matrix (view/add/edit/delete) for content manager, role list with exemption counts, create-role modal. Rule: a user can never grant a permission they do not hold (`BR-084`). |

### Domain 13 — Tourism-company portal (APP-COM)

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-091 | Company overview & today queue | COM | D-04.1 | P0 | C | KPIs (monthly sales $16.8K, bookings 186, visa requests 142, visa approval 96 %), 14-day bookings chart, “required today” queue (14 new visa requests, 5 incomplete docs, 9 new bookings, 3 tours starting), upcoming tours with occupancy, team activity feed, new tour. |
| F-092 | Company notifications | COM | D-04.2 | P1 | C | All/unread; events: new visa request, new booking, passport re-upload, tour approved, employee deactivated, settlement transferred. Mark all read. |
| F-093 | Tour portfolio management | COM | D-04.3 | P0 | C | Status tabs (7 published, 4 in review, 1 documents, 1 rejected, 1 expired), cards with occupancy/booked, edit, bookings, new tour. Rule: rejected/expired tours are not sold (`BR-090`). |
| F-094 | Tour editor & review submission | COM | D-04.4 | P0 | C | Pricing (price, child price, single supplement, net after 10 % commission), schedule slots with seats/status, includes checklist, basic info, gallery, day-by-day itinerary, save & submit for review, cancel. Rule: submission requires complete mandatory fields and ≥1 future slot (`BR-091`). |
| F-095 | Company bookings | COM | D-04.5 | P0 | C | Tabs (all, new, confirmed, completed, cancelled), table: ref, customer, service, date, amount, status, export. Detail view not drawn (`Q-30`). |
| F-096 | Visa request review workspace | COM | D-04.6 | P0 | C | Tabs (all, new, incomplete documents, ready to submit), review progress (1 of 5 completed), applicant queue, per-document accept/reject, accept all, next applicant. Rule: a request cannot advance while a mandatory document is rejected (`BR-095`). |
| F-097 | Team management | COM | D-04.7 | P1 | C | Role catalogue (owner, bookings employee, visa employee, accountant, content manager), member table (role, last activity, status), invite employee, edit role. Rule: only the owner can transfer ownership (`BR-086`). |
| F-098 | Custom role editor | COM | D-04.8 | P1 | C | Role name, base preset, section toggles with fine-grained actions (view/add/edit/delete/publish/confirm/refund), sidebar preview, save. Rule: permissions are scoped to the partner tenant only (`BR-085`). |
| F-099 | Financial reports & settlements | COM | D-04.9 | P1 | C | Period filter (7 days / month / year), KPIs (sales, bookings, Traway commission, net), upcoming settlements, weekly chart, sales by tour table, card on file, Excel export. |
| F-100 | Company settings | COM | D-04.10 | P1 | C | Tabs (company data, account, documents); logo upload, name, licence no., phone, email, address, customer-facing description, save. Rule: licence changes require re-verification (`BR-101`). |

### Domain 14 — Hotel portal (APP-HTL)

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-101 | Hotel overview | HTL | D-05.1 | P0 | C | KPIs (occupancy 82 %, nightly revenue $22.4K, arrivals today 14, ADR $113), availability by room type, 14-day occupancy chart, today's arrivals with check-in status, update prices. |
| F-102 | Hotel notifications | HTL | D-05.2 | P1 | C | All/unread; events: new booking, 14 arrivals, private-branch quota near full, cancellation `TW-48184`, settlement transferred. |
| F-103 | Rooms & rates calendar | HTL | D-05.3 | P0 | C | Room-type cards with from-price, new room type, per-day price/availability with stop-sell and almost-full states, bulk adjust ±$10, confirm/reset. Rule: stop-sell blocks new bookings for that date (`BR-106`). |
| F-104 | Hotel bookings & check-in/out | HTL | D-05.4 | P0 | C | Tabs (all 7, arriving today 2, in-house 2, upcoming 2, cancelled 1), table: ref, guest, room type, dates, amount, status, check-in/out actions, export. |
| F-105 | Hotel employee management | HTL | D-05.5 | P1 | C | Role permission panel (reception, bookings manager, accountant) with toggles (view bookings, check-in/out, update prices, stop-sell, financial reports, export), employee list with shift/status, add employee. |
| F-106 | Hotel profile & publishing | HTL | D-05.6 | P1 | C | Gallery, amenities, basic data (name, category, city, address, check-in/out times, phone, description, cancellation policy), map location, save & publish. Rule: publishing requires approved legal documents (`BR-110`). |
| F-107 | Hotel reports | HTL | D-05.7 | P2 | C | Period filter, KPIs (revenue, occupancy, ADR, cancellation rate), guest-source breakdown (Egypt 58 %, Saudi/Gulf 21 %, Europe 16 %, other 5 %), weekly revenue chart, performance by room type, Excel export. |

### Domain 15 — Restricted employee portal (APP-EMP)

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-108 | Assigned visa-request queue | EMP | D-06.1 | P1 | C | «صلاحيات محددة» banner, request queue, per-document accept/reject, request additional permission from owner. Rule: employee sees only assigned requests (`BR-115`). |
| F-109 | Read-only bookings | EMP | D-06.2 | P1 | C | Read-only booking table, permission banner, no mutation affordances. Rule: no write endpoints are exposed to this role (`BR-116`). |
| F-110 | Employee notifications | EMP | D-06.3 | P2 | C | Assigned-request notifications; locked sidebar entries (team, finance, settings) shown but disabled. Rule: locked modules must be enforced server-side, not only hidden (`BR-117`). |

### Domain 16 — Coupons & authorization administration

| ID | Feature | App | Screens | Pri | Ev | Notes — inputs, outputs, rules |
|---|---|---|---|---|---|---|
| F-111 | Coupon management | ADM / COM | — | P1 | D | Decision DEC-03. Platform Admin creates global coupons; company owners create coupons restricted to their own company. Fields: code, discount type (percentage/fixed), value, service scope (all / flights / hotels / visas), validity window, minimum spend, total usage limit, per-user limit, active state. One coupon per booking; no first-booking restriction; redemption is server-validated and counted atomically so concurrent attempts cannot exceed the limit (`BR-015`). Hotel-owner coupon scope is Q-41. |
| F-112 | Dynamic roles, permissions & audit | ADM / COM / HTL | D-03.15, D-04.7, D-04.8, D-05.5 | P0 | D | Authorization requirement. Platform roles and tenant roles are data, not code. Platform Admin creates platform roles and may assign tenant roles/permissions through a dedicated permission; company and hotel owners invite employees, create organization roles, assign roles and grant bounded direct permissions. Service scope, grant ceilings, anti-escalation, lifecycle rules, enforcement layers and audit are specified in `AUTHORIZATION-ARCHITECTURE.md` and the 78-key catalog in `ROLE-PERMISSION-SCOPE-MATRIX.md`. |

---

## 3. Screen → feature traceability

Every one of the 108 Phase 1 screen states appears exactly once below (68 mobile in two columns, then 40 dashboard in two columns). A screen with no feature would be an analysis failure; none exists.

### 3.1 Mobile screens (68 / 68)

| # | Screen | Feature(s) | Screen | Feature(s) |
|---:|---|---|---|---|
| 1 | M-001 | F-001 | M-002 | F-002 |
| 2 | M-003 | F-002 | M-004 | F-002 |
| 3 | M-005 | F-003, F-004, F-005 | M-006 | F-005, F-006 |
| 4 | M-007 | F-004 | M-008 | F-007 |
| 5 | M-009 | F-008 | M-010 | F-009 |
| 6 | M-011 | F-010 | M-012 | F-011 |
| 7 | M-013 | F-012 | M-014 | F-003, F-014 |
| 8 | M-015 | F-015 | M-016 | F-016 |
| 9 | M-017 | F-017 | M-018 | F-018 |
| 10 | M-019 | F-016, F-017, F-018, F-022 | M-020 | F-023 |
| 11 | M-021 | F-024 | M-022 | F-025 |
| 12 | M-023 | F-026, F-052 | M-024 | F-028, F-029, F-059 |
| 13 | M-025 | F-030 | M-026 | F-027 (U) |
| 14 | M-027 | F-031 | M-028 | F-032 |
| 15 | M-029 | F-033 | M-030 | F-034 |
| 16 | M-031 | F-035, F-037 | M-032 | F-036, F-037, F-038 |
| 17 | M-033 | F-039 | M-034 | F-040 |
| 18 | M-035 | F-041 | M-036 | F-042 |
| 19 | M-037 | F-043, F-044 | M-038 | F-045 |
| 20 | M-039 | F-046 | M-040 | F-047 |
| 21 | M-041 | F-048 | M-042 | F-049 |
| 22 | M-043 | F-050 | M-044 | F-051 |
| 23 | M-045 | F-051, F-052 | M-046 | F-052, F-053 |
| 24 | M-047 | F-052 | M-048 | F-021 |
| 25 | M-049 | F-056 | M-050 | F-059 |
| 26 | M-051 | F-060 | M-052 | F-059, F-060 |
| 27 | M-053 | F-055 | M-054 | F-054 |
| 28 | M-055 | F-058 | M-056 | F-057 |
| 29 | M-057 | F-019 | M-058 | F-020 |
| 30 | M-059 | F-061 | M-060 | F-062 |
| 31 | M-061 | F-063 | M-062 | F-064 |
| 32 | M-063 | F-051, F-065 | M-064 | F-066 |
| 33 | M-065 | F-067 | M-066 | F-068 |
| 34 | M-067 | F-069 | M-068 | F-070, F-071 (U) |

### 3.2 Dashboard screens (40 / 40)

| # | Screen | Feature(s) | Screen | Feature(s) |
|---:|---|---|---|---|
| 1 | D-01.1 | F-072 | D-01.2 | F-073 |
| 2 | D-02.1 | F-074 | D-02.2 | F-075 |
| 3 | D-02.3 | F-076, F-077 | D-03.1 | F-078 |
| 4 | D-03.2 | F-079 | D-03.3 | F-080 |
| 5 | D-03.4 | F-081 | D-03.5 | F-082 |
| 6 | D-03.6 | F-077, F-081 | D-03.7 | F-083 |
| 7 | D-03.8 | F-083 | D-03.9 | F-033, F-084 |
| 8 | D-03.10 | F-085 | D-03.11 | F-057, F-086 |
| 9 | D-03.12 | F-087 | D-03.13 | F-088 |
| 10 | D-03.14 | F-089 | D-03.15 | F-090 |
| 11 | D-04.1 | F-091 | D-04.2 | F-092 |
| 12 | D-04.3 | F-093 | D-04.4 | F-094 |
| 13 | D-04.5 | F-095 | D-04.6 | F-037, F-096 |
| 14 | D-04.7 | F-097 | D-04.8 | F-098 |
| 15 | D-04.9 | F-089, F-099 | D-04.10 | F-100 |
| 16 | D-05.1 | F-101 | D-05.2 | F-102 |
| 17 | D-05.3 | F-103 | D-05.4 | F-104 |
| 18 | D-05.5 | F-105 | D-05.6 | F-106 |
| 19 | D-05.7 | F-107 | D-06.1 | F-108 |
| 20 | D-06.2 | F-109 | D-06.3 | F-110 |

### 3.3 Non-screen assets (11 / 11)

Chapter-title strips `MT-01` – `MT-10` and annotation `DL-01` carry no interaction; they are accounted for in Phase 1 and require no feature. They confirm section grouping only (chapters ٢, ٣, ٣ب, ٤, ٥, ٦, ٧, ٧ب, ٨, ٩).

**F-111 and F-112 have no Phase 1 screen by design.** They are decision/requirement-backed (DEC-03 and the authorization requirement) and are anchored to the drawn role editors D-03.15, D-04.7, D-04.8 and D-05.5 rather than to a new screen.

---

## 4. Cross-application relationships

| Relationship | Direction | Evidence | Implication |
|---|---|---|---|
| Mobile booking → company/hotel portal | MOB → COM/HTL | M-025/M-064 confirmed bookings; D-04.5/D-05.4 partner booking tables | A confirmed booking must materialise in the owning partner's portal with the same reference. |
| Mobile visa request → company visa desk → admin | MOB → COM → ADM | M-030/M-031 → D-04.6 → D-03.2/D-03.3 | One visa application is visible to three applications with different action sets. |
| Admin catalogue → mobile display | ADM → MOB | D-03.9 (fees, documents) → M-029/M-028; D-03.10 (eSIM packages) → M-034/M-035 | Mobile pricing and requirements are administered, not hard-coded. |
| Partner joining → admin review → partner portal | PUB → ADM → COM/HTL | D-02.1/D-02.2 → D-03.6 → D-04.1/D-05.1 | Account creation is gated by document verification, not self-service. |
| Admin finance ↔ partner settlements | ADM ↔ COM | D-03.14 partner dues → D-04.9 upcoming settlements | One settlement record drives both views; amounts must agree. |
| Company team roles → employee portal | COM → EMP | D-04.7/D-04.8 permissions → D-06.1 – D-06.3 locked modules | EMP is a restricted view of the company tenant, not a separate tenant. |
| Admin role matrix → all portals | ADM → all | D-03.15 | Platform roles and partner roles are two distinct permission families sharing one authorisation model. |
| Hotel portal ↔ hotel bookings | MOB ↔ HTL | M-063/M-064 ↔ D-05.1/D-05.4 | Rates, stop-sell and availability set in D-05.3 govern what M-059/M-062 can sell. |
| Coupon scope → mobile checkout | ADM/COM → MOB | D-03.15, D-04.8; M-024, M-057 | One coupon per booking; service scope (flights/hotels/visas) and usage limits are enforced server-side, never by the client. |
| Role & permission administration → all portals | ADM ↔ COM/HTL | D-03.15, D-04.7, D-04.8, D-05.5 | One authorization model with two permission families; platform staff administer tenants only through an explicit audited permission. |

---

## 5. Coverage audit

### 5.1 Screen coverage

| Check | Result |
|---|---|
| Phase 1 mobile states mapped | 68 / 68 |
| Phase 1 dashboard states mapped | 40 / 40 |
| **Total mapped** | **108 / 108** |
| Screens with no feature | 0 |
| Features with no screen | 2 (F-111, F-112 — decision/requirement-backed; F-013 remains the deliberate cross-cutting session rule) |
| Duplicate mapping errors | 0 (each screen appears once) |

### 5.2 Feature distribution

| Application | Features |
|---|---|
| MOB | F-001 – F-071 (71 features, including 3 unresolved) |
| PUB | F-072 – F-076 (5) |
| ADM | F-077 – F-090 (14) |
| COM | F-091 – F-100 (10) |
| HTL | F-101 – F-107 (7) |
| EMP | F-108 – F-110 (3) |
| Cross-cutting (ADM + COM; ADM + COM + HTL) | F-111 – F-112 (2) |
| **Total** | **112** |

### 5.3 Unresolved features (not committed to v1)

| Feature | Why unresolved | Question |
|---|---|---|
| F-027 Seat selection | Only a confirmation-bar fragment supplied | Q-09 |
| F-043 eSIM usage & balance | Balance shown, no usage screen or rules | Q-17 |
| F-071 Tour cancellation | Promise text only, no flow | Q-24 |

All other features are either confirmed (`C`) or confirmed with named inferred mechanics (`C+i`); inferred mechanics are answered by `OPEN-QUESTIONS.md` before Phase 3 schema work.

---

## 6. Statistics

| Metric | Value |
|---|---|
| Applications covered | 6 (5 products + shared public surface) |
| Screen states traced | 108 / 108 |
| Features identified | 112 |
| Confirmed features (`C`) | 102 |
| Confirmed with inferred mechanics (`C+i`) | 5 (F-003, F-005, F-013, F-037, F-077) |
| Decision/requirement-backed (`D`) | 2 (F-111, F-112) |
| Unresolved features (`U`) | 3 (F-027, F-043, F-071) |
| Priority P0 / P1 / P2 / P3 | 64 / 37 / 9 / 2 |
| Business rules extracted | 125 (see `BUSINESS-LOGIC.md`) |
| Roles defined | 17 (see `USER-ROLES-AND-PERMISSIONS.md`) |
| Permission keys | 78 (33 platform, 45 organization — see `ROLE-PERMISSION-SCOPE-MATRIX.md`) |
| Workflows documented | 15 (see `WORKFLOWS.md`) |
| Open questions raised | 52 (see `OPEN-QUESTIONS.md`) |
| Phase 1 inconsistencies addressed | 11 / 11 |
| Phase 1 coverage gaps addressed | 15 / 15 |

---

## 7. Requirement classification summary

| Class | Count | Treatment |
|---|---|---|
| **Confirmed** — drawn and unambiguous | 102 features | Baseline for Phase 3 data model. |
| **Inferred** — feature drawn, mechanics not | 5 features + 31 inferred rules in `BUSINESS-LOGIC.md` | Implementable, but each inference has a question ID and must be signed off. |
| **Decision/requirement-backed** — no screen | 2 features (F-111, F-112) + 28 decision rules | Mandatory because the owner decided it or the authorization requirement demands it; traced to DEC-03 / the requirement. |
| **Unresolved** — concept drawn, behaviour absent | 3 features + 15 gap areas | Explicitly excluded from v1 scope until answered; listed in `OPEN-QUESTIONS.md`. |

No screen was used to justify a feature it does not support, and no gap was silently converted into a requirement. The two screenless features are explicitly marked so no reviewer mistakes them for pixel-confirmed scope.

