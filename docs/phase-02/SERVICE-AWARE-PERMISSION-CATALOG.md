# Service-Aware Permission Catalog

**Project:** Traway  
**Phase:** 2 — Functional & Business Analysis  
**Status:** Revised candidate catalog — exact approval required at Gate 0A  
**Authority:** This document is the proposed service-aware catalog companion to G0-DEC-02. It supersedes the historical 85-key table in `ROLE-PERMISSION-SCOPE-MATRIX.md` and the pre-multi-service eligibility analysis in `PERMISSION-CATALOG-RECONCILIATION.md`.  
**Decision basis:** Owner confirmation (2026-11-24): one service-provider organization may operate multiple services.

---

## 1. Catalog rules

A permission is not sufficient by itself. Effective organization access is the intersection of:

1. exact permission key;
2. active membership in the owning organization;
3. service enabled for that organization by Traway;
4. membership service scope includes that service (except the protected owner template, whose service reach is all services enabled for its organization);
5. applicable resource/property/assignment scope;
6. business workflow constraints.

The organization type is an eligibility guard, not a substitute for service enablement. The service is a capability guard, not a permission. Neither grants access alone.

### 1.1 Organization eligibility values

| Value | Meaning |
|---|---|
| `All` | company, hotel and service_provider organization types; never platform |
| `Company + Provider` | company and service_provider |
| `Hotel + Provider` | hotel and service_provider |
| `Company + Hotel + Provider` | company, hotel and service_provider |
| `Provider` | service_provider only |
| `Platform` | platform family only; not valid in organization permission rows |

An organization must also have the relevant service enabled. For example, `Hotel + Provider` plus `Hotels` means a hotel organization or service-provider organization may hold the key only when Hotels is enabled for that organization.

### 1.2 Service applicability values

- `—`: structural/organization-wide permission; no service line is intrinsically implied.
- `Hotels`, `Tours`, `Visas`: the permission is usable only for records belonging to that service.
- `Hotels + Tours`: generic booking operation; it applies only to actual hotel/tour booking records, never visa applications or eSIM orders.
- `Contextual: Hotels/Tours/Visas`: minimum customer contact fields may be returned only from a record already authorized under one of these services. This is not customer-directory access.
- `Platform only`: permission belongs to the platform family and cannot be granted to an organization.

eSIM is excluded from organization-side operational permissions by owner decision Q-53 (2026-11-24). Traway owns catalogue/packages, pricing/margin, orders, activation orchestration, failure handling, customer support, refund decisions and usage inquiries. No provider eSIM permissions, resource scopes, queues or dashboard modules. External supplier/API remains a platform-controlled technical integration.

### 1.3 Scope requirement values

| Value | Enforcement meaning |
|---|---|
| `platform_global` | Platform organization context only |
| `organization` | Active organization ownership; service-filtered data still applies where reports/settlements aggregate service records |
| `service` | Organization ownership + organization-enabled service + explicit membership service scope |
| `property` | Hotels service + organization ownership + explicit hotel-property scope for non-owner memberships |
| `service_resource` | Service scope plus ownership and resource-level scope/assignment for the target resource |
| `service_assignment` | Service scope plus record assignment where the workflow requires it |

**Fail-closed rule:** an empty or missing service/resource scope grants no access to a non-owner. The protected owner template is the only organization-wide exception and is still limited to services enabled for that organization. Hotel property scopes for non-owner memberships are explicitly required; zero rows means no property access.

### 1.4 Sensitivity and delegation

Sensitivity labels remain `normal`, `sensitive`, `financial`, and `access_control`. Delegability remains defined only in `config/authorization.php` under G0-DEC-02/DEC-13; do not add a duplicate database flag.

The existing non-delegable keys remain non-delegable. In addition:
- `settlements.accounts.manage` is owner-only and non-delegable;
- no `platform.*` key is delegable inside an organization;
- organization owners cannot delegate final visa decisions, platform actions, Flights, or service enablement for the organization.

---

## 2. Platform permission family (37 candidate keys)

Platform permissions are unchanged by the multi-service provider decision. All have `Platform` eligibility, `Platform only` applicability and `platform_global` scope.

| Key | Domain | Sensitivity | Description |
|---|---|---|---|
| `platform.overview.view` | overview | normal | View platform KPIs, charts and pending queue |
| `platform.notifications.view` | notifications | normal | View platform notification inbox |
| `platform.notifications.manage` | notifications | normal | Mark platform notifications read |
| `platform.bookings.view` | bookings | normal | View bookings across all tenants |
| `platform.bookings.export` | bookings | sensitive | Export the all-bookings table |
| `platform.partners.view` | partners | normal | View partner directory and details |
| `platform.partners.create` | partners | sensitive | Create a partner and invite its owner |
| `platform.partners.update` | partners | sensitive | Edit partner services, commission and profile |
| `platform.partners.suspend` | partners | sensitive | Suspend or reinstate a partner |
| `platform.partner_requests.view` | partners | normal | View partner join requests |
| `platform.partner_requests.review` | partners | sensitive | Accept/reject join requests and verify documents |
| `platform.visa_catalog.view` | catalogue | normal | View visa countries, types, fees and requirements |
| `platform.visa_catalog.manage` | catalogue | sensitive | Create, update and deactivate visa offerings |
| `platform.visa_requests.view` | visas | sensitive | View all visa applications and documents |
| `platform.visa_requests.review` | visas | sensitive | Review visa documents and request additional documents |
| `platform.visa_requests.assign` | visas | sensitive | Assign visa applications to platform staff |
| `platform.visa_requests.decide` | visas | sensitive | Record final visa approval or rejection |
| `platform.esim_catalog.view` | catalogue | normal | View platform eSIM packages, costs and margins |
| `platform.esim_catalog.manage` | catalogue | sensitive | Create and update platform eSIM packages |
| `platform.esim_catalog.sync` | catalogue | sensitive | Trigger platform eSIM provider sync |
| `platform.customers.view` | customers | sensitive | View platform customer directory and profiles |
| `platform.customers.update` | customers | sensitive | Edit customer profile data |
| `platform.customers.block` | customers | sensitive | Block or unblock a customer account |
| `platform.customers.export` | customers | sensitive | Export platform customer data |
| `platform.deletion_requests.view` | customers | sensitive | View account-deletion requests |
| `platform.deletion_requests.process` | customers | sensitive | Resolve blocked deletion requests |
| `platform.finance.view` | finance | financial | View platform payments, commissions, dues and transactions |
| `platform.finance.settlements.execute` | finance | financial | Execute or mark settlement transfers |
| `platform.finance.export` | finance | financial | Export platform financial data |
| `platform.settings.manage` | settings | access_control | Manage platform settings, global payment rules and commission defaults |
| `platform.roles.view` | access | normal | View platform roles and permissions |
| `platform.roles.manage` | access | access_control | Create, edit and delete platform roles |
| `platform.roles.assign` | access | access_control | Assign platform roles to platform staff |
| `platform.coupons.view` | coupons | normal | View platform-wide coupons |
| `platform.coupons.manage` | coupons | sensitive | Create, edit and deactivate platform coupons |
| `platform.organization_access.view` | access | sensitive | View memberships, roles and grants of any organization |
| `platform.organization_access.manage` | access | access_control | Assign roles and permissions in any organization |

**Platform-only invariants**
- Flights remain platform-operated in the current release. No organization-side flight permission is defined.
- Only authorized platform staff holding `platform.visa_requests.decide` may make the final visa decision (G0-DEC-04).
- Platform customer-directory permissions are distinct from organization `customers.view`.

---

## 3. Organization permission family (48 candidate keys)

The applicability and scope columns below are mandatory catalog metadata. Organization eligibility does not grant a service: the organization must have the service enabled, and the member must hold that service scope.

| Key | Domain | Eligible organization types | Service applicability | Scope requirement | Sensitivity | Description |
|---|---|---|---|---|---|---|
| `org.profile.view` | profile | All | — | organization | normal | View organization profile and settings |
| `org.profile.update` | profile | All | — | organization | sensitive | Edit organization profile and settings |
| `org.documents.manage` | profile | All | — | organization | sensitive | Upload or replace organization legal documents |
| `org.notifications.view` | notifications | All | — | organization | normal | View organization notification inbox |
| `employees.view` | access | All | — | organization | normal | View employees and their roles/scopes |
| `employees.create` | access | All | — | organization | access_control | Invite an employee within the grantor's ceiling |
| `employees.update` | access | All | — | organization | access_control | Edit employee profile, roles and bounded scopes |
| `employees.deactivate` | access | All | — | organization | access_control | Suspend or remove an employee |
| `roles.view` | access | All | — | organization | normal | View organization roles and permissions |
| `roles.manage` | access | All | — | organization | access_control | Create/edit/delete custom roles within grantor ceiling |
| `roles.assign` | access | All | — | organization | access_control | Assign roles within grantor ceiling |
| `bookings.view` | bookings | Company + Hotel + Provider | Hotels + Tours | service_resource | sensitive | View hotel/tour bookings and minimum guest context |
| `bookings.create` | bookings | Company + Hotel + Provider | Hotels + Tours | service_resource | sensitive | Create an eligible hotel/tour booking on behalf of a customer |
| `bookings.update` | bookings | Company + Hotel + Provider | Hotels + Tours | service_resource | sensitive | Modify eligible hotel/tour booking details |
| `bookings.cancel` | bookings | Company + Hotel + Provider | Hotels | service_resource | sensitive | Cancel an eligible hotel booking under the persisted hotel rate policy |
| `bookings.refund` | bookings | Company + Hotel + Provider | Hotels + Tours | service_resource | financial | Request/initiate an eligible refund; platform workflow governs exceptional approvals |
| `bookings.checkin` | bookings | Hotel + Provider | Hotels | property | normal | Check a hotel guest in |
| `bookings.checkout` | bookings | Hotel + Provider | Hotels | property | normal | Check a hotel guest out |
| `tour_bookings.attendance` | bookings | Company + Provider | Tours | service_resource | normal | Record attendance for an eligible confirmed tour booking within the scheduled departure/session scope |
| `tour_bookings.complete` | bookings | Company + Provider | Tours | service_resource | sensitive | Mark an eligible confirmed tour booking completed only after the scheduled session and fulfilment conditions are satisfied |
| `tour_bookings.cancel` | bookings | Company + Provider | Tours | service_resource | sensitive | Cancel an eligible tour booking under the persisted cancellation policy disclosed at purchase |
| `bookings.export` | bookings | Company + Hotel + Provider | Hotels + Tours | service_resource | sensitive | Export only authorized hotel/tour booking records |
| `tours.view` | tours | Company + Provider | Tours | service_resource | normal | View own tours and programmes |
| `tours.create` | tours | Company + Provider | Tours | service_resource | sensitive | Create a tour |
| `tours.update` | tours | Company + Provider | Tours | service_resource | sensitive | Edit an owned tour |
| `tours.delete` | tours | Company + Provider | Tours | service_resource | sensitive | Delete or withdraw an owned tour |
| `tours.submit` | tours | Company + Provider | Tours | service_resource | sensitive | Submit a tour for platform review |
| `tours.slots.manage` | tours | Company + Provider | Tours | service_resource | sensitive | Manage departure slots and seat capacity |
| `visa_requests.view` | visas | Company + Provider | Visas | service_assignment | sensitive | View requests attached to the organization's own visa offerings |
| `visa_requests.review` | visas | Company + Provider | Visas | service_assignment | sensitive | Validate documents and flag missing items; no final decision |
| `visa_offerings.view` | visas | Company + Provider | Visas | service_resource | normal | View the organization's own visa offerings |
| `visa_offerings.manage` | visas | Company + Provider | Visas | service_resource | sensitive | Create/update own visa offerings and prices |
| `properties.view` | properties | Hotel + Provider | Hotels | service | normal | View hotel properties owned by the organization |
| `properties.create` | properties | Hotel + Provider | Hotels | service | sensitive | Add a hotel property to the organization |
| `properties.update` | properties | Hotel + Provider | Hotels | property | sensitive | Edit property details, address, location and documents |
| `properties.publish` | properties | Hotel + Provider | Hotels | property | sensitive | Publish/unpublish an owned hotel property |
| `rooms.view` | rooms | Hotel + Provider | Hotels | property | normal | View room types and availability for scoped properties |
| `rooms.manage` | rooms | Hotel + Provider | Hotels | property | sensitive | Create/edit/delete room types for scoped properties |
| `rates.view` | rooms | Hotel + Provider | Hotels | property | normal | View the rate calendar for scoped properties |
| `rates.update` | rooms | Hotel + Provider | Hotels | property | sensitive | Update nightly prices and bulk adjustments |
| `rates.stop_sell` | rooms | Hotel + Provider | Hotels | property | sensitive | Apply or lift stop-sell for scoped properties |
| `coupons.view` | coupons | Company | — | organization | normal | View the company's coupons |
| `coupons.create` | coupons | Company | — | organization | sensitive | Create company-scoped coupons |
| `coupons.manage` | coupons | Company | — | organization | sensitive | Edit, limit and deactivate own coupons |
| `reports.view` | reports | All | All enabled services | organization | sensitive | View reports, filtered to enabled and membership-scoped services/resources |
| `reports.export` | reports | All | All enabled services | organization | sensitive | Export only report rows the member may view |
| `settlements.view` | settlements | All | — | organization | financial | View own organization's settlements and dues |
| `settlements.manage` | settlements | All | — | organization | financial | Request an on-demand payout; no transfer execution |
| `settlements.accounts.manage` | settlements | All | — | organization | financial | Manage payout accounts; owner-only and non-delegable |
| `status_history.view` | reports | All | All enabled services | organization | normal | View business status transitions only; never a general activity/audit feed |
| `customers.view` | customers | Company + Hotel + Provider | Contextual: Hotels/Tours/Visas | service_resource | sensitive | View minimum customer contact fields only on records already authorized through the corresponding service |

**Count: 51 organization candidate keys.** For the booking family, the eligible type set is company + hotel + service_provider; for check-in/check-out it is hotel + service_provider; tour attendance, completion and cancellation are service-provider/company candidates only for Tours and remain subject to membership service/resource scope and the suspension action matrix. This preserves the current candidate key names while replacing the obsolete organization-type-only eligibility model with service-aware eligibility. It does not authorize eSIM provider operations or Flights.

---

## 4. Role-template derivation

| Template | Candidate permission set | Effective service boundary |
|---|---|---|
| `company_owner` | All organization keys eligible to Company | Only services enabled for the company; current company capabilities remain Hotels/Tours/Visas only when explicitly enabled |
| `hotel_owner` | All organization keys eligible to Hotel | Hotels only; all organization-owned properties |
| `service_provider_owner` | All organization keys eligible to Provider | Every service enabled for that provider organization; never Flights or platform-only visa decisions |

Employee templates are starter bundles, not authorization bypasses. Every role assignment and direct grant is intersected with the membership's service/resource scopes. R-17 `service_owner` is a custom organization role, not an owner template.

The previous role matrices for company and hotel remain useful for the currently drawn COM/HTL portals. They do not define the complete permission set of a multi-service provider employee. A provider employee can combine service families only when explicitly assigned the corresponding service scope and permissions.

---

## 5. eSIM — platform-only by owner decision Q-53

Traway alone owns the complete eSIM business workflow. Customer-side eSIM purchase, activation QR issuance and platform eSIM catalogue management remain platform/customer workflows. Any supplier/API is a technical integration controlled by Traway; it is not a service-provider organization.

Until those responsibilities are defined:
- no organization-side `esim_*` permission keys are approved;
- no generic `bookings.*` permission may authorize eSIM orders;
- platform eSIM catalogue permissions remain platform-only;
- provider-side eSIM operations remain unavailable by default.

Provider-side eSIM operations are out of scope for this release by owner decision; external supplier selection and credentials remain a platform integration decision in Gate 0B.

---

## 6. Customer data boundary

Organization `customers.view` is not a directory permission. It is a contextual projection:
- caller must first pass the permission and full authorization chain for the linked booking/request;
- only the minimum fields needed for that service workflow are returned;
- unrelated bookings, saved travellers, passport/document files, payment instruments, account controls and customer directory search are excluded;
- exports must apply the same projection and scope filters;
- visa document access is governed by `visa_requests.view/review` and assignment, not by `customers.view` alone.

---

## 7. Gate 0A approval checklist

- [ ] Approve the 37 platform keys and their platform-only boundaries.
- [ ] Approve the 48 organization candidate keys and service applicability.
- [ ] Approve organization eligibility metadata for company/hotel/service_provider.
- [ ] Approve scope requirements, including service-resource and service-assignment enforcement.
- [x] Service enablement is controlled by Traway and distinct from employee service scopes (Q-54 direction approved; schema/lifecycle details remain).
- [x] Provider-side eSIM operations excluded: Traway-only business workflow (Q-53); no organization-side eSIM keys/scopes.
- [x] Unified provider dashboard direction approved (Q-55); detailed screen mapping remains. Hierarchical service/resource scoping approved in principle (Q-56); per-service resource mapping remains.

**No application code, migration, Seeder, authorization config or tests are changed by this catalog proposal.**


## 8. Implementation metadata gap (do not infer implementation)

The current `permissions` model/Seeder stores `allowed_org_types` and `scope_requirement`, but the catalog's service applicability is not currently represented as a dedicated permission attribute. The existing `membership_service_scopes` relationship and `config/authorization.php` service-scope values do not, by themselves, prove that an organization has a service enabled or that a permission is valid for that service.

Owner decisions Q-53–Q-56 now establish the product direction. Before implementation, the schema/authorization design must explicitly decide how to persist and enforce:
- organization-enabled services (platform-approved capabilities);
- permission-to-service applicability;
- membership-to-service scopes;
- resource scopes for tours and other service resources;
- the special protected-owner behavior.

Do not treat `allowed_org_types` as service enablement. Do not use `flights` in organization-side service grants in this release, even though the legacy shared service-scope enum contains that value.

**Existing code review finding:** `Membership::isRestrictedToHotel()` currently treats an empty hotel-scope list as unrestricted (`$ids === [] || ...`), while G0-DEC-03/G0-DEC-05 and the approved authorization design require an empty property scope to grant no access to non-owner staff. This is a code-level authorization gap that must be corrected and covered by negative tests before release. No code was changed in this documentation pass.


### Owner-approved scope and service boundaries — 2026-11-24

- Organization service enablement is a Traway-administered approval lifecycle: request → approve/reject → enabled; a single service can be suspended independently of the organization.
- Membership service scope is the first employee boundary. Resource/property/assignment scope further narrows access where the service has independently manageable resources.
- Hotel non-owner staff require explicit property scopes. Tour staff are scoped to applicable tours/departures/resource groups. Visa staff are scoped to assigned requests or explicitly authorized team queues. Service-only scope is valid only when no narrower resource boundary is meaningful for that workflow.
- Protected organization owners can access all resources only inside services enabled for their organization; this is not platform-wide access.
- eSIM is entirely platform-operated under Q-53 and must not appear in organization-side permission applicability, service scopes, resource scopes or provider dashboard modules.
- Provider portal direction is one unified dashboard with service-specific modules, queues and reports; exact UI mapping remains a documentation task.


### 8. Owner-approved backend boundaries — Q-53–Q-56 (2026-11-24)

- **Q-53:** eSIM is platform-only. No organization-side eSIM permission applicability, membership service scope, resource scope, provider queue or provider API tenant.
- **Q-54:** organization service enablement is Traway-controlled. An organization requests; Platform Admin approves/rejects; only enabled status permits provider operations. Traway can suspend/disable one service independently. This capability is stored separately from employee membership service scopes.
- **Q-55:** the unified provider dashboard is a frontend direction. Backend must expose independently authorized service APIs; it must not rely on hidden navigation or UI checks.
- **Q-56:** hierarchical scope: organization-enabled service → employee membership service scope → resource/property/assignment scope when meaningful. Owner reach is bounded by enabled services. Empty required scope denies.

The proposed `applicable_services` permission metadata, `organization_services` lifecycle model, resource scope options and enforcement contract are specified in [`AUTHORIZATION-DATA-MODEL-DESIGN.md`](./AUTHORIZATION-DATA-MODEL-DESIGN.md) §10. No implementation is implied by this catalog update.
