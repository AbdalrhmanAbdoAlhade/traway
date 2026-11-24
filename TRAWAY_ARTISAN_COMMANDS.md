# أوامر بناء Traway — بالترتيب الخطي

> نفّذ المراحل بالترتيب. كل مرحلة تخلص تمامًا قبل اللي بعدها.
> الأوامر تشتغل على PowerShell أو Git Bash من جذر المشروع (`D:\traway-app`).

---

## المرحلة 0 — الباكدجات والإعداد

```bash
# 1) Sanctum + ملف routes/api.php + جدول personal_access_tokens
php artisan install:api

# 2) Spatie (الصلاحيات + الصور)
composer require spatie/laravel-permission
composer require spatie/laravel-medialibrary

# 3) توثيق الـ API (اختياري)
composer require --dev knuckleswtf/scribe

# 4) تشغيل الـ queue بالـ database (الجداول موجودة افتراضيًا في Laravel)
# تأكد من .env:  QUEUE_CONNECTION=database
```

### تفعيل الـ Teams في Spatie (قبل أي migrate)

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

افتح `config/permission.php` وغيّر:

```php
'teams' => true,
'column_names' => [
    'team_foreign_key' => 'organization_id',
],
```

ثم:

```bash
php artisan config:clear
```

### دوال مساعدة (عشان الـ migrations تتولد بترتيب صحيح)

`artisan make:migration` بيحط timestamp بالثانية، فلو اتنفذ أمرين في نفس الثانية الترتيب بيبوظ. استخدم الدالة دي:

**Git Bash:**
```bash
mig() { php artisan make:migration "$@"; sleep 1; }
```

**PowerShell:**
```powershell
function mig { php artisan make:migration @args; Start-Sleep -Seconds 1 }
```

---

## المرحلة 1 — كل الـ Migrations (بالترتيب)

> الجداول الجاهزة من Laravel: `users`, `cache`, `jobs`, `sessions`, `password_reset_tokens`. جدول `users` بنعدله بـ migration إضافية.

### 1.1 الأساس

```bash
mig create_countries_table
mig create_organizations_table
mig add_traway_columns_to_users_table --table=users

# Spatie: يولّد migration الصلاحيات (اتنشرت فوق). لو ما اتولدتش:
# php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations"
mig add_traway_columns_to_roles_table --table=roles   # description, is_template, is_locked

mig create_memberships_table
mig create_membership_permissions_table
mig create_invitations_table
```

### 1.2 الانضمام والتعاقد

```bash
mig create_join_requests_table
mig create_organization_documents_table
mig create_organization_services_table
mig create_organization_visa_countries_table
```

### 1.3 كتالوج التأشيرات

```bash
mig create_visa_country_configs_table
mig create_visa_types_table
mig create_visa_type_documents_table
```

### 1.4 الفنادق

```bash
mig create_hotel_properties_table
mig create_membership_properties_table        # بعد hotel_properties
mig create_amenities_table
mig create_hotel_property_amenity_table
mig create_room_types_table
mig create_room_type_calendar_table
```

### 1.5 الصور (Medialibrary)

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
```

### 1.6 الرحلات

```bash
mig create_tours_table
mig create_tour_inclusions_table
mig create_tour_itinerary_days_table
mig create_tour_departures_table
```

### 1.7 eSIM

```bash
mig create_esim_providers_table
mig create_esim_packages_table
```

### 1.8 العملاء

```bash
mig create_customer_profiles_table
mig create_saved_travelers_table
mig create_payment_methods_table
```

### 1.9 الحجوزات

> `bookings.coupon_id` يتعمل `nullable` بدون foreign key دلوقتي، والـ FK بيتضاف مع الكوبونات في 1.12.

```bash
mig create_bookings_table
mig create_booking_items_table
mig create_booking_travelers_table
mig create_booking_hotel_stays_table
mig create_booking_tour_details_table
mig create_visa_applications_table
mig create_visa_application_documents_table
mig create_esim_orders_table
mig create_flight_offers_cache_table
```

### 1.10 المال

```bash
mig create_payments_table
mig create_refunds_table
mig create_ledger_entries_table
mig create_settlements_table
mig create_bank_accounts_table
```

### 1.11 التشغيل

```bash
mig create_status_histories_table
php artisan make:notifications-table
mig create_reviews_table
mig create_account_deletion_requests_table
mig create_platform_settings_table
```

### 1.12 الكوبونات (مؤجلة، نفّذها لما نشوف شاشة لها)

```bash
mig create_coupons_table
mig create_coupon_redemptions_table
mig add_coupon_foreign_key_to_bookings_table --table=bookings
```

### تشغيل المرحلة

```bash
php artisan migrate:fresh
```

---

## المرحلة 2 — Enums

> لو `make:enum` مش موجود في نسختك، اعمل الملفات يدويًا في `app/Enums`.

```bash
php artisan make:enum Enums/OrganizationType --string
php artisan make:enum Enums/OrganizationStatus --string
php artisan make:enum Enums/MembershipKind --string
php artisan make:enum Enums/MembershipStatus --string
php artisan make:enum Enums/JoinRequestStatus --string
php artisan make:enum Enums/DocumentType --string
php artisan make:enum Enums/DocumentStatus --string
php artisan make:enum Enums/PartnerService --string
php artisan make:enum Enums/VisaApplicationStatus --string
php artisan make:enum Enums/VisaDocumentStatus --string
php artisan make:enum Enums/TourStatus --string
php artisan make:enum Enums/DepartureStatus --string
php artisan make:enum Enums/PriceTier --string
php artisan make:enum Enums/BookingType --string
php artisan make:enum Enums/BookingStatus --string
php artisan make:enum Enums/PaymentStatus --string
php artisan make:enum Enums/LedgerType --string
php artisan make:enum Enums/SettlementStatus --string
php artisan make:enum Enums/TravelerType --string
php artisan make:enum Enums/EsimOrderStatus --string
```

---

## المرحلة 3 — كل الـ Models (بدون `-m` لأن الـ migrations خلصت)

### Traits

```bash
php artisan make:trait Traits/HasStatusHistory
php artisan make:trait Traits/ApiResponse
```

### الأساس والانضمام

```bash
php artisan make:model Country
php artisan make:model Organization
php artisan make:model Membership
php artisan make:model MembershipPermission
php artisan make:model Invitation
php artisan make:model JoinRequest
php artisan make:model OrganizationDocument
php artisan make:model OrganizationService
```

### التأشيرات والفنادق والرحلات

```bash
php artisan make:model VisaCountryConfig
php artisan make:model VisaType
php artisan make:model VisaTypeDocument

php artisan make:model HotelProperty
php artisan make:model Amenity
php artisan make:model RoomType
php artisan make:model RoomTypeCalendar      # اضبط: protected $table = 'room_type_calendar';

php artisan make:model Tour
php artisan make:model TourInclusion
php artisan make:model TourItineraryDay
php artisan make:model TourDeparture
```

### eSIM والعملاء

```bash
php artisan make:model EsimProvider
php artisan make:model EsimPackage
php artisan make:model CustomerProfile
php artisan make:model SavedTraveler
php artisan make:model PaymentMethod
```

### الحجوزات والمال

```bash
php artisan make:model Booking
php artisan make:model BookingItem
php artisan make:model BookingTraveler
php artisan make:model BookingHotelStay
php artisan make:model BookingTourDetail
php artisan make:model VisaApplication
php artisan make:model VisaApplicationDocument
php artisan make:model EsimOrder
php artisan make:model FlightOffersCache      # protected $table = 'flight_offers_cache';

php artisan make:model Payment
php artisan make:model Refund
php artisan make:model LedgerEntry
php artisan make:model Settlement
php artisan make:model BankAccount
```

### التشغيل

```bash
php artisan make:model StatusHistory
php artisan make:model Review
php artisan make:model AccountDeletionRequest
php artisan make:model PlatformSetting

# مؤجلة
php artisan make:model Coupon
php artisan make:model CouponRedemption
```

> جداول الربط (`membership_properties`, `organization_visa_countries`, `hotel_property_amenity`) ملهاش Models، بتتستخدم بـ `belongsToMany`.

---

## المرحلة 4 — Seeders

```bash
php artisan make:seeder PermissionCatalogSeeder
php artisan make:seeder OwnerTemplatesSeeder
php artisan make:seeder PlatformRolesSeeder
php artisan make:seeder StaffTemplatesSeeder
php artisan make:seeder PlatformSettingsSeeder
php artisan make:seeder PlatformOrganizationSeeder
php artisan make:seeder CountriesSeeder
php artisan make:seeder AmenitiesSeeder
```

ترتيب النداء في `DatabaseSeeder`: `PlatformSettingsSeeder`, `CountriesSeeder`, `AmenitiesSeeder`, `PermissionCatalogSeeder`, `PlatformOrganizationSeeder`, `OwnerTemplatesSeeder`, `PlatformRolesSeeder`, `StaffTemplatesSeeder`.

---

## المرحلة 5 — Services

> `make:class` بيعمل كلاس فاضي في `app/Services`.

```bash
# الهوية والصلاحيات
php artisan make:class Services/AuthService
php artisan make:class Services/AuthorizationService
php artisan make:class Services/InvitationService
php artisan make:class Services/RoleService
php artisan make:class Services/MembershipService

# الشركاء والانضمام
php artisan make:class Services/JoinRequestService
php artisan make:class Services/PartnerService
php artisan make:class Services/DocumentService

# الكتالوجات
php artisan make:class Services/VisaCatalogService
php artisan make:class Services/EsimCatalogService

# الفنادق والرحلات
php artisan make:class Services/HotelPropertyService
php artisan make:class Services/RoomInventoryService
php artisan make:class Services/TourService
php artisan make:class Services/TourReviewService

# الحجوزات
php artisan make:class Services/PricingService
php artisan make:class Services/BookingService
php artisan make:class Services/HotelBookingService
php artisan make:class Services/TourBookingService
php artisan make:class Services/VisaApplicationService
php artisan make:class Services/EsimOrderService
php artisan make:class Services/FlightSearchService

# المال
php artisan make:class Services/PaymentService
php artisan make:class Services/RefundService
php artisan make:class Services/LedgerService
php artisan make:class Services/SettlementService

# العملاء والتقارير والإشعارات
php artisan make:class Services/CustomerService
php artisan make:class Services/ReportService
php artisan make:class Services/GlobalSearchService
php artisan make:class Services/NotificationService
php artisan make:class Services/StatusHistoryService
php artisan make:class Services/AccountDeletionService

# مؤجل
php artisan make:class Services/CouponService
```

---

## المرحلة 6 — Policies و Middleware و Form Requests

### Policies

```bash
php artisan make:policy OrganizationPolicy --model=Organization
php artisan make:policy JoinRequestPolicy --model=JoinRequest
php artisan make:policy MembershipPolicy --model=Membership
php artisan make:policy HotelPropertyPolicy --model=HotelProperty
php artisan make:policy RoomTypePolicy --model=RoomType
php artisan make:policy TourPolicy --model=Tour
php artisan make:policy BookingPolicy --model=Booking
php artisan make:policy VisaApplicationPolicy --model=VisaApplication
php artisan make:policy SettlementPolicy --model=Settlement
```

### Middleware

```bash
php artisan make:middleware SetOrganizationContext
php artisan make:middleware EnsureAccountType
php artisan make:middleware EnsureOrganizationActive
php artisan make:middleware SetLocale
```

### Form Requests

```bash
# عام
php artisan make:request Auth/LoginRequest
php artisan make:request Auth/ForgotPasswordRequest
php artisan make:request Auth/AcceptInvitationRequest
php artisan make:request Public/StoreJoinRequestRequest

# الإدارة
php artisan make:request Admin/StorePartnerRequest
php artisan make:request Admin/ApproveJoinRequestRequest
php artisan make:request Admin/RejectJoinRequestRequest
php artisan make:request Admin/UpdateCommissionRequest
php artisan make:request Admin/StoreVisaTypeRequest
php artisan make:request Admin/UpdateVisaTypeDocumentsRequest
php artisan make:request Admin/UpdateEsimMarginRequest
php artisan make:request Admin/UpdateEsimPackageRequest
php artisan make:request Admin/TransferSettlementRequest
php artisan make:request Admin/StorePlatformRoleRequest

# الشركة
php artisan make:request Company/StoreTourRequest
php artisan make:request Company/UpdateTourRequest
php artisan make:request Company/StoreDepartureRequest
php artisan make:request Company/StoreItineraryDayRequest
php artisan make:request Company/CancelBookingRequest
php artisan make:request Company/ReviewVisaDocumentRequest
php artisan make:request Company/InviteStaffRequest
php artisan make:request Company/StoreCustomRoleRequest
php artisan make:request Company/UpdateCompanySettingsRequest

# الفندق
php artisan make:request Hotel/BulkCalendarUpdateRequest
php artisan make:request Hotel/StoreRoomTypeRequest
php artisan make:request Hotel/UpdateRoomTypeRequest
php artisan make:request Hotel/UpdatePropertyProfileRequest
php artisan make:request Hotel/StoreHotelStaffRequest

# العميل
php artisan make:request Customer/StoreBookingRequest
php artisan make:request Customer/UploadVisaDocumentRequest
php artisan make:request Customer/StoreTravelerRequest
```

---

## المرحلة 7 — Controllers

> `--api` يولّد (index/store/show/update/destroy). الباقي controllers عادية للـ actions.

### 7.1 عام

```bash
php artisan make:controller Api/Auth/LoginController
php artisan make:controller Api/Auth/PasswordController
php artisan make:controller Api/Auth/InvitationController
php artisan make:controller Api/PublicJoinRequestController
php artisan make:controller Api/GlobalSearchController
php artisan make:controller Api/NotificationController
php artisan make:controller Api/NavigationController
php artisan make:controller Api/Webhooks/PaymentWebhookController
```

### 7.2 لوحة الإدارة (`/api/admin`)

```bash
php artisan make:controller Api/Admin/DashboardController
php artisan make:controller Api/Admin/PartnerController --api
php artisan make:controller Api/Admin/JoinRequestController
php artisan make:controller Api/Admin/PartnerDocumentController
php artisan make:controller Api/Admin/BookingController
php artisan make:controller Api/Admin/VisaCountryController --api
php artisan make:controller Api/Admin/VisaTypeController --api
php artisan make:controller Api/Admin/VisaTypeDocumentController
php artisan make:controller Api/Admin/EsimDashboardController
php artisan make:controller Api/Admin/EsimPackageController --api
php artisan make:controller Api/Admin/EsimSyncController
php artisan make:controller Api/Admin/EsimMarginController
php artisan make:controller Api/Admin/CustomerController
php artisan make:controller Api/Admin/FinanceOverviewController
php artisan make:controller Api/Admin/SettlementController
php artisan make:controller Api/Admin/TransactionController
php artisan make:controller Api/Admin/TaxReportController
php artisan make:controller Api/Admin/PlatformRoleController --api
php artisan make:controller Api/Admin/PlatformStaffController --api
php artisan make:controller Api/Admin/TourReviewController
php artisan make:controller Api/Admin/HotelReviewController
```

### 7.3 لوحة شركة السياحة (`/api/company`)

```bash
php artisan make:controller Api/Company/DashboardController
php artisan make:controller Api/Company/TourController --api
php artisan make:controller Api/Company/TourDepartureController --api
php artisan make:controller Api/Company/TourItineraryController --api
php artisan make:controller Api/Company/TourInclusionController --api
php artisan make:controller Api/Company/TourMediaController
php artisan make:controller Api/Company/BookingController
php artisan make:controller Api/Company/VisaRequestController
php artisan make:controller Api/Company/VisaDocumentReviewController
php artisan make:controller Api/Company/TeamController --api
php artisan make:controller Api/Company/InvitationController
php artisan make:controller Api/Company/CompanyRoleController --api
php artisan make:controller Api/Company/FinanceReportController
php artisan make:controller Api/Company/SettingsController
php artisan make:controller Api/Company/BankAccountController
php artisan make:controller Api/Company/DocumentController
```

### 7.4 لوحة الفندق (`/api/hotel`)

```bash
php artisan make:controller Api/Hotel/DashboardController
php artisan make:controller Api/Hotel/RoomTypeController --api
php artisan make:controller Api/Hotel/RoomCalendarController
php artisan make:controller Api/Hotel/BookingController
php artisan make:controller Api/Hotel/StaffController --api
php artisan make:controller Api/Hotel/StaffRoleController
php artisan make:controller Api/Hotel/PropertyProfileController
php artisan make:controller Api/Hotel/PropertyMediaController
php artisan make:controller Api/Hotel/ReportController
```

### 7.5 تطبيق العميل (`/api/customer`)

```bash
php artisan make:controller Api/Customer/SearchController
php artisan make:controller Api/Customer/BookingController
php artisan make:controller Api/Customer/VisaApplicationController
php artisan make:controller Api/Customer/TravelerController --api
php artisan make:controller Api/Customer/PaymentMethodController --api
php artisan make:controller Api/Customer/EsimController
php artisan make:controller Api/Customer/ReviewController
php artisan make:controller Api/Customer/AccountController
```

> لوحة الموظف مفيش لها Controllers خاصة (بتستخدم controllers الشركة والفندق بصلاحياته، و`NavigationController` بيرجع الأقسام المتاحة).

---

## المرحلة 8 — Resources و Events و Listeners و Jobs و Notifications

### API Resources

```bash
php artisan make:resource OrganizationResource
php artisan make:resource MembershipResource
php artisan make:resource JoinRequestResource
php artisan make:resource OrganizationDocumentResource
php artisan make:resource VisaTypeResource
php artisan make:resource VisaApplicationResource
php artisan make:resource HotelPropertyResource
php artisan make:resource RoomTypeResource
php artisan make:resource RoomCalendarDayResource
php artisan make:resource TourResource
php artisan make:resource TourDepartureResource
php artisan make:resource EsimPackageResource
php artisan make:resource BookingResource
php artisan make:resource CustomerResource
php artisan make:resource PaymentResource
php artisan make:resource SettlementResource
php artisan make:resource RoleResource
```

### Events و Listeners

```bash
php artisan make:event JoinRequestApproved
php artisan make:event PartnerInvited
php artisan make:event BookingConfirmed
php artisan make:event BookingCancelled
php artisan make:event PaymentCaptured
php artisan make:event RefundProcessed
php artisan make:event VisaDocumentRejected
php artisan make:event TourSubmittedForReview
php artisan make:event SettlementTransferred

php artisan make:listener SendPartnerInvitation --event=PartnerInvited
php artisan make:listener CreateOwnerMembership --event=JoinRequestApproved
php artisan make:listener RecordSaleInLedger --event=PaymentCaptured
php artisan make:listener RecordRefundInLedger --event=RefundProcessed
php artisan make:listener NotifyCustomerOfRejectedDocument --event=VisaDocumentRejected
php artisan make:listener NotifyAdminsOfTourSubmission --event=TourSubmittedForReview
php artisan make:listener NotifyPartnerOfTransfer --event=SettlementTransferred
php artisan make:listener SendBookingConfirmation --event=BookingConfirmed
php artisan make:listener ReleaseInventoryOnCancel --event=BookingCancelled
```

### Notifications

```bash
php artisan make:notification PartnerInvitationNotification
php artisan make:notification JoinRequestDecisionNotification
php artisan make:notification DocumentExpiringNotification
php artisan make:notification VisaDocumentRejectedNotification
php artisan make:notification BookingConfirmedNotification
php artisan make:notification SettlementTransferredNotification
```

### Jobs

```bash
php artisan make:job GenerateSettlementsJob
php artisan make:job NotifyExpiringDocumentsJob
php artisan make:job SyncEsimPackagesJob
php artisan make:job ProcessAccountDeletionsJob
php artisan make:job ExpireFlightOffersJob
php artisan make:job ReleaseUnpaidBookingsJob
```

جدولتها في `routes/console.php`:

```php
Schedule::job(new GenerateSettlementsJob)->twiceMonthly(1, 16, '02:00');
Schedule::job(new NotifyExpiringDocumentsJob)->daily();
Schedule::job(new SyncEsimPackagesJob)->hourly();
Schedule::job(new ProcessAccountDeletionsJob)->daily();
Schedule::job(new ExpireFlightOffersJob)->everyFifteenMinutes();
Schedule::job(new ReleaseUnpaidBookingsJob)->everyFiveMinutes();
```

---

## المرحلة 9 — الاختبارات

```bash
php artisan make:test Auth/LoginByAccountTypeTest
php artisan make:test Admin/ApproveJoinRequestTest
php artisan make:test Admin/RoleEscalationTest
php artisan make:test Admin/SettlementTest
php artisan make:test Admin/EsimMarginTest
php artisan make:test Company/VisaReviewTest
php artisan make:test Company/TourWorkflowTest
php artisan make:test Hotel/PropertyScopeTest
php artisan make:test Hotel/CalendarBulkUpdateTest
php artisan make:test Hotel/ConcurrentBookingTest
php artisan make:test Customer/AccountDeletionTest
php artisan make:test Customer/BookingPriceRevalidationTest
```

---

## المرحلة 10 — تحقق وتوثيق

```bash
php artisan migrate:fresh --seed
php artisan route:list --path=api
php artisan test
php artisan scribe:generate
php artisan schedule:list
php artisan queue:work --tries=3
```

---

## ترتيب العمل (ملخص)

| # | المرحلة | الأوامر |
|---|---|---|
| 0 | الباكدجات والإعداد | install:api، composer require، تعديل config الـ teams |
| 1 | Migrations | ~50 أمر `mig` + 2 publish |
| 2 | Enums | 20 |
| 3 | Models | ~45 |
| 4 | Seeders | 8 |
| 5 | Services | 30 |
| 6 | Policies / Middleware / Requests | 9 / 4 / 33 |
| 7 | Controllers | ~60 |
| 8 | Resources / Events / Jobs / Notifications | 17 / 9 + 9 / 6 / 6 |
| 9 | Tests | 12 |
| 10 | تحقق | migrate:fresh --seed وroute:list وtest |
