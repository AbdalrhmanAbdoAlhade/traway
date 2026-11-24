# خطة بناء Traway — النسخة 2 (مبنية على تصميم Figma الفعلي)

> النسخة دي بتحل محل `TRAWAY_BUILD_PLAN.md`. اتبنت بعد قراءة حوالي 20 شاشة من Figma (ملف `traway`، 37 شاشة + Landing).
> الترتيب **خطي صارم**: كل الـ Migrations، بعدين كل الـ Models، بعدين Seeders، بعدين Services، بعدين Policies/Requests، وأخيرًا Controllers.

---

## 0. اللي اتقرا واللي لسه

**اتقرا من Figma (واضح):**
D01 دخول، D08 إضافة شريك، D09 مراجعة طلب انضمام، D10 ملف شركة، D12 كتالوج التأشيرات، D13 eSIM، D15 تفاصيل العميل، D16 المالية، D17 الأدوار والصلاحيات (إدارة)، D21 محرر الرحلة، D22 حجوزات الشركة، D23 طلبات التأشيرات، D24 الفريق، D25 دور مخصص، D27 إعدادات الشركة، D30 الغرف والأسعار، D31 حجوزات الفندق، D32 موظفو الفندق، D33 ملف الفندق، وهيكل الملف كله.

**لسه ما اتقراش (تخمين من العناوين، يتراجع لما نكمل القراءة):**
D02 نسيت كلمة المرور، D04/D18/D28 نظرة عامة، D05/D19/D29/D37 الإشعارات، D06 كل الحجوزات (إدارة)، D07 الشركاء، D11 ملف فندق، D14 العملاء (قائمة)، D20 قائمة الرحلات، D26 التقارير المالية للشركة، D34 تقارير الفندق، D35 و D36 لوحة الموظف، وصفحة الـ Landing ونموذج الانضمام.

---

## 1. اللي اتغير عن النسخة الأولى (ملخص)

| التغيير | السبب في التصميم |
|---|---|
| حذف `rooms` و`rate_plans` و`room_rates` و`room_availability` وإضافة `room_type_calendar` | الفندق بيحدد **سعر + عدد متاح + قفل بيع لكل يوم لكل نوع غرفة** مباشرة من تقويم، ومفيش خطط أسعار |
| حذف `commission_rules` | العمولة رقم واحد لكل شريك (+/-) والافتراضي من `platform_settings` |
| eSIM والتأشيرات بقوا **ملك المنصة** (مفيش organization_id على eSIM) | الإدارة هي اللي تدير الكتالوج وهامش الربح، والتأشيرات بتتسند لشركة سياحة حسب الدولة |
| إضافة كتالوج التأشيرات (دول، أنواع، مستندات مطلوبة) | شاشة D12 |
| إضافة `settlements` (تسويات كل 15 يوم) | شاشة D16 |
| إضافة تحقق المستندات + تاريخ انتهاء المستند | D09 و D10 (الترخيص ينتهي بعد 40 يوم) |
| حذف `tasks` | لوحة الموظف في التصميم هي طلبات التأشيرات والحجوزات والإشعارات، مش مهام |
| إضافة شيفتات الموظفين، مرافق الفندق، `saved_travelers`، `tour_inclusions` | D32 و D33 و D15 و D21 |

---

## 2. القرارات الثابتة (من الـ docs)

- Laravel 13 / PHP 8.4 / Sanctum / Spatie Permission (Teams، `organization_id` هو team id).
- أنواع المنظمات: `platform`, `company`, `hotel`, `service_provider`.
- USD فقط. مفيش Audit Logs، بس `status_histories` لتغييرات الحالة.
- Owner Templates ثابتة: `company_owner`, `hotel_owner`, `service_provider_owner`، والمالك ما يعدلش صلاحياتها الأساسية.
- فندق واحد (Organization) ممكن يملك أكتر من Property، وصلاحيات الموظف على مستوى الـ Property.
- لا أحد يمنح صلاحية أعلى من صلاحياته (التصميم بيقولها صراحة في D17).
- العمولات الافتراضية (شركة 10% / فندق 15%) بتتحط بـ Seeder.

---

## 3. تعارضات محتاجة قرارك قبل التنفيذ

1. **التحويلات المالية:** الـ docs بتقول *Payout عند الطلب من الـ ledger*، لكن Figma (D16 + صفحة الشركاء) بيقول **دورة تحويل تلقائية كل 15 يوم** وزرار "تحويل" عند الإدارة. الخطة دي ماشية على التصميم (`settlements`)، وبتفضل `payouts` اختيارية. قرر أنهي.
2. **صيغة رقم الحجز:** الـ docs `TRW-FLT/HOT/TOU/ESM/VIS-XXXXXXXX`، والتصميم فيه `TW-48197` و`HTL-90418` و`TRW7K2` و`TR-33107` (غير موحد حتى جوه Figma). الخطة بتستخدم صيغة الـ docs، وده يحتاج تحديث نصوص التصميم.
3. **وسائل الدفع:** التصميم فيه Visa/Mastercard وApple Pay و"فوري". فوري بالجنيه المصري، والقرار الثابت USD فقط. محتاج تحديد هل فوري هيتشال أو تتحول العملة.
4. **الكوبونات:** القرارات موجودة في الـ docs لكن لسه ما شفتش لها شاشة. اتحطت في الـ migrations كـ "مؤجل" لحد ما نشوف شاشة لها.
5. **الطيران:** مفيش شاشات طيران في اللوحات، بس عمولة طيران في رسم D16 وحجز طيران في ملف العميل. اتعامل معاه كخدمة من التطبيق فقط (بدون لوحة).

---

## المرحلة 1 — الإعداد

1. تثبيت `sanctum`, `spatie/laravel-permission` (teams = true, `team_foreign_key = organization_id`), `spatie/laravel-medialibrary`.
2. مجلدات: `app/Enums`, `Services`, `Policies`, `Http/Requests`, `Http/Resources`, `Support`.
3. Middleware `SetOrganizationContext` (يحدد المنظمة من الـ membership ويعمل `setPermissionsTeamId`).
4. Middleware `EnsureAccountType:{platform|company|hotel|staff}`: الدخول في D01 بيختار نوع الحساب، والباك إند لازم يتحقق إن المستخدم عنده membership في منظمة من النوع ده.
5. `ApiResponse` trait موحد، واللغة الافتراضية `ar` مع دعم `en`.

---

## المرحلة 2 — كل الـ Migrations (بالترتيب)

### 2.1 الأساس
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 1 | `countries` | id, code (ISO2), name_ar, name_en, is_active |
| 2 | `organizations` | type, name, slug, status (pending/active/suspended/rejected), city, country_id, address, phone, email, license_no, established_year, logo, cover, bio, commission_rate (nullable = الافتراضي), approved_at, suspended_at |
| 3 | `users` | name, email unique, phone, password, locale, status, last_active_at, حقول حذف الحساب |
| 4 | جداول Spatie (مع `organization_id`) + على `roles`: `description`, `is_template`, `is_locked` |
| 5 | `memberships` | user_id, organization_id, kind (owner/staff), job_title, status (invited/active/suspended), shift_label, shift_starts_at, shift_ends_at, invited_by, last_active_at |
| 6 | `membership_permissions` | membership_id, permission_id, granted_by_user_id |
| 7 | `membership_properties` | membership_id, hotel_property_id |
| 8 | `invitations` | organization_id, email, role_id, token, expires_at, accepted_at |
| 9 | `password_reset_tokens`, `personal_access_tokens` | افتراضي |

### 2.2 الانضمام والتعاقد
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 10 | `join_requests` | type (company/hotel), company_name, owner_name, email, phone, city, license_no, established_year, requested_services json, status (pending/approved/rejected/needs_info), reviewed_by, reviewed_at, rejection_reason, organization_id |
| 11 | `organization_documents` | organization_id, join_request_id, type (commercial_register/tax_card/tourism_license/hotel_license), file_path, mime, size_kb, status (pending/verified/cancelled), verified_by, verified_at, expires_at |
| 12 | `organization_services` | organization_id, service (tours/visa_processing/hotel_packages) |
| 13 | `organization_visa_countries` | organization_id, country_id (الدول المسموحة بالعقد) |

### 2.3 كتالوج التأشيرات (ملك المنصة)
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 14 | `visa_country_configs` | country_id unique, responsible_organization_id nullable (شركة المعالجة), status (active/unassigned) |
| 15 | `visa_types` | country_id, name, price, processing_days_min/max, stay_days, entries, is_active, sort |
| 16 | `visa_type_documents` | visa_type_id, name, hint, accepted_formats (PDF/JPG), is_required, sort |

### 2.4 الفنادق
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 17 | `hotel_properties` | organization_id, name, stars, city, address, lat, lng, description, check_in_time, check_out_time, reception_phone, cancellation_policy (text), status |
| 18 | `amenities` + `hotel_property_amenity` | key, name_ar (واي فاي، حمام سباحة، سبا، جيم، نادي أطفال، مطعم، خدمة غرف 24 ساعة، نقل من المطار، إفطار، مواقف سيارات...) |
| 19 | `room_types` | hotel_property_id, name, max_adults, max_children, rooms_count, base_price, description, status |
| 20 | `room_type_calendar` | room_type_id, date, price, available, is_closed, price_tier (normal/high)، **unique(room_type_id, date)** |
| 21 | `media` | Medialibrary (صور الفندق والرحلات، أول صورة = الغلاف) |

### 2.5 الرحلات
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 22 | `tours` | organization_id, title, type, destination_country_id, destination_city, nights, days, description, price_adult, price_child (2-11), single_supplement, status (draft/pending_review/published/rejected/paused), submitted_at, published_at, rejection_reason |
| 23 | `tour_inclusions` | tour_id, label, is_included, sort |
| 24 | `tour_itinerary_days` | tour_id, day_number, title, description |
| 25 | `tour_departures` | tour_id, starts_on, ends_on, capacity, booked, status (available/almost_full/full/closed) |

### 2.6 eSIM (ملك المنصة)
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 26 | `esim_providers` | name, credentials (encrypted), status, balance, countries_count, last_synced_at |
| 27 | `esim_packages` | provider_id, region, country_id nullable, data_gb (null = غير محدود), validity_days, provider_cost, margin_override nullable, price_override nullable, sell_price, is_visible |

### 2.7 العملاء
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 28 | `customer_profiles` | user_id, city, preferred_language |
| 29 | `saved_travelers` | user_id, full_name, type (adult/child), birth_date, passport_no (encrypted), passport_expiry |
| 30 | `payment_methods` | user_id, gateway_token, brand, last4 (الرقم الكامل ما بيتخزنش أبدًا) |

### 2.8 الحجوزات
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 31 | `bookings` | reference unique, customer_id, organization_id nullable (eSIM/طيران = المنصة), type (flight/hotel/tour/visa/esim), status (new/confirmed/checked_in/completed/cancelled/refunded), subtotal, discount, total, commission_amount, coupon_id nullable, price_approved_at |
| 32 | `booking_items` | booking_id, bookable_type, bookable_id, quantity, unit_price, total |
| 33 | `booking_travelers` | booking_id, full_name, type, birth_date, passport_no, passport_expiry |
| 34 | `booking_hotel_stays` | booking_id, hotel_property_id, room_type_id, check_in, check_out, adults, children, checked_in_at, checked_out_at |
| 35 | `booking_tour_details` | booking_id, tour_departure_id, adults, children, single_rooms |
| 36 | `visa_applications` | booking_id, visa_type_id, organization_id (المسؤولة)، status (new/docs_incomplete/ready_to_submit/submitted/approved/rejected), assigned_at |
| 37 | `visa_application_documents` | application_id, visa_type_document_id, file_path, status (not_uploaded/pending_review/accepted/rejected), rejection_reason, reviewed_by, reviewed_at |
| 38 | `esim_orders` | booking_id, package_id, iccid, qr_payload, status (ready_to_install/active/expired) |
| 39 | `flight_offers_cache` | search_hash, payload json, expires_at |

### 2.9 المال
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 40 | `payments` | reference (PAY-xxxxx), booking_id, method (card/apple_pay/…), brand, last4, amount, status (success/refunded/failed), gateway_ref |
| 41 | `refunds` | booking_id, payment_id, amount, reason, status |
| 42 | `ledger_entries` | organization_id nullable, booking_id, type (sale/commission/refund/settlement/adjustment), amount, balance_after |
| 43 | `settlements` | organization_id, period_start, period_end, gross_sales, commission, refunds, net, status (ready/transferred/on_hold), hold_reason, transferred_at, transferred_by, transfer_reference |
| 44 | `bank_accounts` | organization_id, bank_name, account_holder, iban (encrypted), status, verified_by |
| 45 | `coupons` + `coupon_redemptions` | **مؤجل لحد ما نشوف شاشة** (القرارات في الـ docs) |

### 2.10 التشغيل
| # | الجدول | الأعمدة المهمة |
|---|---|---|
| 46 | `status_histories` | subject_type/id, from_status, to_status, changed_by, reason |
| 47 | `notifications` | افتراضي Laravel |
| 48 | `reviews` | booking_id, customer_id, reviewable_type/id, rating, comment |
| 49 | `account_deletion_requests` | user_id, scheduled_for, status, blocked_reason |
| 50 | `platform_settings` | key, value json (esim_default_margin=35, commission_company=10, commission_hotel=15, settlement_cycle_days=15, max_staff_per_org=15) |

**نقاط مراجعة:** `migrate:fresh` ينجح، وفحص الـ unique على `room_type_calendar` وعلى `visa_country_configs.country_id`.

---

## المرحلة 3 — الـ Models والعلاقات

أهم العلاقات الجديدة:

- `Organization`: hasMany memberships, documents, services, hotelProperties, tours, settlements, bankAccounts؛ belongsToMany visaCountries.
- `OrganizationDocument`: scope `expiringWithin($days)` (لتنبيه "ينتهي بعد 40 يوم") و`isExpired()` (يوقف التسوية).
- `VisaCountryConfig`: belongsTo country, responsibleOrganization؛ hasMany visaTypes عن طريق country.
- `VisaType`: hasMany requiredDocuments.
- `HotelProperty`: hasMany roomTypes؛ belongsToMany amenities؛ media.
- `RoomType`: hasMany calendar؛ دوال `minPrice()` و`availableOn($date)`.
- `Tour`: hasMany inclusions, itineraryDays, departures؛ دالة `netForPartner()` (السعر - العمولة).
- `EsimPackage`: accessor `effectiveMargin`، وحساب `sell_price = provider_cost × (1 + margin)`.
- `Booking`: hasOne hotelStay, tourDetails, visaApplication, esimOrder؛ hasMany items, travelers, payments؛ morphMany statusHistories.
- `VisaApplication`: hasMany documents؛ accessor `reviewProgress` (مثال 1/5).
- `Settlement`: belongsTo organization؛ scope `readyToTransfer()`.
- `Membership`: belongsTo user, organization؛ belongsToMany properties.
- `Enums`: OrganizationType, OrganizationStatus, DocumentType, DocumentStatus, VisaApplicationStatus, VisaDocumentStatus, TourStatus, DepartureStatus, BookingType, BookingStatus, PaymentStatus, SettlementStatus.
- Trait `HasStatusHistory`.

---

## المرحلة 4 — Permission Catalog والـ Seeders

### 4.1 الكتالوج (صيغة `scope.module.action`)

**الإدارة** (مصفوفة D17: أقسام × [عرض، إضافة، تعديل، حذف، موافقة/تصدير]):
`platform.partners.*`, `platform.content.*` (الرحلات والفنادق)، `platform.bookings.*`, `platform.visa_catalog.*`, `platform.finance.*`, `platform.customers.*`, `platform.roles.*`

**شركة السياحة** (D25):
- `company.trips`: view, create, update, publish, delete
- `company.bookings`: view, confirm, cancel, refund
- `company.visa_requests`: view, review
- `company.team`: manage
- `company.finance`: view
- `company.settings`: manage
- نظرة عامة دايمًا مفعلة

**الفندق** (D32):
`hotel.bookings.view`, `hotel.checkin`, `hotel.arrivals.view`, `hotel.rates.update`, `hotel.sales.toggle`, `hotel.finance.view`, `hotel.reports.export`

### 4.2 الـ Seeders
1. `PermissionCatalogSeeder`
2. `OwnerTemplatesSeeder` (company_owner، hotel_owner، service_provider_owner، `is_locked = true`)
3. `PlatformRolesSeeder`: مدير النظام، مشرف المحتوى، المسؤول المالي، خدمة العملاء
4. `StaffTemplatesSeeder`: موظف حجوزات، موظف تأشيرات، محاسب (قوالب "ابدأ من قالب" في D25)، واستقبال/مدير حجوزات للفندق
5. `PlatformSettingsSeeder`: عمولة 10/15، هامش eSIM 35، دورة 15 يوم، حد الموظفين 15
6. `PlatformOrganizationSeeder` + أول Super Admin
7. `CountriesSeeder` (48 دولة للتأشيرات + 146 لـ eSIM)
8. `AmenitiesSeeder`

### 4.3 قاعدة non-delegable
في `NonDelegablePermissions`: كل `platform.*` + `company.team.manage` للمالك فقط عند أول تعيين + تعديل Owner Templates + العمولات + الحسابات البنكية + اعتماد التحويلات.

---

## المرحلة 5 — الـ Services

| Service | المسؤولية (حسب التصميم) |
|---|---|
| `AuthService` | دخول بنوع الحساب، تذكرني، نسيت كلمة المرور، تبديل المنظمة |
| `InvitationService` | دعوة المالك (من D08) ودعوة الموظف (من D24)، قبول الدعوة وتعيين كلمة المرور |
| `JoinRequestService` | submit من الـ Landing، تحقق المستندات، approve (يرفض لو في مستند غير موثق)، reject بسبب |
| `PartnerService` | إضافة شريك يدويًا (D08)، تعديل العمولة، تعليق مؤقت، الخدمات والدول المسموحة |
| `DocumentService` | رفع، تحقق/إلغاء، تنبيه الانتهاء قبل 40 يوم، تعليق التسوية عند الانتهاء |
| `RoleService` | أدوار مخصصة (D25)، حفظ مصفوفة الصلاحيات، معاينة "سيرى الموظف"، منع الصلاحيات المقفلة |
| `AuthorizationService` | مصدر القرار الوحيد (permissions + property scope + non-delegable + منع منح أعلى من صلاحياتك) |
| `MembershipService` | موظفين، إيقاف/تفعيل، الشيفتات، حد 15 موظف |
| `VisaCatalogService` | دول، أنواع، مستندات مطلوبة (ترتيب/إلزامي)، إسناد شركة معالجة لكل دولة |
| `VisaApplicationService` | استلام الطلب، مراجعة كل مستند (قبول/رفض بسبب)، إشعار العميل لإعادة رفع الملف المرفوض فقط، منع الإرسال قبل مراجعة الكل |
| `EsimCatalogService` | مزامنة الباقات من المزود، تطبيق هامش 35% العام، override لباقة، إظهار/إخفاء |
| `HotelPropertyService` | الملف (مرافق، سياسة الإلغاء، الصور) |
| `RoomInventoryService` | تقويم الأسعار: تحديد أيام، ±$10، قفل/فتح البيع (Bulk) |
| `TourService` | CRUD، التسعير وحساب صافي الشريك، المواعيد والمقاعد، الشامل، البرنامج اليومي، إرسال للمراجعة |
| `TourReviewService` | موافقة/رفض الإدارة |
| `PricingService` | حساب السعر، العمولة، إعادة التحقق من السعر وموافقة العميل |
| `BookingService` | إنشاء، reference، حالات، إلغاء مع استرداد |
| `HotelBookingService` | خصم من `room_type_calendar` داخل transaction + lock، تسجيل وصول/مغادرة |
| `TourBookingService` | خصم مقاعد `tour_departures` وتحديث حالتها |
| `PaymentService` | gateway + webhook + تأكيد |
| `RefundService` | سياسات الاسترداد (72 ساعة للفندق مثلًا من `cancellation_policy`) |
| `LedgerService` | قيود البيع والعمولة والاسترداد |
| `SettlementService` | توليد تسويات كل 15 يوم (1-15 و16-آخر الشهر)، "جاهز للتحويل"/"معلّق"، تنفيذ التحويل، تقرير ضريبي |
| `CustomerService` | الملف، المسافرين المحفوظين، إيقاف الحساب |
| `ReportService` | KPIs لكل لوحة والرسوم (عمولة حسب الخدمة، إشغال، مبيعات) |
| `GlobalSearchService` | بحث موحد برقم حجز/عميل/شركة (شريط البحث في كل الشاشات) |
| `NotificationService` | الإشعارات الداخلية (جرس + شاشة إشعارات لكل لوحة) |
| `StatusHistoryService`, `AccountDeletionService` | زي ما هما |

---

## المرحلة 6 — Policies و Form Requests

- Policies: Organization, JoinRequest, HotelProperty, RoomType, Tour, Booking, VisaApplication, Settlement, Role, Membership، وكلها بتنادي `AuthorizationService`.
- Form Requests مهمة: `ApproveJoinRequestRequest` (يفشل لو في مستند غير موثق)، `StoreCustomRoleRequest` (يتحقق من عدم منح صلاحيات أعلى)، `BulkCalendarUpdateRequest` (dates[] + action)، `ReviewVisaDocumentRequest` (reason إجباري عند الرفض).

---

## المرحلة 7 — الـ Controllers والـ Routes

### 7.1 عام
`Auth\LoginController`, `Auth\PasswordController`, `Auth\InvitationController`, `PublicJoinRequestController` (store)، `GlobalSearchController`, `NotificationController` (مشترك لكل اللوحات).

### 7.2 لوحة الإدارة — `/api/admin` (سايد بار D04)
| القسم | Controller |
|---|---|
| نظرة عامة | `DashboardController` |
| الشركاء | `PartnerController` (قائمة، إضافة D08، ملف D10/D11، عمولة، تعليق)، `JoinRequestController` (D09: index/show/approve/reject)، `PartnerDocumentController` (verify/cancel) |
| كل الحجوزات | `BookingController` |
| كتالوج التأشيرات | `VisaCountryController`, `VisaTypeController`, `VisaTypeDocumentController` |
| eSIM | `EsimDashboardController`, `EsimPackageController`, `EsimSyncController`, `EsimMarginController` |
| العملاء | `CustomerController` (قائمة، تفاصيل، إيقاف الحساب) |
| المالية والعمولات | `FinanceOverviewController`, `SettlementController` (index، transfer، review)، `TransactionController`, `TaxReportController` |
| الأدوار والصلاحيات | `PlatformRoleController`, `PlatformStaffController` |

### 7.3 لوحة الشركة — `/api/company` (سايد بار D18)
`DashboardController`, `TourController` (+ `TourDepartureController`, `TourItineraryController`, `TourInclusionController`, `TourMediaController`), `BookingController` (index، cancel)، `VisaRequestController` (index/show + `VisaDocumentReviewController`)، `TeamController`, `CompanyRoleController`, `InvitationController`, `FinanceReportController`, `SettingsController` (بيانات، حساب بنكي، مستندات).

### 7.4 لوحة الفندق — `/api/hotel` (سايد بار D28)
`DashboardController`, `RoomTypeController`, `RoomCalendarController` (show + bulkUpdate)، `BookingController` (index + `checkIn` + `checkOut`)، `StaffController` + `StaffRoleController`, `PropertyProfileController`, `ReportController`.

### 7.5 لوحة الموظف — `/api/staff`
مفيش Controllers خاصة. الموظف بيستخدم نفس routes الشركة/الفندق ولكن بصلاحياته. الأقسام غير المسموحة بترجع 403 والـ `GET /api/me/navigation` بيرجع الأقسام المتاحة والمقفلة (D25: "مقفل — لن يظهر للموظف"). شاشات D35 و D36 هي `VisaRequestController` و`BookingController` (عرض فقط) بصلاحيات محددة.

### 7.6 تطبيق العميل
`Customer\SearchController`, `BookingController`, `VisaApplicationController` (رفع ملف لكل مستند)، `TravelerController`, `PaymentMethodController`, `AccountController` (حذف الحساب)، و`Webhooks\PaymentWebhookController`.

---

## المرحلة 8 — Resources و Events/Jobs

- Events: `JoinRequestApproved`, `PartnerInvited`, `BookingConfirmed`, `BookingCancelled`, `PaymentCaptured`, `RefundProcessed`, `VisaDocumentRejected`, `TourSubmittedForReview`, `SettlementTransferred`.
- Jobs / Scheduler:
  - `GenerateSettlementsJob` (يوم 1 و16)
  - `NotifyExpiringDocumentsJob` (يومي، حد 40 يوم)
  - `SyncEsimPackagesJob` (دوري)
  - `ProcessAccountDeletionsJob`, `ExpireFlightOffersJob`, `ReleaseUnpaidBookingsJob`

---

## المرحلة 9 — الاختبارات (الأهم)

1. دخول بنوع حساب غلط يفشل.
2. الموافقة على طلب انضمام مع مستند غير موثق تفشل.
3. موظف يحاول منح صلاحية أعلى من صلاحياته يفشل (D17).
4. موظف فندق مقيد بـ Property واحدة ما يشوفش غيرها.
5. Bulk update للتقويم + منع الحجز على يوم مقفول أو اتباع بالكامل.
6. حجز فندق متزامن (race condition) على آخر غرفة.
7. التسوية: تتأجل لو مستند الشريك منتهي، وصافي = مبيعات - عمولة - مرتجعات.
8. هامش eSIM: العام 35% وoverride لباقة واحدة.
9. مراجعة التأشيرة: لا يمكن الإرسال قبل مراجعة كل المستندات، ورفض مستند يطلب إعادة رفعه بس.
10. حذف الحساب (الموانع الثلاثة).

---

## المرحلة 10 — التسليم

توثيق (Scribe أو Postman)، queue worker + scheduler، rate limiting على الدخول وطلب الانضمام، private disk للمستندات والجوازات.

---

## ربط الشاشات بالـ Endpoints (المقروءة)

| الشاشة | Endpoint |
|---|---|
| D01 دخول | `POST /api/auth/login` (email, password, account_type) |
| D08 إضافة شريك | `POST /api/admin/partners` |
| D09 مراجعة طلب | `GET /api/admin/join-requests/{id}`، `POST .../documents/{id}/verify`، `POST .../approve`، `POST .../reject` |
| D10 ملف شركة | `GET /api/admin/partners/{id}`، `PATCH .../commission`، `POST .../suspend` |
| D12 كتالوج التأشيرات | `/api/admin/visa/countries`, `/visa-types`, `/visa-types/{id}/documents` |
| D13 eSIM | `/api/admin/esim/packages`, `POST /esim/sync`, `PATCH /esim/margin` |
| D15 تفاصيل عميل | `GET /api/admin/customers/{id}`, `POST .../suspend` |
| D16 المالية | `GET /api/admin/finance/overview`, `/settlements`, `POST /settlements/{id}/transfer`, `/transactions` |
| D17 الأدوار | `/api/admin/roles`, `PUT /roles/{id}/permissions` |
| D21 محرر الرحلة | `/api/company/tours/{id}` + `/departures` `/itinerary` `/inclusions` `/media`، `POST .../submit` |
| D22 حجوزات الشركة | `GET /api/company/bookings`, `POST .../{id}/cancel` |
| D23 طلبات التأشيرات | `GET /api/company/visa-requests`, `POST .../documents/{id}/accept|reject` |
| D24 / D25 الفريق والدور | `/api/company/team`, `/roles`, `POST /invitations` |
| D27 إعدادات الشركة | `/api/company/settings` (company, bank, documents) |
| D30 الغرف والأسعار | `GET /api/hotel/room-types/{id}/calendar`, `PATCH .../calendar/bulk` |
| D31 حجوزات الفندق | `GET /api/hotel/bookings`, `POST .../{id}/check-in|check-out` |
| D32 موظفو الفندق | `/api/hotel/staff`, `/staff-roles` |
| D33 ملف الفندق | `GET|PUT /api/hotel/property` |

---

## Checklist

- [ ] م1: الإعداد
- [ ] م2: Migrations (50 جدول)
- [ ] م3: Models + Enums
- [ ] م4: Permission Catalog + Seeders
- [ ] م5: Services
- [ ] م6: Policies + Requests
- [ ] م7: Controllers + Routes
- [ ] م8: Resources + Events + Jobs
- [ ] م9: الاختبارات
- [ ] م10: التسليم
