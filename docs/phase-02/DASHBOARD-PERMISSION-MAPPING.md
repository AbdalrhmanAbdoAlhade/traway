# Dashboard Screen-to-Permission & Data-Scope Mapping

**Project:** Traway  
**Status:** Source-derived mapping + proposed authorization mapping; Gate 0A approval required  
**Date:** 2026-11-24  
**Sources:** `phase-01/UI-SCREENS-INVENTORY.md`, `phase-02/FEATURES-CATALOG.md`, supplied dashboard montage, `SERVICE-AWARE-PERMISSION-CATALOG.md`, `MULTI-SERVICE-PROVIDER-AUTHORIZATION.md`

This document maps the dashboard screens actually present in the supplied UI corpus to the candidate permissions and data boundaries. It does not claim that a multi-service provider dashboard already exists. UI facts and proposed backend authorization are deliberately distinguished.

---

## 1. What the supplied dashboard designs actually contain

The supplied montage corresponds to the four dashboard groups already inventoried in Phase 1:

| App | Inventory IDs | Audience | Confirmed screens |
|---|---|---|---|
| APP-ADM | D-03.1–D-03.15 | Traway platform staff | Overview, notifications, all bookings, partners, add partner, join review, partner details, visa catalogue, eSIM catalogue, customer deletion requests, customer directory/detail, finance, roles |
| APP-COM | D-04.1–D-04.10 | Tourism-company partner | Overview, notifications, tours, tour editor, bookings, visa request review, team, role editor, finance, company settings |
| APP-HTL | D-05.1–D-05.7 | Hotel partner | Overview, notifications, rooms/rates, bookings, employees, hotel profile, reports |
| APP-EMP | D-06.1–D-06.3 | Restricted company employee | Assigned visa requests, read-only bookings, notifications |

The corpus does **not** contain a separate complete APP-SP multi-service provider portal. APP-COM is explicitly a tourism-company portal; APP-HTL is explicitly a hotel portal; APP-EMP is a restricted company employee shell. These are source facts from the UI inventory, not assumptions.

The platform's partner forms show supported services on D-03.5 and D-03.7/D-03.8, but the supplied partner screens still split the tourism-company and hotel experiences. A single organization operating multiple services is an owner-confirmed product rule; the dashboard navigation and workflows for that combined tenant are not yet fully drawn.

---

## 2. Mapping rules

1. The candidate permission keys below are drawn from `SERVICE-AWARE-PERMISSION-CATALOG.md`. They are not yet the approved final catalog.
2. A permission authorizes an operation, never all records in a table.
3. Organization-side records must always be filtered by active `organization_id`.
4. Service operations additionally require organization service enablement and the membership's explicit service scope for non-owner staff.
5. Property/resource/assignment restrictions are additional filters, not substitutes for service scope.
6. Dashboard KPIs, charts, counts, reports, exports and notification targets must use the same scope as their underlying rows.
7. UI hiding/locked sidebar states are not security controls; the API must enforce the same restriction.
8. The APP-EMP UI demonstrates assigned visa work and read-only bookings; it does not define a global employee role that applies identically to every service.

---

## 3. APP-ADM — Traway platform administration

Platform permissions are always platform-family keys. They are never granted to partner memberships.

| Screen | Visible capability from UI | Candidate permission(s) | Data boundary / enforcement |
|---|---|---|---|
| D-03.1 Overview | Platform KPIs, sales-by-service, pending queue, latest bookings, export | `platform.overview.view`; queue/booking details also require `platform.bookings.view`; export requires `platform.bookings.export`; partner/visa/eSIM queue cards require their corresponding view permissions | Platform-wide only for staff holding each permission; each KPI must be independently filtered by the staff member's permitted data family |
| D-03.2 Notifications | View, filter, mark read | `platform.notifications.view`, `platform.notifications.manage` | Platform notification inbox; target links must re-check target permission |
| D-03.3 All bookings | Cross-service bookings list and export | `platform.bookings.view`, `platform.bookings.export` | Cross-tenant platform scope; partner identity is visible to authorized platform staff |
| D-03.4 Partners | Partner directory, counts, status, add/review actions | `platform.partners.view`, `platform.partners.create`, `platform.partner_requests.view` | Platform partner directory; counts must not leak hidden partner categories to restricted staff |
| D-03.5 Add partner | Partner type, supported services, commission, owner invite | `platform.partners.create`; changes to services/commission also `platform.partners.update` | Creating an organization and enabling its services are platform-controlled actions; do not let partner owners self-enable services |
| D-03.6 Join review | Documents, requested services, accept/reject | `platform.partner_requests.view`, `platform.partner_requests.review` | Review only the submitted organization and its documents; activation must persist approved service enablement |
| D-03.7 / D-03.8 Partner detail | Allowed services, documents, team, KPIs, commission, suspend | `platform.partners.view`; service/commission edits `platform.partners.update`; suspension `platform.partners.suspend`; access details `platform.organization_access.view` | One selected organization; service configuration is the source of organization-enabled services |
| D-03.9 Visa catalogue | Countries, visa types, prices, required documents | `platform.visa_catalog.view`, `platform.visa_catalog.manage` | Platform visa catalogue; separate from partner visa-offering ownership and application review |
| D-03.10 eSIM catalogue | Provider API, package cost/sale/margin, sync, export | `platform.esim_catalog.view`, `platform.esim_catalog.manage`, `platform.esim_catalog.sync` | Platform catalogue and supplier integration only; this screen does not prove partner-side eSIM order-management permissions |
| D-03.11 Deletion requests | View deletion queue | `platform.deletion_requests.view` | Platform deletion-request scope |
| D-03.12 / D-03.13 Customers | Global directory, profile, payment/travellers/bookings, block/export/report | `platform.customers.view`, `platform.customers.update`, `platform.customers.block`, `platform.customers.export` | Platform-wide customer directory; never exposed by organization `customers.view` |
| D-03.14 Finance | Platform transactions, commissions, partner dues, transfers, export | `platform.finance.view`, `platform.finance.export`, `platform.finance.settlements.execute` | Platform financial ledger; transfer execution is platform-only |
| D-03.15 Roles & permissions | Platform roles and organization access administration | `platform.roles.view`, `platform.roles.manage`, `platform.roles.assign`, `platform.organization_access.view`, `platform.organization_access.manage` | Platform staff role family vs organization role family must remain distinct; organization assignment is explicitly privileged |

**Visa decision boundary:** the partner visa desk may validate documents, but final approve/reject remains `platform.visa_requests.decide` under G0-DEC-04.

---

## 4. APP-COM — Tourism-company partner portal

The current design is a tourism-company portal. For a multi-service provider organization, these screens can be service modules within a unified tenant shell, but the UI inventory does not yet confirm the unified shell or all additional modules.

| Screen | Visible capability from UI | Candidate permission(s) | Data boundary / enforcement |
|---|---|---|---|
| D-04.1 Overview | Sales/bookings/visa KPIs, today queue, upcoming tours, new tour | `reports.view`; `bookings.view`; `visa_requests.view`; `tours.view`; `tours.create`; possibly `status_history.view` for business status transitions | KPIs and queue cards must each be filtered by enabled service + membership service scope + owned resources/assignments |
| D-04.2 Notifications | View/filter notifications | `org.notifications.view` | Organization inbox only; each target is re-authorized |
| D-04.3 Tours | Tour portfolio, statuses, occupancy, edit, bookings, create | `tours.view`, `tours.update`, `tours.create`, `bookings.view` | Own organization's tours only; employee service scope; any employee-specific tour scope/assignment must be defined before use |
| D-04.4 Tour editor | Create/edit pricing, slots, itinerary, images, submit | `tours.create`, `tours.update`, `tours.slots.manage`, `tours.submit` | Tour must belong to active organization; slots inherit parent tour ownership; review/publish state machine remains enforced |
| D-04.5 Bookings | List/filter/export bookings | `bookings.view`, `bookings.export`; row actions need specific mutation permissions if later supplied | Only Hotels/Tours bookings for the active organization and allowed services/resources; never visa applications or eSIM orders through generic bookings |
| D-04.6 Visa requests | Applicant queue, per-document accept/reject, accept all, next applicant | `visa_requests.view`, `visa_requests.review` | Organization's own visa offerings/applications; assigned queue for employee role; document validation only, no final visa decision |
| D-04.7 Team | View/invite/edit members and roles | `employees.view`, `employees.create`, `employees.update`, plus `roles.view`, `roles.assign` | Only members of this organization; grants limited by grantor's own permissions and service/resource scope |
| D-04.8 Custom role editor | Fine-grained role permissions | `roles.view`, `roles.manage` | Role permissions must be intersected with organization-enabled services and each member's service/resource scopes; no privilege escalation |
| D-04.9 Financial reports | Sales, bookings, commission, net, settlements, export | `reports.view`, `reports.export`, `settlements.view` | Organization's own financial records; report rows filtered by enabled services and member scopes; payout request needs `settlements.manage` only if exposed |
| D-04.10 Company settings | Profile, docs, account | `org.profile.view`, `org.profile.update`, `org.documents.manage` | Active organization only; licence/document changes may trigger platform re-verification |

### UI conflict to preserve explicitly

The UI inventory describes a “team activity feed” on D-04.1. The later feature catalog describes this as business status-history updates, and G0-DEC-01 explicitly excludes general activity/audit logs. Therefore the backend must **not** build a general team activity feed. If the dashboard needs status history, expose only business-entity status transitions through `status_history.view`; do not record generic user activity.

---

## 5. APP-HTL — Hotel partner portal

| Screen | Visible capability from UI | Candidate permission(s) | Data boundary / enforcement |
|---|---|---|---|
| D-05.1 Overview | Occupancy, revenue, room availability, arrivals, update prices | `reports.view`, `bookings.view`, `rates.view`, `rates.update` | Only scoped hotel properties; every KPI and arrival row must use the same property filter |
| D-05.2 Notifications | Hotel notifications | `org.notifications.view` | Organization inbox; notification target must be property-authorized |
| D-05.3 Rooms & rates | Room types, daily rates, availability, stop-sell, bulk updates | `rooms.view`, `rooms.manage`, `rates.view`, `rates.update`, `rates.stop_sell` | Explicit hotel-property scope for non-owner staff; property must belong to active organization |
| D-05.4 Bookings | Booking list, check-in/out, export | `bookings.view`, `bookings.checkin`, `bookings.checkout`, `bookings.export` | Hotel bookings only; booking.hotel_id must belong to organization and be within member's hotel scopes |
| D-05.5 Employees | Reception/booking manager/accountant permission toggles | `employees.view`, `employees.create`, `employees.update`, `roles.view`, `roles.manage`, `roles.assign` | Employee permissions are bounded by the owner/grantor; property scopes must be assigned independently |
| D-05.6 Hotel profile | Property gallery, amenities, location, policy, publish | `properties.view`, `properties.update`, `properties.publish` | Property belongs to active organization; non-owner must have explicit property scope |
| D-05.7 Reports | Revenue, occupancy, ADR, cancellations, room-type performance, export | `reports.view`, `reports.export` | Organization-owned data filtered to enabled Hotels service and assigned properties |

For a multi-service `service_provider` organization, these hotel operations must work under the same organization tenant when Hotels is enabled. The current source code's hotel-type-only scope check is insufficient and must be corrected as documented in `AUTHORIZATION-DATA-MODEL-DESIGN.md`.

---

## 6. APP-EMP — Restricted employee portal

The supplied employee portal is explicitly a restricted **company employee** portal, with assigned visa requests and read-only bookings.

| Screen | Visible capability from UI | Candidate permission(s) | Data boundary / enforcement |
|---|---|---|---|
| D-06.1 Assigned visa requests | Assigned queue, document accept/reject, request permission | `visa_requests.view`, `visa_requests.review` | Must be assigned to this employee and belong to the active organization; no final visa decision |
| D-06.2 Read-only bookings | View booking table | `bookings.view` | Read-only because no mutation permission is granted; service/resource scopes still apply |
| D-06.3 Notifications | Assigned-request notifications, locked modules | `org.notifications.view` | Organization notifications only; locked team/finance/settings routes must be denied server-side |

The UI's “request additional permission from owner” is a request workflow, not a grant. The owner must separately approve and assign the permission/scope.

Do not generalize this employee shell to all provider services. A multi-service provider may need employee modules for Hotels, Tours, Visas and (if approved) eSIM, each separately scoped.

---

## 7. Unified multi-service provider portal — confirmed rule vs missing UI

### Confirmed by owner
- One provider organization may operate more than one service.
- Organization is the tenant boundary.
- Platform controls which services are enabled for the organization.
- Owner assigns employees only within enabled services and bounded resources.
- Employees may have multiple service scopes.
- Flights remain platform-only.
- Provider visa staff do not make final approval/rejection decisions.

### Present in supplied UI
- Company portal: Tours + visa request review + bookings + team + finance + settings.
- Hotel portal: properties/rooms/rates + hotel bookings + hotel staff + reports.
- Admin partner screens: supported services, partner details, service/commission controls.
- Employee portal: assigned visa queue + read-only bookings.

### Not yet present as a complete confirmed flow
- One unified provider dashboard showing all enabled services in one tenant shell.
- A service switcher or service-aware sidebar.
- Platform enable/disable service workflow details and organization capability state.
- Employee assignment UI for multiple service scopes.
- Resource/property assignment UI for provider employees.
- Unified employee portal modules across multiple services.
- Provider-side eSIM package/order/activation/support workflows.
- Provider-side service-specific reports and export screens beyond current COM/HTL examples.

These are UI coverage gaps. Do not invent new screen IDs or assume an unseen UI behavior is approved. Q-54, Q-55 and Q-56 remain relevant.

---

## 8. Candidate dashboard permission bundles (not final role presets)

The following are capability groupings implied by the UI, not new hard-coded roles:

| Candidate bundle | Screen evidence | Permission areas |
|---|---|---|
| Provider owner | D-04.1–D-04.10 + D-05.1–D-05.7 where services enabled | Eligible organization permissions for enabled services, employees/roles, reports, settlements, profile; protected owner template |
| Tours operator | D-04.3–D-04.5 | `tours.*`, Hotels/Tours-specific `bookings.*` as needed, contextual `customers.view` |
| Visa officer | D-04.6, D-06.1 | `visa_requests.view/review`, assigned-only queue, no final decision |
| Hotel receptionist | D-05.1, D-05.4 | `bookings.view`, `bookings.checkin`, `bookings.checkout` within assigned property |
| Hotel revenue/rooms manager | D-05.3, D-05.7 | `rooms.*`, `rates.*`, `reports.view/export` within assigned properties |
| Organization accountant | D-04.9, D-05.7 | `reports.view/export`, `settlements.view`; payout account/settlement actions only if separately granted |
| Platform support/content/finance | D-03.1–D-03.15 | Explicit platform permission family only; never infer broad access from job title |

Role names and bundles must remain data-driven. A membership can hold several roles, but its effective permission union is still intersected with enabled-service, membership-service, resource/property and assignment scopes.

---

## 9. Implementation blockers before dashboard/API completion

1. Add platform-controlled organization-enabled services, separate from membership service scopes.
2. Persist and enforce permission-to-service applicability.
3. Refactor hotel property scoping so it applies to Hotels operations for both `hotel` and multi-service `service_provider` organizations.
4. Fix empty service/property scope behavior to deny non-owner access.
5. Add query-level scope to list, dashboard KPI, chart, report and export paths.
6. Derive service from persisted resources; do not trust client-supplied service context.
7. Keep APP-EMP assignment-based visa access and read-only booking behavior.
8. Keep provider visa review separate from platform final decision.
9. Do not implement provider eSIM permissions until Q-53 is resolved.
10. Update the UI inventory only when the unified provider screens are actually supplied/approved.

**Implementation status:** this is a documentation mapping only. No application code, database migration, Seeder, policy, API or test was changed.
