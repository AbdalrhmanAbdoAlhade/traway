# Phase 1 — Screen Coverage Report

**Project:** Traway
**Phase:** 1 — UI Discovery
**Companion document:** [`UI-SCREENS-INVENTORY.md`](./UI-SCREENS-INVENTORY.md)
**Purpose:** Prove that every discovered image file was either visually analyzed or explicitly marked unreadable.

---

## 1. Reconciliation summary

| Check | Discovered | Analyzed | Unreadable / Unknown |
|---|---|---|---|
| Mobile images (`traway-mobile`) | 78 | 78 | 0 |
| Dashboard images (`traway-Dashboards`) | 7 | 7 | 0 |
| **Total images** | **85** | **85** | **0** |
| Mobile screen / overlay states | — | 68 | — |
| Dashboard screen / overlay states | — | 40 | — |
| **Total screen / overlay states** | — | **108** | — |
| Non-screen design assets | 11 | 11 | 0 |

Reconciliation identity:

```
78 mobile files  = 68 screen files      + 10 chapter-title strips
 7 dashboard files =  6 screen boards    +  1 annotation label
85 total files    = 85 analyzed files    +  0 unreadable files
108 screen states = 68 mobile states     + 40 dashboard states
```

No file has an unknown status. No file was skipped. No file was judged from its filename alone.

### Reading evidence

* All 85 files were opened with the vision reader.
* The four boards that cannot be read when downscaled were sliced at 1:1 into overlapping 1,500 × H crops and re-read:

| Board | Source width | Crops inspected |
|---|---|---|
| `٢ — لوحة إدارة Traway.png` | 18,006 px | 14 |
| `٣ — لوحة شركة السياحة.png` | 12,870 px | 10 |
| `٤ — لوحة الفندق.png` | 9,018 px | 7 |
| `٥ — لوحة الموظف.png` | 3,882 px | 3 |
| **Total crops** | | **34** |

---

## 2. Mobile file coverage (78 / 78)

| # | File | Dimensions | Assigned ID | Content | Result |
|---:|---|---|---|---|---|
| 1 | `traway-mobile/Splash.png` | 353×693 | M-001 | Splash | Analyzed — screen |
| 2 | `traway-mobile/on boarding - الحجز.png` | 314×654 | M-002 | Onboarding 1/3 | Analyzed — screen |
| 3 | `traway-mobile/Onboarding · التأشيرات.png` | 312×653 | M-003 | Onboarding 2/3 | Analyzed — screen |
| 4 | `traway-mobile/04 Onboarding · eSIM.png` | 293×633 | M-004 | Onboarding 3/3 | Analyzed — screen |
| 5 | `traway-mobile/05 تسجيل الدخول.png` | 297×638 | M-005 | Login — phone | Analyzed — screen |
| 6 | `traway-mobile/05ب دخول بالبريد.png` | 297×638 | M-006 | Login — email | Analyzed — screen |
| 7 | `traway-mobile/05ج إنشاء حساب بالبريد.png` | 297×638 | M-008 | Signup | Analyzed — screen |
| 8 | `traway-mobile/05د تأكيد البريد.png` | 297×638 | M-009 | Verify email | Analyzed — screen |
| 9 | `traway-mobile/05هـ الحساب جاهز.png` | 297×638 | M-010 | Account ready | Analyzed — screen |
| 10 | `traway-mobile/05و نسيت كلمة المرور.png` | 297×638 | M-012 | Forgot password | Analyzed — screen |
| 11 | `traway-mobile/05ز كلمة مرور جديدة.png` | 297×638 | M-013 | Reset password | Analyzed — screen |
| 12 | `traway-mobile/06 رمز التحقق OTP.png` | 297×638 | M-007 | SMS OTP | Analyzed — screen |
| 13 | `traway-mobile/07 إكمال الحساب.png` | 297×638 | M-011 | Complete profile | Analyzed — screen |
| 14 | `traway-mobile/Iframe.png` | 390×2751 | M-014 | Home — full page | Analyzed — screen (long capture) |
| 15 | `traway-mobile/31 البحث.png` | 293×633 | M-015 | Global search | Analyzed — screen |
| 16 | `traway-mobile/25 اختيار المدينة.png` | 293×658 | M-016 | City picker | Analyzed — screen |
| 17 | `traway-mobile/26 التقويم.png` | 293×633 | M-017 | Date picker | Analyzed — screen |
| 18 | `traway-mobile/27 المسافرين والدرجة.png` | 293×633 | M-018 | Passengers & class | Analyzed — screen |
| 19 | `traway-mobile/09 بحث الطيران.png` | 293×705 | M-019 | Flight search | Analyzed — screen |
| 20 | `traway-mobile/28 فلاتر الطيران.png` | 293×654 | M-020 | Flight filters | Analyzed — screen |
| 21 | `traway-mobile/10 نتائج الرحلات.png` | 293×633 | M-021 | Flight results | Analyzed — screen |
| 22 | `traway-mobile/10أ اختيار رحلة العودة.png` | 293×633 | M-022 | Return flight | Analyzed — screen |
| 23 | `traway-mobile/10ب بيانات المسافرين.png` | 293×633 | M-023 | Passenger details | Analyzed — screen |
| 24 | `traway-mobile/10ج الدفع.png` | 297×648 | M-024 | Flight payment | Analyzed — screen |
| 25 | `traway-mobile/10د تأكيد الحجز.png` | 297×638 | M-025 | Flight confirmed | Analyzed — screen |
| 26 | `traway-mobile/Bottom Bar.png` | 293×90 | M-026 | Seat-confirm fragment | Analyzed — component fragment |
| 27 | `traway-mobile/11 التأشيرات · الدول.png` | 293×633 | M-027 | Visa countries | Analyzed — screen |
| 28 | `traway-mobile/12 التأشيرات · الأنواع.png` | 293×633 | M-028 | Visa types | Analyzed — screen |
| 29 | `traway-mobile/13 تفاصيل التأشيرة.png` | 293×633 | M-029 | Visa detail | Analyzed — screen |
| 30 | `traway-mobile/14 رفع المستندات.png` | 297×638 | M-030 | Document upload | Analyzed — screen |
| 31 | `traway-mobile/14ب تم إرسال طلب التأشيرة.png` | 297×638 | M-031 | Visa submitted | Analyzed — screen |
| 32 | `traway-mobile/مستندات التاشيرات.png` | 293×633 | M-032 | Visa document tracker | Analyzed — screen |
| 33 | `traway-mobile/15 eSIM · الدول.png` | 297×638 | M-033 | eSIM countries | Analyzed — screen |
| 34 | `traway-mobile/16 eSIM · الباقات.png` | 297×559 | M-034 | eSIM packages | Analyzed — screen |
| 35 | `traway-mobile/17 eSIM · تفاصيل وشراء.png` | 297×638 | M-035 | eSIM checkout | Analyzed — screen |
| 36 | `traway-mobile/18 eSIM · التفعيل QR.png` | 297×638 | M-036 | eSIM QR activation | Analyzed — screen |
| 37 | `traway-mobile/19 حجوزاتي.png` | 297×638 | M-037 | My bookings | Analyzed — screen |
| 38 | `traway-mobile/تفاصيل الحجوازات.png` | 293×633 | M-038 | Booking details | Analyzed — screen |
| 39 | `traway-mobile/19ج التذكرة الإلكترونية.png` | 293×633 | M-039 | E-ticket | Analyzed — screen |
| 40 | `traway-mobile/19د تغيير الموعد.png` | 293×633 | M-040 | Reschedule | Analyzed — screen |
| 41 | `traway-mobile/19هـ إضافة حقيبة.png` | 293×633 | M-041 | Add baggage | Analyzed — screen |
| 42 | `traway-mobile/19و إلغاء الحجز.png` | 293×633 | M-042 | Cancel booking | Analyzed — screen |
| 43 | `traway-mobile/20 حسابي.png` | 293×633 | M-043 | My account | Analyzed — screen |
| 44 | `traway-mobile/المسافرون المحفوظون.png` | 293×633 | M-044 | Saved travellers | Analyzed — screen |
| 45 | `traway-mobile/20ج بيانات المسافر.png` | 293×633 | M-045 | Traveller data | Analyzed — screen |
| 46 | `traway-mobile/تعديل البيانات.png` | 293×633 | M-046 | Edit profile | Analyzed — screen |
| 47 | `traway-mobile/37 مسح الجواز.png` | 293×633 | M-047 | Passport OCR | Analyzed — screen |
| 48 | `traway-mobile/32 الإشعارات.png` | 293×633 | M-048 | Notifications | Analyzed — screen |
| 49 | `traway-mobile/33 الشروط والخصوصية.png` | 293×633 | M-049 | Terms & privacy | Analyzed — screen |
| 50 | `traway-mobile/34 إضافة بطاقة.png` | 293×633 | M-050 | Add card | Analyzed — screen |
| 51 | `traway-mobile/35 سجل المدفوعات.png` | 293×633 | M-051 | Payment history | Analyzed — screen |
| 52 | `traway-mobile/طرق الدفع والمدفوعات.png` | 293×633 | M-052 | Payment methods | Analyzed — screen |
| 53 | `traway-mobile/اللغه والعمله والاعدادات.png` | 293×633 | M-053 | Language & settings | Analyzed — screen |
| 54 | `traway-mobile/المفضله.png` | 293×633 | M-054 | Favourites | Analyzed — screen |
| 55 | `traway-mobile/خدمه العملاء.png` | 293×633 | M-055 | Customer service | Analyzed — screen |
| 56 | `traway-mobile/38 حذف الحساب.png` | 293×633 | M-056 | Delete account | Analyzed — screen |
| 57 | `traway-mobile/كل العروض.png` | 293×633 | M-057 | All offers | Analyzed — screen |
| 58 | `traway-mobile/الوجهات المميزه.png` | 293×764 | M-058 | Featured destinations | Analyzed — screen |
| 59 | `traway-mobile/نتائج الفنادق.png` | 297×638 | M-059 | Hotel results | Analyzed — screen |
| 60 | `traway-mobile/29 فلاتر الفنادق.png` | 293×633 | M-060 | Hotel filters | Analyzed — screen |
| 61 | `traway-mobile/30 الفنادق على الخريطة.png` | 293×633 | M-061 | Hotels on map | Analyzed — screen |
| 62 | `traway-mobile/تفاصيل الفندق.png` | 293×633 | M-062 | Hotel details | Analyzed — screen |
| 63 | `traway-mobile/22ب حجز الفندق والدفع.png` | 297×638 | M-063 | Hotel booking & payment | Analyzed — screen |
| 64 | `traway-mobile/22ج تأكيد حجز الفندق.png` | 297×638 | M-064 | Hotel confirmed | Analyzed — screen |
| 65 | `traway-mobile/23 الرحلات السياحية.png` | 297×638 | M-065 | Tours catalogue | Analyzed — screen |
| 66 | `traway-mobile/24 تفاصيل البرنامج.png` | 297×638 | M-066 | Tour details | Analyzed — screen |
| 67 | `traway-mobile/24ب حجز الرحلة والدفع.png` | 297×638 | M-067 | Tour booking & payment | Analyzed — screen |
| 68 | `traway-mobile/24ج تأكيد حجز الرحلة.png` | 297×638 | M-068 | Tour confirmed | Analyzed — screen |
| 69 | `traway-mobile/Title · ٢ — تسجيل الدخول وإنشاء الحساب.png` | 462×32 | MT-01 | Chapter title | Analyzed — non-screen asset |
| 70 | `traway-mobile/Title · ٣ — الرئيسية والبحث والطيران.png` | 394×33 | MT-02 | Chapter title | Analyzed — non-screen asset |
| 71 | `traway-mobile/Title · ٣ب — بيانات المسافرين والدفع والعروض.png` | 535×37 | MT-03 | Chapter title | Analyzed — non-screen asset |
| 72 | `traway-mobile/Title · ٤ — التأشيرات.png` | 189×37 | MT-04 | Chapter title | Analyzed — non-screen asset |
| 73 | `traway-mobile/Title · ٥ — شرائح eSIM.png` | 220×37 | MT-05 | Chapter title | Analyzed — non-screen asset |
| 74 | `traway-mobile/Title · ٦ — الحجوزات وإدارة الحجز.png` | 339×33 | MT-06 | Chapter title | Analyzed — non-screen asset |
| 75 | `traway-mobile/Title · ٧ — الحساب الشخصي.png` | 298×37 | MT-07 | Chapter title | Analyzed — non-screen asset |
| 76 | `traway-mobile/Title · ٧ب — المفضلة والإعدادات والدعم.png` | 440×37 | MT-08 | Chapter title | Analyzed — non-screen asset |
| 77 | `traway-mobile/Title · ٨ — الفنادق.png` | 157×28 | MT-09 | Chapter title | Analyzed — non-screen asset |
| 78 | `traway-mobile/Title · ٩ — الرحلات السياحية.png` | 282×33 | MT-10 | Chapter title | Analyzed — non-screen asset |

**Mobile subtotal: 78 files = 68 screen files + 10 non-screen assets, 0 unreadable.**

---

## 3. Dashboard file coverage (7 / 7)

| # | File | Dimensions | Assigned ID(s) | Screens inside | Result |
|---:|---|---|---|---|---|
| 1 | `traway-Dashboards/١ — الدخول وطلب الانضمام.png` | 2,598×1,224 | D-01.1 portal login; D-01.2 forgot password | 2 | Analyzed — board (read whole) |
| 2 | `traway-Dashboards/Join Form.png` | 1,802×928 | D-02.1 company join; D-02.2 hotel join; D-02.3 request received | 3 | Analyzed — board (read whole) |
| 3 | `traway-Dashboards/٢ — لوحة إدارة Traway.png` | 18,006×1,423 | D-03.1 overview; D-03.2 notifications; D-03.3 all bookings; D-03.4 partners; D-03.5 add partner; D-03.6 join review; D-03.7 company partner detail; D-03.8 hotel partner detail; D-03.9 visa catalogue; D-03.10 eSIM catalogue; D-03.11 deletion requests; D-03.12 customers; D-03.13 customer detail; D-03.14 finance & commissions; D-03.15 roles & permissions (+ add-country modal, new-role modal) | 15 (+2 modals) | Analyzed — board (14 crops) |
| 4 | `traway-Dashboards/٣ — لوحة شركة السياحة.png` | 12,870×1,260 | D-04.1 overview; D-04.2 notifications; D-04.3 tours; D-04.4 edit tour; D-04.5 bookings; D-04.6 visa requests; D-04.7 team & permissions; D-04.8 custom role; D-04.9 financial reports; D-04.10 company settings | 10 | Analyzed — board (10 crops) |
| 5 | `traway-Dashboards/٤ — لوحة الفندق.png` | 9,018×1,182 | D-05.1 overview; D-05.2 notifications; D-05.3 rooms & rates; D-05.4 bookings; D-05.5 employees; D-05.6 hotel profile; D-05.7 reports | 7 | Analyzed — board (7 crops) |
| 6 | `traway-Dashboards/٥ — لوحة الموظف.png` | 3,882×1,103 | D-06.1 visa requests; D-06.2 bookings (view only); D-06.3 notifications | 3 | Analyzed — board (3 crops) |
| 7 | `traway-Dashboards/شركة سياحة · القاهرة · شريك منذ مارس 2024.png` | 181×11 | DL-01 | 0 (label) | Analyzed — non-screen asset |

**Dashboard subtotal: 7 files = 6 screen boards + 1 non-screen asset, 0 unreadable. 40 screen states extracted.**

---

## 4. Screen-count arithmetic per board

| Board | Claimed by design header | States observed | Note |
|---|---|---|---|
| Admin | 14 | 15 full pages + 2 modals | Header under-counts by 1; recorded as inconsistency I-3 |
| Company | 10 | 10 | Matches |
| Hotel | 7 | 7 | Matches |
| Employee | 3 | 3 | Matches |
| Portal login | — | 2 | — |
| Join flow | — | 3 | — |
| **Dashboard total** | — | **40** | — |
| Mobile | — | 68 | Includes 1 fragment and 1 long capture |
| **Grand total** | — | **108** | — |

---

## 5. Files explicitly excluded from screen counting

These files exist, were opened, and were classed as design assets rather than application screens:

| File | Reason |
|---|---|
| 10 × `Title · …` strips | Chapter dividers with no interactive content |
| `شركة سياحة · القاهرة · شريك منذ مارس 2024.png` | Isolated meta-label component (11 px high) |
| `Bottom Bar.png` | Fragment of a seat-selection bar; parent screen absent (counted as 1 partial state) |

They remain in the coverage table with an explicit status, so nothing is silently dropped.

---

## 6. Unreadable / unsupported files

| File | Problem | Action |
|---|---|---|
| — | None | — |

All files are valid PNG images and were decoded successfully. No `.fig`, `.sketch`, `.xd`, `.pdf`, `.svg`, `.zip` or other design source was present in either directory.

---

## 7. Validation checklist (Phase 1)

| # | Criterion | Result |
|---|---|---|
| 1 | Both source directories inspected recursively | ✅ Pass — 2 directories, 0 sub-directories, 85 files |
| 2 | Every image file visually inspected | ✅ Pass — 85 / 85 |
| 3 | Every file has an explicit status | ✅ Pass — 0 unknown |
| 4 | Screens grouped by app, portal, module, workflow | ✅ Pass — 5 apps, 11 mobile modules, 4 portal modules |
| 5 | Duplicate screenshots identified | ✅ Pass — 0 byte-identical; 7 variant groups documented |
| 6 | Unreadable files recorded | ✅ Pass — none exist |
| 7 | Navigation relationships recorded | ✅ Pass — mobile graph + 4 portal sidebars |
| 8 | Screen count reconciled against file count | ✅ Pass — 78 = 68 + 10; 7 = 6 + 1; 85 = 85 |
| 9 | No requirement invented from filenames | ✅ Pass — ambiguities listed as gaps/inconsistencies |
| 10 | Inventory + coverage documents saved | ✅ Pass — `docs/phase-01/` |
| 11 | No application code, migrations or tables generated | ✅ Pass — documentation only |

**Phase 1 coverage result: COMPLETE.**
**Blocking issues: none. Outstanding items: 15 coverage gaps and 11 design inconsistencies carried into Phase 2 as questions, not assumptions.**

Awaiting explicit approval: **`APPROVE PHASE 1`**.
