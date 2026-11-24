# Phase 1 — Complete UI Screens Inventory

**Project:** Traway — travel super-app (mobile) + multi-portal partner/admin dashboards
**Phase:** 1 of 7 — UI Discovery
**Status:** Complete — awaiting approval (`APPROVE PHASE 1`)
**Prepared by:** Senior Architecture / Backend Analysis
**Date:** 2026-11-23

---

## 1. Purpose and method

This document is the authoritative inventory of every UI artifact supplied for the Traway project. It is the traceability root for all later phases (features → database → APIs → tasks).

Method:

1. Recursive enumeration of `traway-mobile` and `traway-Dashboards` (PowerShell `Get-ChildItem -Recurse`).
2. Image metadata extraction (dimensions, byte size) with `System.Drawing`.
3. Visual inspection of **every** image with the vision reader.
4. The four ultra-wide dashboard boards (up to 18,006 px wide) were additionally sliced into **34 overlapping 1,500 px crops** at 1:1 pixel scale and each crop was inspected, because whole-board downscaling destroys legibility.
5. Screen states, interactive elements, labels, statuses, IDs, amounts and navigation affordances were transcribed from the pixels.
6. No functionality was inferred from filenames alone. Where a behaviour is invisible in the design, it is recorded as an assumption or gap, never as a requirement.

### Corpus summary

| Metric | Value |
|---|---|
| Directories inspected | 2 (`traway-mobile`, `traway-Dashboards`) |
| Nested sub-directories | 0 (all files sit at the directory root) |
| Total files discovered | **85** |
| Total image files | **85** (100 % PNG) |
| Non-image / design source files (.fig, .sketch, .xd, .pdf, .svg) | **0** |
| Total images visually analyzed | **85** |
| Unreadable / corrupt / unsupported files | **0** |
| Mobile image files | 78 |
| Dashboard image files | 7 |
| Exact duplicate files (MD5) | **0** |
| Distinct screen / overlay states identified | **108** (68 mobile + 40 dashboard) |
| Non-screen design assets | **11** (10 mobile chapter title strips + 1 dashboard annotation label) |
| Component fragment captures | 1 (`Bottom Bar.png` — seat-confirmation strip) |

### Image profile

| Group | Typical size | Notes |
|---|---|---|
| Mobile screens | 293×633 – 353×693 px | Phone frame captures, readable |
| Mobile long capture | 390×2751 px (`Iframe.png`) | Full vertically-scrolled home page |
| Mobile title strips | 157×28 – 535×37 px | Transparent-background section titles only |
| Dashboard login board | 2,598×1,224 px | 2 screens side by side |
| Dashboard join board | 1,802×928 px | 3 states side by side |
| Admin board | 18,006×1,423 px | 15+ screen states |
| Company board | 12,870×1,260 px | 10 screen states |
| Hotel board | 9,018×1,182 px | 7 screen states |
| Employee board | 3,882×1,103 px | 3 screen states |
| Dashboard annotation | 181×11 px | Text label component, not a screen |

---

## 2. Application taxonomy

Five distinct front-ends are represented. This separation is a **confirmed** design fact (it drives the role model in later phases).

| Code | Application | Audience | Evidence |
|---|---|---|---|
| **APP-MOB** | Traway traveller mobile app (Arabic RTL, English toggle) | End customers | 68 screens, bottom tab bar |
| **APP-ADM** | Traway platform administration portal | Platform owner / Traway staff | Board «٢ — لوحة إدارة Traway» |
| **APP-COM** | Tourism-company partner portal | Partner agencies (e.g. «رحلات النيل») | Board «٣ — لوحة شركة السياحة» |
| **APP-HTL** | Hotel partner portal | Partner hotels (e.g. «منتجع شاطئ النخلة») | Board «٤ — لوحة الفندق» |
| **APP-EMP** | Restricted staff portal (same shell as APP-COM, limited permissions) | Company employee (e.g. visa officer «عمر حسني») | Board «٥ — لوحة الموظف» |

Plus **APP-PUB** — the partner onboarding / login web surface (`Join Form.png`, `١ — الدخول وطلب الانضمام.png`) shared by APP-ADM / APP-COM / APP-HTL.

### Mobile module map

| Module | Screens | Feature domain |
|---|---|---|
| Onboarding | 4 | Brand, value proposition, permission priming |
| Authentication & Account Creation | 9 | Phone/email login, OTP, signup, verification, password reset |
| Home & Discovery | 5 | Home feed, global search, city/date/passenger pickers |
| Flights | 8 | Search → filters → results → return leg → passengers → payment → confirmation |
| Visas | 6 | Country → visa type → detail → document upload → request status |
| eSIM | 4 | Country → package → purchase → QR activation |
| Bookings | 6 | My bookings, booking detail, e-ticket, reschedule, baggage, cancellation |
| Profile & Settings | 14 | Profile, travellers, passport scan, payments, favourites, support, legal, deletion |
| Hotels | 6 | Results → filters → map → detail → booking/payment → confirmation |
| Tours | 4 | Catalogue → programme detail → booking/payment → confirmation |
| Offers & Destinations | 2 | Promotions list, featured destinations |

---

## 3. MOBILE INVENTORY (APP-MOB)

Legend for **Navigation**: `→` = explicit affordance visible in the capture; `(i)` = inferred from context/standard pattern and flagged for confirmation.

### 3.1 Onboarding

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-001 | Onboarding | سبلاش / Splash | `traway-mobile/Splash.png` | Brand splash: logo, tagline «رحلتك من الفكرة… حتى الوصول», feature chips (طيران، فنادق، تأشيرات، eSIM) | auto → M-002 (i) | Analyzed |
| M-002 | Onboarding | on boarding - الحجز / Onboarding 1 of 3 — Booking | `traway-mobile/on boarding - الحجز.png` | Value prop: one place for flights, hotels, trips; comparison of prices | تخطي → M-005; التالي → M-003 | Analyzed |
| M-003 | Onboarding | Onboarding 2 of 3 — Visas | `traway-mobile/Onboarding · التأشيرات.png` | Visa promise: choose country/type, see required documents, upload from phone (PDF/JPG) | تخطي → M-005; التالي → M-004 | Analyzed |
| M-004 | Onboarding | 04 Onboarding 3 of 3 — eSIM | `traway-mobile/04 Onboarding · eSIM.png` | eSIM promise: buy on arrival, QR activation, 10 GB Italy example | تخطي/البدء → M-005 | Analyzed |

### 3.2 Authentication & account creation

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-005 | Auth | 05 تسجيل الدخول / Login — phone tab | `traway-mobile/05 تسجيل الدخول.png` | Country code selector (+20), phone input, OTP send, Apple/Google social buttons, browse-as-guest, legal consent text | إرسال رمز التحقق → M-007; tab → M-006; Apple/Google → M-014 (i); تصفح كضيف → M-014 | Analyzed |
| M-006 | Auth | 05ب دخول بالبريد / Login — email tab | `traway-mobile/05ب دخول بالبريد.png` | Email + password, show/hide password, remember me, forgot password, social login, create account link | تسجيل الدخول → M-014; نسيت كلمة المرور → M-012; إنشاء حساب جديد → M-008 | Analyzed |
| M-007 | Auth | 06 رمز التحقق OTP / SMS OTP | `traway-mobile/06 رمز التحقق OTP.png` | 4-digit OTP cells, masked phone, resend countdown (00:42), «لم يصل الرمز؟ أرسل عبر واتساب» | تأكيد → M-014 or M-011 (i); تعديل → M-005 | Analyzed |
| M-008 | Auth | 05ج إنشاء حساب بالبريد / Create account (email) | `traway-mobile/05ج إنشاء حساب بالبريد.png` | Full name, email, password + strength meter (length, upper/lower, symbol, number), terms checkbox | إنشاء الحساب → M-009; تسجيل دخول → M-005 | Analyzed |
| M-009 | Auth | 05د تأكيد البريد / Verify email | `traway-mobile/05د تأكيد البريد.png` | 6-digit email code, resend countdown 00:58, open-mail-app button | تأكيد البريد → M-010 (i); تغيير البريد → M-008 | Analyzed |
| M-010 | Auth | 05هـ الحساب جاهز / Account ready | `traway-mobile/05هـ الحساب جاهز.png` | Success state; optional prompts to add phone and passport; primary CTA «ابدأ الاستكشاف» | add phone (i), add passport → M-047; ابدأ الاستكشاف → M-014 | Analyzed |
| M-011 | Auth | 07 إكمال الحساب / Complete profile | `traway-mobile/07 إكمال الحساب.png` | First/last name, email, gender, birth date, nationality, marketing opt-in | إنشاء الحساب → M-014 (i) | Analyzed |
| M-012 | Auth | 05و نسيت كلمة المرور / Forgot password | `traway-mobile/05و نسيت كلمة المرور.png` | Email input, send reset code, spam-folder hint | إرسال كود الاستعادة → M-013; العودة لتسجيل الدخول → M-006 | Analyzed |
| M-013 | Auth | 05ز كلمة مرور جديدة / Reset password | `traway-mobile/05ز كلمة مرور جديدة.png` | Recovery code, new password + strength, confirm match validation | حفظ وتسجيل الدخول → M-006 | Analyzed |

### 3.3 Home & discovery

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-014 | Home | الرئيسية / Home (full-page capture) | `traway-mobile/Iframe.png` | Greeting + notification bell, product tabs (طيران، فنادق، رحلات), flight search card (CAI→DXB, dates, 1 adult economy), quick cards for eSIM & visas, upcoming-trip card (DXB in 15 days, eSIM/visa/ticket progress), offers carousel, featured destinations, hotels, tours, visas by country, trust section | product tabs switch the search panel in place (i); flight search → M-019; bell → M-048; header search → M-015; eSIM card → M-033; visa card → M-027; trip card → M-038; عرض الكل (offers) → M-057; عرض الكل (destinations) → M-058; عرض الكل (hotels) → M-059; عرض الكل (tours) → M-065; visa country chip → M-028 | Analyzed |
| M-015 | Search | 31 البحث / Global search | `traway-mobile/31 البحث.png` | Unified search with filter chips (الكل، وجهات، فنادق، eSIM، تأشيرات), mixed results (destination, hotel, eSIM, visa) | result row → M-014/M-062/M-034/M-028 (i); back → M-014 | Analyzed |
| M-016 | Search | 25 اختيار المدينة / City & airport picker | `traway-mobile/25 اختيار المدينة.png` | Search airport/city, recent searches, popular destinations from Cairo (DXB, JED, RUH, IST, AYT, CDG, LHR, KUL) | select → back to M-019 | Analyzed |
| M-017 | Search | 26 التقويم / Date range picker | `traway-mobile/26 التقويم.png` | Departure/return dates, 2-month calendar with per-day prices, «أرخص الأسعار» toggle, flexible ±3 days | تأكيد → M-019 | Analyzed |
| M-018 | Search | 27 المسافرين والدرجة / Passengers & cabin class | `traway-mobile/27 المسافرين والدرجة.png` | Adults (12+), children (2–11), infants (<2) steppers with limits (9 passengers, 1 infant per adult), cabin (economy, premium, business, first) | تأكيد → M-019 | Analyzed |

### 3.4 Flights

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-019 | Flights | 09 بحث الطيران / Flight search | `traway-mobile/09 بحث الطيران.png` | Trip type (one-way/round/multi-city), from/to with swap, dates, passengers/class, direct-only toggle, popular destinations from Cairo | بحث عن رحلات → M-021; dates → M-017; passengers → M-018; cities → M-016 | Analyzed |
| M-020 | Flights | 28 فلاتر الطيران / Flight filters | `traway-mobile/28 فلاتر الطيران.png` | Stops, departure window, price range slider ($172–$290 in capture), airlines with min fares, baggage-included toggle, reset/apply (12 offers) | apply → M-021 | Analyzed |
| M-021 | Flights | 10 نتائج الرحلات / Flight results | `traway-mobile/10 نتائج الرحلات.png` | Date strip with fares, sort (cheapest/fastest/direct), filters, result cards (airline, times, duration, stops, price, refundability), hotel cross-sell banner | تعديل → M-019; filter → M-020; اختيار الرحلة → M-022 (round trip) or M-023 (one-way) (i); hotel banner → M-062 | Analyzed |
| M-022 | Flights | 10أ اختيار رحلة العودة / Return flight selection | `traway-mobile/10أ اختيار رحلة العودة.png` | Return-leg results (DXB→CAI) with 18-day validity notice, same card pattern, «اختيار العودة» | اختيار العودة → M-023 | Analyzed |
| M-023 | Flights | 10ب بيانات المسافرين / Passenger details | `traway-mobile/10ب بيانات المسافرين.png` | Trip summary, traveller cards (adult prefilled, second adult with first/family name + passport number, inline validation), add child (2–11), additional baggage (+$19), total for 3 passengers, proceed to payment | مسح الجواز → M-047; متابعة للدفع → M-024 | Analyzed |
| M-024 | Flights | 10ج الدفع / Payment | `traway-mobile/10ج الدفع.png` | 14:32 fare-hold timer, payment methods (card, e-wallet, Apple Pay), card fields (number, expiry, CVV), price breakdown with promo code TRAWAY10 (−$24), total $511 | ادفع → M-025; add card → M-050 (i) | Analyzed |
| M-025 | Flights | 10د تأكيد الحجز / Booking confirmed | `traway-mobile/10د تأكيد الحجز.png` | Success overlay, PNR TRW7K2, CAI→DXB, 3h35m, date/time, 3 passengers, economy, total, upsell to UAE eSIM and UAE visa, «عرض الحجز» / «الرئيسية» | عرض الحجز → M-038; eSIM → M-034; visa → M-028 | Analyzed |
| M-026 | Flights | Bottom Bar / Seat confirmation fragment | `traway-mobile/Bottom Bar.png` | Partial capture of a seat-selection confirmation bar: 3 seats (12A, 12B, 12C), «مجاناً», CTA «تأكيد المقاعد» | (seat map not supplied) → M-024 (i) | Analyzed — **fragment only, parent seat-map screen missing** |

### 3.5 Visas

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-027 | Visas | 11 التأشيرات · الدول / Visa countries | `traway-mobile/11 التأشيرات · الدول.png` | Search country, region chips (الكل، الخليج، أوروبا وشنجن، آسيا), country rows with visa-type counts (السعودية 3، الإمارات 3، تركيا 2، فرنسا 2، ألمانيا 2) | country row → M-028; bottom tabs | Analyzed |
| M-028 | Visas | 12 التأشيرات · الأنواع / Visa types | `traway-mobile/12 التأشيرات · الأنواع.png` | Country hero, visa-type cards with duration, entry type (متعدد/مفرد), document count (سياحة، عمل، زيارة عائلية) | type card → M-029; back → M-027 | Analyzed |
| M-029 | Visas | 13 تفاصيل التأشيرة / Visa type detail | `traway-mobile/13 تفاصيل التأشيرة.png` | Stay validity (90 days), visa validity (1 year), entry type, fee «[حسب لوحة التحكم]», required documents list (passport, photo, flight booking, hotel booking, bank statement) with PDF/JPG/PNG ≤10 MB | ابدأ رفع المستندات → M-030; favourite | Analyzed |
| M-030 | Visas | 14 رفع المستندات / Upload documents | `traway-mobile/14 رفع المستندات.png` | Upload progress (3 of 5, 60 %), per-document status, in-progress upload, camera/upload actions, optional doc, save (2 remaining), data-sharing consent note | حفظ المستندات → M-031 (i) | Analyzed |
| M-031 | Visas | 14ب تم إرسال طلب التأشيرة / Visa request submitted | `traway-mobile/14ب تم إرسال طلب التأشيرة.png` | Success state, request no. TRW-V-2291, review SLA 24–48 h, follow-up button | متابعة الطلب → M-032; tab الحجوزات → M-037 | Analyzed |
| M-032 | Visas | مستندات التاشيرات / Visa document tracker | `traway-mobile/مستندات التاشيرات.png` | Per-application document checklist (2 of 5 missing), uploaded/required states with file types, complete-upload CTA, other application (UAE, 5/5 complete), reusable saved documents (passport, personal photo) | أكمل رفع المستندات → M-030 | Analyzed |

### 3.6 eSIM

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-033 | eSIM | 15 eSIM · الدول / eSIM countries | `traway-mobile/15 eSIM · الدول.png` | Search country/region, device eSIM compatibility check, country vs region mode, popular countries with package counts and data ranges | country card → M-034; التحقق الآن (device check) (i) | Analyzed |
| M-034 | eSIM | 16 eSIM · الباقات / eSIM packages | `traway-mobile/16 eSIM · الباقات.png` | Country hero, package selector (3 GB/7 d $9; 5 GB/15 d $14 best value; 10 GB/30 d $23), coverage notes, total, continue | متابعة → M-035 | Analyzed |
| M-035 | eSIM | 17 eSIM · تفاصيل وشراء / eSIM checkout | `traway-mobile/17 eSIM · تفاصيل وشراء.png` | Plan facts (coverage, speed 4G/5G, provider, refund policy), payment method (card / e-wallet), promo code, total incl. tax $14, pay securely | ادفع بأمان → M-036 | Analyzed |
| M-036 | eSIM | 18 eSIM · التفعيل QR / eSIM activation QR | `traway-mobile/18 eSIM · التفعيل QR.png` | Order no. TW-58210, QR code, manual activation string (LPA:1$…), install-on-this-device, view my bookings | عرض في حجوزاتي → M-037 | Analyzed |

### 3.7 Bookings management

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-037 | Bookings | 19 حجوزاتي / My bookings | `traway-mobile/19 حجوزاتي.png` | Tabs (القادمة، السابقة، الملغاة), trip card with countdown (15 days), booking ref, flight detail, eSIM add-on progress (5 GB unused), visa docs incomplete warning, upload CTA | card → M-038; eSIM QR → M-036; visa docs → M-032 | Analyzed |
| M-038 | Bookings | تفاصيل الحجوازات / Booking details | `traway-mobile/تفاصيل الحجوازات.png` | Tabs (الرحلة، المسافرين، الدفع), outbound/return flight cards with PNR, baggage, meals, actions (إلغاء الحجز، إضافة حقيبة، تغيير الموعد), download e-ticket, support | e-ticket → M-039; change date → M-040; baggage → M-041; cancel → M-042 | Analyzed |
| M-039 | Bookings | 19ج التذكرة الإلكترونية / E-ticket | `traway-mobile/19ج التذكرة الإلكترونية.png` | Passenger selector chips, boarding-pass style ticket (CAI→DXB, 3h35m, flight MS 912, date, boarding 07:25, seat 14A, gate, baggage 23 kg), QR, airport arrival advisory, download PDF / add to wallet | share; download PDF; add to wallet | Analyzed |
| M-040 | Bookings | 19د تغيير الموعد / Reschedule flight | `traway-mobile/19د تغيير الموعد.png` | Departure/return date fields, calendar with alternate fares, change fee + fare difference (+$65), total difference 135, confirm | تأكيد الموعد الجديد → M-038 (i) | Analyzed |
| M-041 | Bookings | 19هـ إضافة حقيبة / Add baggage | `traway-mobile/19هـ إضافة حقيبة.png` | Baggage steppers (23 kg $24, 32 kg $35, sports equipment $19), extra weight options, total $245, add to booking | إضافة للحجز → M-038 (i) | Analyzed |
| M-042 | Bookings | 19و إلغاء الحجز / Cancel booking | `traway-mobile/19و إلغاء الحجز.png` | Refund breakdown (3605 of 1515 fee), refund-to-original-method SLA 7–14 days, irreversibility notice, optional reason chips, typed confirmation «إلغاء», confirm/cancel | تأكيد إلغاء الحجز → M-037 (i) | Analyzed |

### 3.8 Profile, travellers, payments & settings

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-043 | Profile | 20 حسابي / My account | `traway-mobile/20 حسابي.png` | Avatar + name/phone, edit profile, saved travellers (3), visa documents (2 missing), payment methods & history, favourites, language/currency (العربية · دولار $), notifications toggle, customer service | edit → M-046; travellers → M-044; visa docs → M-032; payments → M-052; favourites → M-054; support → M-055 | Analyzed |
| M-044 | Profile | المسافرون المحفوظون / Saved travellers | `traway-mobile/المسافرون المحفوظون.png` | Traveller cards with relation, passport no., expiry, status badges (ساري/ينتهي قريباً), edit, add traveller | edit → M-045; add → M-045 (new) (i) | Analyzed |
| M-045 | Profile | 20ج بيانات المسافر / Traveller data | `traway-mobile/20ج بيانات المسافر.png` | First/family name (EN), email, phone, gender, passport no., expiry with validity warning, passport OCR scan, save | مسح الجواز → M-047; حفظ → M-044 | Analyzed |
| M-046 | Profile | تعديل البيانات / Edit personal data | `traway-mobile/تعديل البيانات.png` | Names, email with verified badge, phone, gender, passport no./expiry, profile completeness 80 %, save changes | save → M-043; scan passport → M-047 | Analyzed |
| M-047 | Profile | 37 مسح الجواز / Passport scan (OCR) | `traway-mobile/37 مسح الجواز.png` | Camera capture frame with MRZ detection, extracted data preview (P<EGYHASSAN<<MARIAM…), retake/gallery/flash, manual entry | confirm → M-045/M-046 | Analyzed |
| M-048 | Notifications | 32 الإشعارات / Notifications | `traway-mobile/32 الإشعارات.png` | Filter chips (الكل، الحجوزات، العروض، المدفوعات), grouped by اليوم/أمس/هذا الأسبوع, unread dots, mark-all-read, deep-link items | item → related booking/visa/eSIM (i) | Analyzed |
| M-049 | Legal | 33 الشروط والخصوصية / Terms & privacy | `traway-mobile/33 الشروط والخصوصية.png` | Tabbed terms / privacy policy, last-updated date, numbered legal sections, agree button | موافق → back (i) | Analyzed |
| M-050 | Payments | 34 إضافة بطاقة / Add card | `traway-mobile/34 إضافة بطاقة.png` | Card preview, number, name, expiry, CVV, set-default toggle, PCI note, save | حفظ البطاقة → M-052 | Analyzed |
| M-051 | Payments | 35 سجل المدفوعات / Payment history | `traway-mobile/35 سجل المدفوعات.png` | Totals (paid $657, refunded $65), status chips (الكل، مدفوع، مسترد، قيد المعالجة), grouped by month, per-transaction status, export | row → booking detail (i) | Analyzed |
| M-052 | Payments | طرق الدفع والمدفوعات / Payment methods & payments | `traway-mobile/طرق الدفع والمدفوعات.png` | Default Visa card, Mastercard **** 1190, add card, recent payments list with statuses | add → M-050; view all → M-051 | Analyzed |
| M-053 | Settings | اللغه والعمله والاعدادات / Language, currency & settings | `traway-mobile/اللغه والعمله والاعدادات.png` | Language toggle (العربية/English), notifications toggle, change password, terms & privacy, delete account | terms → M-049; delete → M-056 | Analyzed |
| M-054 | Favourites | المفضله / Favourites | `traway-mobile/المفضله.png` | Tabs (فنادق، وجهات، رحلات), saved hotel cards with price/night and rating, remove-from-favourites | card → M-062 (i) | Analyzed |
| M-055 | Support | خدمه العملاء / Customer service | `traway-mobile/خدمه العملاء.png` | WhatsApp contact, phone callback, working hours (to be defined by partner) | WhatsApp/phone (external) | Analyzed |
| M-056 | Account | 38 حذف الحساب / Delete account | `traway-mobile/38 حذف الحساب.png` | Consequences list (documents deleted, saved travellers/favourites lost, bookings not cancelled), upcoming-trip warning, optional reason chips, typed «حذف» confirmation | حذف حسابي نهائياً → logout (i) | Analyzed |

### 3.9 Offers & destinations

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-057 | Offers | كل العروض / All offers | `traway-mobile/كل العروض.png` | Promo code TRAWAY10 (10 % on first booking), category chips (الكل، طيران، فنادق، رحلات، eSIM), offer cards with discount %, conditions, expiry countdown | offer card → related product (i) | Analyzed |
| M-058 | Discovery | الوجهات المميزه / Featured destinations | `traway-mobile/الوجهات المميزه.png` | Region chips (الكل، الخليج، أوروبا، آسيا، جزر وشواطئ), featured hero (Dubai, from $178), destination grid with visa requirement badges and starting prices | destination → M-019/M-059 (i) | Analyzed |

### 3.10 Hotels

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-059 | Hotels | نتائج الفنادق / Hotel results | `traway-mobile/نتائج الفنادق.png` | Search summary (Dubai, 11–4 Dec, 2 adults), filter chips (free cancellation, breakfast, 5 stars), 186 properties, sort, hotel cards with rating/reviews, amenities, price/night and total | filter → M-060; map → M-061; card → M-062; favourite toggle | Analyzed |
| M-060 | Hotels | 29 فلاتر الفنادق / Hotel filters | `traway-mobile/29 فلاتر الفنادق.png` | Price-per-night histogram + range, star rating, guest rating, amenities chips, free cancellation, pay-at-hotel, apply (62 offers) | apply → M-059 | Analyzed |
| M-061 | Hotels | 30 الفنادق على الخريطة / Hotels on map | `traway-mobile/30 الفنادق على الخريطة.png` | Map with price pins, destination selector, list/map toggle, selected-hotel bottom card with rating and price | card → M-062; list → M-059 | Analyzed |
| M-062 | Hotels | تفاصيل الفندق / Hotel details | `traway-mobile/تفاصيل الفندق.png` | Photo gallery, rating/reviews, amenity icons, room-type selector with prices, cancellation/breakfast badges, total for 7 nights, book now | احجز الآن → M-063; favourite/share | Analyzed |
| M-063 | Hotels | 22ب حجز الفندق والدفع / Hotel booking & payment | `traway-mobile/22ب حجز الفندق والدفع.png` | Hotel summary, check-in/out dates & times, lead guest, special requests chips, payment timing (pay now −5 % / pay at hotel), 7 nights + taxes, total $958 | ادفع → M-064; تعديل الضيف → M-045 (i) | Analyzed |
| M-064 | Hotels | 22ج تأكيد حجز الفندق / Hotel booking confirmed | `traway-mobile/22ج تأكيد حجز الفندق.png` | Success overlay, voucher e-mail notice, confirmation HTL-90418, dates, call hotel / voucher / directions, tour upsell, view booking | عرض الحجز → M-037; directions (external) | Analyzed |

### 3.11 Tours

| ID | Module | Screen (AR / EN) | File | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| M-065 | Tours | 23 الرحلات السياحية / Tours catalogue | `traway-mobile/23 الرحلات السياحية.png` | Search, category chips (الكل، جولات يومية، برامج متكاملة، عائلية), featured multi-day programme («المالديف 6 ليالٍ» $1,160), tour cards with duration and price | card → M-066 | Analyzed |
| M-066 | Tours | 24 تفاصيل البرنامج / Tour programme detail | `traway-mobile/24 تفاصيل البرنامج.png` | Hero, rating 4.8 (612 reviews), duration/guide/languages/group size, day programme timeline, participant stepper, total for 2, choose date | اختر التاريخ و احجز → M-067; favourite | Analyzed |
| M-067 | Tours | 24ب حجز الرحلة والدفع / Tour booking & payment | `traway-mobile/24ب حجز الرحلة والدفع.png` | Date strip with availability, departure time slots, pickup location, payment card, price for 2, pay | ادفع → M-068 | Analyzed |
| M-068 | Tours | 24ج تأكيد حجز الرحلة / Tour booking confirmed | `traway-mobile/24ج تأكيد حجز الرحلة.png` | Success, QR voucher, booking TR-33107, pickup instructions, free cancellation 24 h, contact guide, add to calendar, my bookings / home | حجوزاتي → M-037; تواصل مع المرشد (i) | Analyzed |

---

## 4. DASHBOARD INVENTORY

### 4.1 Partner onboarding & portal login — `١ — الدخول وطلب الانضمام.png` (2 states)

| ID | App | Module | Screen | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| D-01.1 | APP-PUB | Auth | Partner portal login | Account-type chooser (إدارة Traway، شركة سياحة، فندق، موظف), email, password, remember me, login, forgot password, «طلب فتح حساب» | login → role dashboard; forgot → D-01.2; request → D-02.1 | Analyzed |
| D-01.2 | APP-PUB | Auth | Forgot password | Email → send reset link, back to login | back → D-01.1 | Analyzed |

Marketing panel (not a screen) states platform scale: +120 tourism companies, +360 hotels, +48 visa countries.

### 4.2 Join requests — `Join Form.png` (3 states)

| ID | App | Module | Screen | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| D-02.1 | APP-PUB | Onboarding | Company join request | Activity type, company name/city/responsible/phone/email, 3 document uploads (tourism licence, commercial register, tax card), terms, submit | submit → D-02.3 | Analyzed |
| D-02.2 | APP-PUB | Onboarding | Hotel join request | Same form for hotel (hotel name, city, hotel establishment licence) | submit → D-02.3 | Analyzed |
| D-02.3 | APP-PUB | Onboarding | Request received | Request no. P-2291, review within two business days, approval e-mail + activation link, «دخول الشركاء» / «تقديم طلب آخر» | → D-01.1 | Analyzed |

### 4.3 Administration portal — `٢ — لوحة إدارة Traway.png`

Board header declares “14 شاشات”; **15 distinct full-page states** were observed plus 2 modals. This discrepancy is recorded in §7.

| ID | App | Module | Screen | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| D-03.1 | APP-ADM | Overview | Platform overview | KPIs: sales $96.4K, bookings 3,914, visa requests 612, eSIM sales 1,208 (all with MoM deltas); monthly sales bar chart 2026; sales-by-service donut (flights 42 %, hotels 27 %, tours 16 %, visas 10 %, eSIM 5 %); pending approvals queue; latest bookings table with statuses; export | sidebar; pending item → review; booking row → booking | Analyzed |
| D-03.2 | APP-ADM | Notifications | Notifications | All/unread filter (3 unread), grouped by time, event types: partner join request, hotel join request, incomplete documents, eSIM bundles uploaded, account deletion request, refund request; mark all read | item → target module | Analyzed |
| D-03.3 | APP-ADM | Bookings | All bookings | Tabs (الكل 8، طيران 2، فنادق 3، رحلات 1، تأشيرات 1، eSIM 1), table (ref, customer, service, partner, amount, status), export | row → detail (i); export | Analyzed |
| D-03.4 | APP-ADM | Partners | Partners | Tabs (شركات السياحة 3، الفنادق 4، طلبات الانضمام 3), add partner, table (partner, team, active tours, tours/month, monthly sales, commission, status, actions) | add → D-03.5; row → D-03.7/D-03.8; review → D-03.6 | Analyzed |
| D-03.5 | APP-ADM | Partners | Add partner | Commission stepper (default 10 %), partner type, company/hotel data, owner invite e-mail, phone, licence no., supported services (tours, UAE/Saudi/Turkey/Georgia visas), send owner invitation | save → D-03.4 | Analyzed |
| D-03.6 | APP-ADM | Partners | Join request review | Applicant data, requested services, commission contract terms (10 %), legal documents with verification states (commercial register ✔, tourism licence ✔, tax card pending), confirm document, accept first / reject | accept → D-03.4; reject → D-03.4 | Analyzed |
| D-03.7 | APP-ADM | Partners | Partner detail — tourism company | KPIs (monthly sales $16.8K, active tours 18, visa requests 142, rating 4.8), commission setting, allowed services, documents with expiry badges, team list with owner flag, suspend account | back → D-03.4 | Analyzed |
| D-03.8 | APP-ADM | Partners | Partner detail — hotel | KPIs (monthly revenue $22.4K, occupancy 82 %, room types 4, rating 4.7), commission 15 %, allowed services (rooms & rates, packages, seasonal offers, cancellation), documents, team, suspend | back → D-03.4 | Analyzed |
| D-03.9 | APP-ADM | Visas | Visa catalogue | Country list (48) with active toggles, visa types per country (tourist 30 d $599, business 3–5 y $156, residence 10–15 y $290), required-document editor with mandatory/optional toggles, add document, add country modal | save; add country | Analyzed |
| D-03.10 | APP-ADM | eSIM | eSIM catalogue | Provider markup 35 %, total margin, wallet balance $4,210, provider block (146 countries, API-linked, term field shows 12), packages per country (cost, sale, margin, availability toggle), sync from API, price comparison + CSV export | sync; export | Analyzed |
| D-03.11 | APP-ADM | Customers | Account deletion requests | Filters (all, this month), count 14, table with request state | row → customer (i) | Analyzed |
| D-03.12 | APP-ADM | Customers | Customers | KPIs (58,214 customers, 21,930 active, $246 avg booking, 14 deletion requests), searchable table (customer, city, bookings, spend, last activity, status), export | row → D-03.13 | Analyzed |
| D-03.13 | APP-ADM | Customers | Customer detail | KPIs ($657 spend, 5 bookings, 2 visa requests, 3 travellers), contact/profile block, payment method, bookings table, saved travellers, block account, send report | back → D-03.12; block | Analyzed |
| D-03.14 | APP-ADM | Finance | Finance & commissions | KPIs ($96.4K payments, $12.2K platform commission, $34.2K partner dues, $1.7K refunds), partner dues table (period, sales, commission, due, transfer status), transactions log (PAY-xxxxx, method, customer, amount, status), commission-by-service chart | export; transfer actions | Analyzed |
| D-03.15 | APP-ADM | Access control | Roles & permissions | Permission hierarchy (system admin / finance / content / support; partner owner company/hotel; employees), section × action matrix (view/add/edit/delete) for content manager, team-role list with exemption counts, create role modal | new role modal → save | Analyzed |

### 4.4 Tourism-company portal — `٣ — لوحة شركة السياحة.png` (10 states)

| ID | App | Module | Screen | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| D-04.1 | APP-COM | Overview | Company overview | KPIs (monthly sales $16.8K, bookings 186, visa requests 142, visa approval 96 %), 14-day bookings chart, “required today” queue (14 new visa requests, 5 incomplete docs, 9 new bookings, 3 tours starting), upcoming tours with occupancy, team activity feed, + new tour | sidebar; queue item → module; new tour → D-04.4 | Analyzed |
| D-04.2 | APP-COM | Notifications | Notifications | All/unread, events (new visa request, new booking, passport re-upload, tour approved, employee deactivated, settlement transferred), mark all read | item → module | Analyzed |
| D-04.3 | APP-COM | Tours | Tours & programmes | Status tabs (7 published, 4 review, 1 docs, 1 rejected, 1 expired), tour cards with occupancy/booked, edit, bookings, new tour | edit → D-04.4; bookings → D-04.5; new → D-04.4 | Analyzed |
| D-04.4 | APP-COM | Tours | Edit / create tour | Pricing (price, child price, single supplement, net after 10 % commission), schedule slots with status and seats, includes checklist, basic info (name, type, destination, duration, description), image gallery, day-by-day itinerary editor, save & submit for review / cancel | save → D-04.3 | Analyzed |
| D-04.5 | APP-COM | Bookings | Bookings | Tabs (الكل، جديدة، مؤكدة، مكتملة، ملغاة), table (ref, customer, service, date, amount, status), export | row → detail (i); export | Analyzed |
| D-04.6 | APP-COM | Visas | Visa requests | Tabs (الكل، جديد، مستندات ناقصة، جاهز للتقديم), review-progress indicator (1 of 5 completed), applicant queue with statuses, document review pane (accept/reject per document), accept all documents, next applicant | accept → next applicant | Analyzed |
| D-04.7 | APP-COM | Team | Team & permissions | Role catalogue (owner, bookings employee, visa employee, accountant, content manager), member table (role, last activity, status), invite employee, edit role | edit → D-04.8; invite → D-04.8 (i) | Analyzed |
| D-04.8 | APP-COM | Team | Custom role editor | Role name, base preset, section toggles with fine-grained actions (عرض/إضافة/تعديل/حذف/نشر/تأكيد/استرداد), preview of resulting sidebar, save role | save → D-04.7 | Analyzed |
| D-04.9 | APP-COM | Finance | Financial reports | Period filter (last 7 days/this month/this year), KPIs (sales, bookings, Traway commission, net), upcoming settlements, weekly sales chart, sales by tour table, credit card on file, export Excel | export | Analyzed |
| D-04.10 | APP-COM | Settings | Company settings | Tabs (company data, account, documents), logo upload, name, licence no., phone, email, address, customer-facing description, save | save | Analyzed |

### 4.5 Hotel portal — `٤ — لوحة الفندق.png` (7 states)

| ID | App | Module | Screen | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| D-05.1 | APP-HTL | Overview | Hotel overview | KPIs (occupancy 82 %, nightly revenue $22.4K, arrivals today 14, ADR $113), available units by room type, 14-day occupancy chart, today's arrivals table with check-in status, update prices | sidebar; update prices → D-05.3 | Analyzed |
| D-05.2 | APP-HTL | Notifications | Notifications | All/unread, events (new booking, 14 arrivals, private-branch quota near full, cancellation TW-48184, settlement transferred) | item → module | Analyzed |
| D-05.3 | APP-HTL | Rooms | Rooms & rates | Room-type cards with from-price, new room type, per-room-type pricing calendar (day price, availability, stop-sell/almost-full), bulk adjust ±$10, confirm/reset | new room → modal; confirm | Analyzed |
| D-05.4 | APP-HTL | Bookings | Bookings | Tabs (الكل 7، وصول اليوم 2، مقيمين 2، قادمة 2، ملغاة 1), table (ref, guest, room type, dates, amount, status, check-in/out actions), export | check-in/out action | Analyzed |
| D-05.5 | APP-HTL | Team | Employees | Role permission panel (reception, bookings manager, accountant toggles: view bookings, check-in/out, update prices, stop-sell, financial reports, export), employee list with shift and status, add employee | add employee | Analyzed |
| D-05.6 | APP-HTL | Profile | Hotel profile | Photo gallery, amenities chips, basic data (name, category, city, address, check-in/out times, phone, description, cancellation policy), map location editor, save & publish | save | Analyzed |
| D-05.7 | APP-HTL | Reports | Reports | Period filter, KPIs (revenue, occupancy, ADR, cancellation rate), guest-source breakdown (Egypt 58 %, Saudi/Gulf 21 %, Europe 16 %, other 5 %), weekly revenue chart, performance-by-room-type table, export Excel | export | Analyzed |

### 4.6 Restricted employee portal — `٥ — لوحة الموظف.png` (3 states)

| ID | App | Module | Screen | Main functionality | Navigation | Status |
|---|---|---|---|---|---|---|
| D-06.1 | APP-EMP | Visas | Assigned visa requests | “صلاحيات محددة” banner, request queue, document accept/reject, request additional permission from owner | sidebar | Analyzed |
| D-06.2 | APP-EMP | Bookings | Bookings (view only) | Read-only booking table, permission banner, no mutation affordances | sidebar | Analyzed |
| D-06.3 | APP-EMP | Notifications | Notifications | Assigned-request notifications, restricted-state sidebar (locked modules: team, finance, settings) | item → D-06.1/D-06.2 | Analyzed |

---

## 5. Non-screen design assets

| ID | File | Type | Content | Treatment |
|---|---|---|---|---|
| MT-01 | `traway-mobile/Title · ٢ — تسجيل الدخول وإنشاء الحساب.png` | Chapter title strip | «٢ — تسجيل الدخول وإنشاء الحساب» | Design annotation — excluded from screen count |
| MT-02 | `traway-mobile/Title · ٣ — الرئيسية والبحث والطيران.png` | Chapter title strip | «٣ — الرئيسية والبحث والطيران» | Design annotation |
| MT-03 | `traway-mobile/Title · ٣ب — بيانات المسافرين والدفع والعروض.png` | Chapter title strip | «٣ب — بيانات المسافرين والدفع والعروض» | Design annotation |
| MT-04 | `traway-mobile/Title · ٤ — التأشيرات.png` | Chapter title strip | «٤ — التأشيرات» | Design annotation |
| MT-05 | `traway-mobile/Title · ٥ — شرائح eSIM.png` | Chapter title strip | «٥ — شرائح eSIM» | Design annotation |
| MT-06 | `traway-mobile/Title · ٦ — الحجوزات وإدارة الحجز.png` | Chapter title strip | «٦ — الحجوزات وإدارة الحجز» | Design annotation |
| MT-07 | `traway-mobile/Title · ٧ — الحساب الشخصي.png` | Chapter title strip | «٧ — الحساب الشخصي» | Design annotation |
| MT-08 | `traway-mobile/Title · ٧ب — المفضلة والإعدادات والدعم.png` | Chapter title strip | «٧ب — المفضلة والإعدادات والدعم» | Design annotation |
| MT-09 | `traway-mobile/Title · ٨ — الفنادق.png` | Chapter title strip | «٨ — الفنادق» | Design annotation |
| MT-10 | `traway-mobile/Title · ٩ — الرحلات السياحية.png` | Chapter title strip | «٩ — الرحلات السياحية» | Design annotation |
| DL-01 | `traway-Dashboards/شركة سياحة · القاهرة · شريك منذ مارس 2024.png` | Component annotation | Partner card meta line | Design annotation — excluded from screen count |

---

## 6. Navigation relationships

### 6.1 Mobile navigation graph (primary edges)

```
Splash M-001
└─ Onboarding M-002 ⇄ M-003 ⇄ M-004 ──(skip)──┐
                                                ▼
Auth: M-005 (phone) ⇄ M-006 (email) ── OTP M-007 ──┐
      M-008 signup → M-009 verify → M-010 ready → M-011 complete profile
      M-012 forgot → M-013 reset
                                                ▼
Home M-014 ── tabs ── Bookings M-037 | Visas M-027 | eSIM M-033 | Account M-043
   ├─ search M-015 → destination/hotel/eSIM/visa
   ├─ flight search M-019 → dates M-017 | passengers M-018 | cities M-016
   │      → results M-021 → filters M-020
   │      → return M-022 → passengers M-023 → payment M-024 → confirmed M-025
   │            (seat fragment M-026 belongs between M-023 and M-024)
   ├─ visas M-027 → types M-028 → detail M-029 → upload M-030 → submitted M-031 → tracker M-032
   ├─ eSIM M-033 → packages M-034 → checkout M-035 → QR M-036
   ├─ bookings M-037 → details M-038 → e-ticket M-039 | reschedule M-040 | baggage M-041 | cancel M-042
   ├─ account M-043 → travellers M-044 → traveller M-045 → passport scan M-047
   │                → profile M-046 | payments M-052 → add card M-050 | history M-051
   │                → favourites M-054 | support M-055 | settings M-053 → terms M-049 | delete M-056
   ├─ notifications M-048
   ├─ offers M-057 | destinations M-058
   ├─ hotels M-059 → filters M-060 | map M-061 | detail M-062 → booking M-063 → confirmed M-064
   └─ tours M-065 → detail M-066 → booking M-067 → confirmed M-068
```

Bottom tab bar (as drawn): الرئيسية · حجوزاتي · التأشيرات · eSIM · حسابي. Note label inconsistency («جوازاتي» appears on some screens) — see §7.

### 6.2 Dashboard navigation

| Portal | Sidebar items | Detail edges |
|---|---|---|
| APP-ADM | نظرة عامة · الشركاء · كل الحجوزات · كتالوج التأشيرات · شرائح eSIM · العملاء · المالية والعمولات · الأدوار والصلاحيات | partners → add / review / company detail / hotel detail; customers → customer detail; delete requests under customers; roles → new-role modal |
| APP-COM | نظرة عامة · الرحلات والبرامج · الحجوزات · طلبات التأشيرات · الفريق والصلاحيات · التقارير المالية · إعدادات الشركة | tours → edit tour; team → role editor; notifications → module |
| APP-HTL | نظرة عامة · الغرف والأسعار · الحجوزات · الموظفين · ملف الفندق · التقارير | overview → rooms & rates / bookings; rooms → new room type modal |
| APP-EMP | طلبات التأشيرات · الحجوزات (عرض فقط) · الإشعارات (+ locked: التيم، المالية، الإعدادات) | request → document review |

---

## 7. Duplicates, variants and consistency observations

### 7.1 No exact duplicates
MD5 comparison over all 85 files returned **zero** identical pairs.

### 7.2 Same screen, different state (variants — intentionally kept as separate captures)

| Group | Captures | Difference |
|---|---|---|
| Login | M-005 / M-006 | Phone vs email tab |
| Signup path | M-008 (email+password) / M-011 (profile completion) | Two distinct entry routes |
| Calendar | M-017 (search dates) / M-040 (reschedule with fare difference) | Context and pricing content differ |
| Passenger identity | M-045 (traveller record) / M-046 (account profile) | Different entity and fields |
| Payment lists | M-051 (full history) / M-052 (methods + recent) | Different scope |
| Destructive confirmations | M-042 (cancel booking) / M-056 (delete account) | Same pattern, different consequences |
| Tour/stay/visa confirmations | M-025 / M-064 / M-068 / M-031 | Same success pattern, different domain objects |
| Empty/dense states | — | No empty-state captures supplied for any list screen |

### 7.3 Recorded inconsistencies (do not resolve by assumption)

1. **Bottom-tab label** «جوازاتي» (My passports) on some screens vs «حجوزاتي» (My bookings) on others; icon is a ticket/bookings icon. Requires one canonical label.
2. **Booking reference formats are inconsistent** across captures: `TRW7K2`, `TW-48210`, `TW-48197`, `TW-48201`, `TRW-V-2291`, `HTL-90418`, `TR-33107`, `PAY-90121`, `P-2291`. A single reference scheme per entity must be decided (Phase 3/4).
3. **Admin board header says “14 شاشات”** while 15 full-page states (plus 2 modals) are drawn. Count mismatch to be confirmed with design owner.
4. **Currency display** is `$` and «دولار $» everywhere, while settings expose a currency selector. Multi-currency is implied but only USD is shown.
5. **Visa fee** on M-029 is a literal placeholder «[حسب لوحة التحكم]» — pricing is dashboard-driven by design (confirmed), but the mobile rendering rule (per passport nationality? per country?) is not shown.
6. **Support working hours** on M-055 are placeholders «[المواعيد يحددها العميل]»; phone number placeholder «[رقم خدمة العملاء]».
7. **`Bottom Bar.png`** is misnamed: it is a seat-confirmation fragment, not the tab bar. The parent seat-map screen is absent.
8. **`Iframe.png`** is the only full home capture and shows the home feed end-to-end; no separate above-the-fold home screenshot exists.
9. **Account deletion** states existing bookings will *not* be cancelled, while cancellation is a separate paid flow — consequence rules need confirmation.
10. **Employee dashboard** is drawn inside the company portal shell with locked sidebar entries; it is a permission state, not necessarily a separate product.
11. **Cancellation figures do not reconcile with the booking total:** M-042 shows a refund of 3,605 after a 1,515 fee (implying 5,120), while the same trip's payment screen M-024 totals $511. Either a different currency (e.g. EGP) or a different booking is represented — must be clarified before pricing/refund tables are designed.

---

## 8. Coverage gaps (screens implied but not supplied)

Recorded as gaps, **not** as requirements:

| Gap | Implied by | Impact |
|---|---|---|
| Flight seat map | M-026 fragment | Seat selection feature unknown |
| Hotel search form (destination/dates/guests) | M-059 summary only | Hotel search inputs unknown |
| Tour cancellation / refund | M-068 mentions free cancellation 24 h | No UI for tour cancellation |
| Visa payment / fee checkout | M-029 fee placeholder | Whether visas are paid in-app is unconfirmed |
| Notification detail / inbox item destination | M-048, D-03.2 | Deep-link targets unknown |
| Support chat thread | M-055 only offers WhatsApp/phone | In-app messaging unconfirmed |
| Reviews / ratings submission | ratings shown on hotels & tours | Review creation UI absent |
| Multi-city flight booking | M-019 offers «متعددة» | No multi-city UI supplied |
| eSIM usage / balance screen | M-037 shows 5 GB remaining | Usage detail screen missing |
| Admin reports / audit log | export buttons across dashboards | Dedicated reporting screens absent |
| Partner document re-upload after rejection | D-03.6, D-04.3 rejected state | Partner-side remediation UI missing |
| Refund processing UI | D-03.2 refund requests | Approval workflow UI missing |
| Payout / settlement detail | D-03.14, D-04.9 | Payout execution UI missing |
| Unified multi-service provider portal | Owner-confirmed organization may operate multiple service lines; current supplied portals are separate APP-COM and APP-HTL | No complete unified provider shell, service switcher, multi-service employee scope editor or cross-service navigation is supplied; do not infer screen IDs or behavior |
| Empty, error, offline states | all lists | Non-happy-path design absent |
| English localisation of mobile screens | language toggle M-053 | Only Arabic layouts supplied |

---

## 9. Analysis status summary

| Metric | Count |
|---|---|
| Files discovered | 85 |
| Files analyzed | 85 |
| Unreadable files | 0 |
| Unsupported design formats | 0 |
| Mobile screens / states | 68 |
| Dashboard screens / states | 40 |
| **Total screens / states** | **108** |
| Design annotations | 11 |
| Files with unknown status | **0** |

Phase 1 exit criteria: **met** — every discovered image has an explicit analyzed status, every screen is mapped to an application, module and navigation context, and every gap/inconsistency is documented rather than assumed.

**Awaiting approval: `APPROVE PHASE 1`.**


## 10. Authorization mapping companion

The supplied dashboard screens have been mapped to candidate permissions and data boundaries in [`../phase-02/DASHBOARD-PERMISSION-MAPPING.md`](../phase-02/DASHBOARD-PERMISSION-MAPPING.md). That companion preserves the distinction between supplied UI facts and proposed backend authorization. It does not change the Phase 1 screen count or invent a new provider portal screen.
