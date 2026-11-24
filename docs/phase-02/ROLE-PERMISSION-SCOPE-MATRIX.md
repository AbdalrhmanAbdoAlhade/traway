# Phase 2 — Role, Permission & Scope Matrix

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Decisions DEC-11 – DEC-19 applied — awaiting approval (`APPROVE PHASE 3`)
**Companion:** [`AUTHORIZATION-ARCHITECTURE.md`](./AUTHORIZATION-ARCHITECTURE.md)
**Date:** 2026-11-23 · **Reconciled:** 2026-11-24

---

## 1. How to read this document

| Symbol | Meaning |
|---|---|
| ● | Full capability within the role's scope |
| ◐ | Partial — only the listed subset |
| ○ | No access |
| **S** | Conditional on service scope (membership must include the service) |
| **A** | Assigned records only |
| `D` | Decision-backed capability with no drawn screen (DEC-03, DEC-06, DEC-11 – DEC-19) |

Two families are kept separate throughout:

* **Platform permissions** (`platform.*`) apply across tenants and are held only by platform-role members.
* **Organization permissions** (all other keys) apply inside one company, hotel or service-provider organization and are held only by members of that organization.
* **Owner templates** (`company_owner`, `hotel_owner`, `service_provider_owner`) are protected and are not custom roles.

---

## 2. Role summary (19 roles)

| ID | Role | Family | Organization type | Scope archetype |
|---|---|---|---|---|
| R-01 | Traveller | Customer | — | Self-only (ownership, not RBAC) |
| R-02 | Guest | Customer | — | Public read-only |
| R-03 | Platform super admin | Platform | Platform | Platform-global |
| R-04 | Platform finance | Platform | Platform | Platform-global (financial) |
| R-05 | Platform content manager | Platform | Platform | Platform-global (catalogue/marketing) |
| R-06 | Platform support | Platform | Platform | Platform-global (read/assist) |
| R-07 | Company owner | Organization | Company | Organization-wide, all services |
| R-08 | Bookings employee | Organization | Company | Organization-wide bookings |
| R-09 | Visa offerings manager | Organization | Company | Service-scoped: visas; view only on requests |
| R-10 | Accountant | Organization | Company | Organization-wide (read finance) |
| R-11 | Content manager | Organization | Company | Service-scoped: tours |
| R-12 | Hotel owner | Organization | Hotel | Organization-wide, all properties |
| R-13 | Reception | Organization | Hotel | Property-scoped, arrivals |
| R-14 | Hotel bookings manager | Organization | Hotel | Property-scoped bookings + rooms/rates |
| R-15 | Hotel accountant | Organization | Hotel | Property-scoped read financial |
| R-16 | Restricted employee | Organization | Company | Assigned-only + service scope |
| R-17 | Service owner (custom role) | Organization | Company | Service-scoped management; not an owner template |
| R-18 | Platform visa officer | Platform | Platform | Platform-global (visa decisions) |
| R-19 | Service provider owner | Organization | Service provider | Organization-wide |

**Customers (R-01/R-02) do not use this catalog.** Their access is enforced by ownership policies (a traveller may only read rows whose `user_id` is their own) and by public endpoint allow-lists. Assigning RBAC permissions to customers would be both unnecessary and risky.

---

## 3. Permission catalog (85 keys — proposed, pending Gate 0A approval)

This catalog is the reconciled canonical set from `../PERMISSION-CATALOG-RECONCILIATION.md` (37 platform + 48 organization). The centralized-catalog principle is approved (`G0-DEC-02`); **the exact keys are not yet approved**. Compared with the current 80-key seeder it adds 6 keys, removes 1 (`org.profile.publish`) and renames 1 (`team_activity.view` → `status_history.view`).

### 3.1 Platform family (37)

| Key | Domain | Sensitivity | Description | Screens | Features |
|---|---|---|---|---|---|
| `platform.overview.view` | overview | Normal | View platform KPIs, charts and pending queue | D-03.1 | F-078 |
| `platform.notifications.view` | notifications | Normal | View platform notification inbox | D-03.2 | F-079 |
| `platform.notifications.manage` | notifications | Normal | Mark notifications read; manage inbox state | D-03.2 | F-079 |
| `platform.bookings.view` | bookings | Normal | View bookings across all tenants | D-03.3 | F-080 |
| `platform.bookings.export` | bookings | Sensitive | Export the all-bookings table | D-03.3 | F-080 |
| `platform.partners.view` | partners | Normal | View partner directory and partner details | D-03.4, D-03.7, D-03.8 | F-081, F-083 |
| `platform.partners.create` | partners | Sensitive | Create a partner and invite its owner | D-03.5 | F-082 |
| `platform.partners.update` | partners | Sensitive | Edit partner commission, services and data | D-03.7, D-03.8 | F-083 |
| `platform.partners.suspend` | partners | Sensitive | Suspend or reinstate a partner | D-03.7, D-03.8 | F-083 |
| `platform.partner_requests.view` | partners | Normal | View join requests | D-03.4, D-03.6 | F-081, F-077 |
| `platform.partner_requests.review` | partners | Sensitive | Accept/reject join requests and verify documents | D-03.6 | F-077 |
| `platform.visa_catalog.view` | catalogue | Normal | View visa countries, types, fees and documents | D-03.9 | F-084 |
| `platform.visa_catalog.manage` | catalogue | Sensitive | Create/update/deactivate visa offerings and requirements | D-03.9 | F-084 |
| `platform.visa_requests.view` | visas | Sensitive | View all visa applications and documents | D-03.3, D-04.6 (platform oversight) | F-096, F-108 |
| `platform.visa_requests.review` | visas | Sensitive | Review documents and request additional documents; no final decision (DEC-04) | D-03.3, D-04.6 | F-096, F-108 |
| `platform.visa_requests.assign` | visas | Sensitive | Assign visa applications to platform staff | D-03.3 | F-096 |
| `platform.visa_requests.decide` | visas | Sensitive | Record the final approval or rejection (DEC-04) | D-03.3 (D) | F-096 |
| `platform.esim_catalog.view` | catalogue | Normal | View eSIM packages, costs and margins | D-03.10 | F-085 |
| `platform.esim_catalog.manage` | catalogue | Sensitive | Create/update packages and availability | D-03.10 | F-085 |
| `platform.esim_catalog.sync` | catalogue | Sensitive | Trigger provider sync and price comparison | D-03.10 | F-085 |
| `platform.customers.view` | customers | Sensitive | View customer directory and profiles | D-03.12, D-03.13 | F-087, F-088 |
| `platform.customers.update` | customers | Sensitive | Edit customer profile data | D-03.13 | F-088 |
| `platform.customers.block` | customers | Sensitive | Block or unblock a customer account | D-03.13 | F-088 |
| `platform.customers.export` | customers | Sensitive | Export customer data | D-03.12 | F-087 |
| `platform.deletion_requests.view` | customers | Sensitive | View account-deletion requests | D-03.11 | F-086 |
| `platform.deletion_requests.process` | customers | Sensitive | Approve, cancel or execute deletion | D-03.11 | F-086 |
| `platform.finance.view` | finance | Financial | View payments, commissions, dues and transactions | D-03.14 | F-089 |
| `platform.finance.settlements.execute` | finance | Financial | Execute or mark settlement transfers | D-03.14 | F-089 |
| `platform.finance.export` | finance | Financial | Export financial data | D-03.14 | F-089 |
| `platform.settings.manage` | settings | Access-control | Manage platform settings, global payment rules and commission defaults | — (D) | F-112 |
| `platform.roles.view` | access | Normal | View platform roles and permissions | D-03.15 | F-090, F-112 |
| `platform.roles.manage` | access | Access-control | Create, edit and delete platform roles | D-03.15 | F-090, F-112 |
| `platform.roles.assign` | access | Access-control | Assign platform roles to staff | D-03.15 | F-090, F-112 |
| `platform.coupons.view` | coupons | Normal | View platform-wide coupons | — (D) | F-111 |
| `platform.coupons.manage` | coupons | Sensitive | Create, edit, deactivate platform coupons | — (D) | F-111 |
| `platform.organization_access.view` | access | Sensitive | View memberships, roles and grants of any organization | — (D) | F-112 |
| `platform.organization_access.manage` | access | Access-control | Assign roles and permissions to owners and employees of any organization | — (D) | F-112 |

### 3.2 Organization family (48)

Org type values:
- Company + Hotel.
- `Company` = company only.
- `Hotel` = hotel only.
- `Company + Hotel` = company and hotel only; excludes service providers.

| Key | Domain | Org type | Sensitivity | Description | Screens | Features |
|---|---|---|---|---|---|---|
| `org.profile.view` | profile | All | Normal | View organization profile and settings | D-04.10, D-05.6 | F-100, F-106, F-114 |
| `org.profile.update` | profile | All | Sensitive | Edit organization profile and settings | D-04.10, D-05.6 | F-100, F-106, F-114 |
| `org.documents.manage` | profile | All | Sensitive | Upload/replace legal documents | D-04.10, D-05.6 | F-100, F-106, F-114 |
| `org.notifications.view` | notifications | All | Normal | View organization notification inbox | D-04.2, D-05.2, D-06.3 | F-092, F-102, F-110 |
| `employees.view` | access | All | Normal | View employees and their roles | D-04.7, D-05.5 | F-097, F-105, F-112, F-114 |
| `employees.create` | access | All | Access-control | Invite an employee | D-04.7, D-05.5 | F-097, F-105, F-112, F-114 |
| `employees.update` | access | All | Access-control | Edit employee profile, role, service and property scope | D-04.7, D-05.5 | F-097, F-105, F-112, F-114 |
| `employees.deactivate` | access | All | Access-control | Suspend or remove an employee | D-04.7, D-05.5 | F-097, F-105, F-112, F-114 |
| `roles.view` | access | All | Normal | View roles and their permissions | D-04.7, D-04.8, D-05.5 | F-097, F-098, F-105, F-112 |
| `roles.manage` | access | All | Access-control | Create/edit/delete custom roles | D-04.8, D-05.5 | F-098, F-105, F-112 |
| `roles.assign` | access | All | Access-control | Assign roles to employees | D-04.7, D-05.5 | F-097, F-105, F-112 |
| `bookings.view` | bookings | Company + Hotel | Sensitive | View bookings and guest data | D-04.5, D-05.4, D-06.2 | F-095, F-104, F-109 |
| `bookings.create` | bookings | Company + Hotel | Sensitive | Create a booking on behalf of a customer | D-04.5, D-05.4 (not drawn) | F-095, F-104 |
| `bookings.update` | bookings | Company + Hotel | Sensitive | Modify booking details | D-04.5, D-05.4 | F-095, F-104 |
| `bookings.cancel` | bookings | Company + Hotel | Sensitive | Cancel a booking | D-04.5, D-05.4 | F-095, F-104 |
| `bookings.refund` | bookings | Company + Hotel | Financial | Initiate an eligible refund | D-04.5, D-05.4 | F-095, F-104 |
| `bookings.checkin` | bookings | Hotel | Normal | Check a guest in | D-05.4 | F-104 |
| `bookings.checkout` | bookings | Hotel | Normal | Check a guest out | D-05.4 | F-104 |
| `bookings.export` | bookings | Company + Hotel | Sensitive | Export booking lists | D-04.5, D-05.4 | F-095, F-104 |
| `tours.view` | tours | Company | Normal | View tours and programmes | D-04.3 | F-093 |
| `tours.create` | tours | Company | Sensitive | Create a tour | D-04.4 | F-094 |
| `tours.update` | tours | Company | Sensitive | Edit a tour | D-04.4 | F-094 |
| `tours.delete` | tours | Company | Sensitive | Delete/withdraw a tour | D-04.3 | F-093 |
| `tours.submit` | tours | Company | Sensitive | Submit a tour for platform review | D-04.4 | F-094 |
| `tours.slots.manage` | tours | Company | Sensitive | Manage departure slots and seat capacity | D-04.4 | F-094 |
| `visa_requests.view` | visas | Company | Sensitive | View applications attached to the company's own offerings; read-only, no decision | D-04.6 (view scope, DEC-16) | F-096, F-108 |
| `visa_requests.review` | visas | Company | Sensitive | Validate documents and flag missing items; no final decision (DEC-04) | D-04.6 | F-096, F-108 |
| `visa_offerings.view` | visas | Company | Normal | View the company's visa offerings | D-03.9 (company scope, D) | F-084 |
| `visa_offerings.manage` | visas | Company | Sensitive | Create/update own visa offerings and prices | D-03.9 (company scope, D) | F-084 |
| `properties.view` | properties | Hotel | Normal | View hotel properties of the organization | D-05.1, D-05.6 | F-101, F-106 |
| `properties.create` | properties | Hotel | Sensitive | Add a hotel property | D-05.6 (D) | F-106 |
| `properties.update` | properties | Hotel | Sensitive | Edit property details, address, location and documents | D-05.6 | F-106 |
| `properties.publish` | properties | Hotel | Sensitive | Publish or unpublish a hotel property | D-05.6 (D) | F-106 |
| `rooms.view` | rooms | Hotel | Normal | View room types and availability | D-05.3 | F-103 |
| `rooms.manage` | rooms | Hotel | Sensitive | Create/edit/delete room types | D-05.3 | F-103 |
| `rates.view` | rooms | Hotel | Normal | View the rate calendar | D-05.3 | F-103 |
| `rates.update` | rooms | Hotel | Sensitive | Update nightly prices and bulk adjustments | D-05.3 | F-103 |
| `rates.stop_sell` | rooms | Hotel | Sensitive | Apply or lift stop-sell | D-05.3 | F-103 |
| `coupons.view` | coupons | Company | Normal | View the company's coupons | — (D) | F-111 |
| `coupons.create` | coupons | Company | Sensitive | Create company-scoped coupons | — (D) | F-111 |
| `coupons.manage` | coupons | Company | Sensitive | Edit, limit and deactivate own coupons | — (D) | F-111 |
| `reports.view` | reports | All | Sensitive | View operational and financial reports | D-04.9, D-05.7 | F-099, F-107 |
| `reports.export` | reports | All | Sensitive | Export reports | D-04.9, D-05.7 | F-099, F-107 |
| `settlements.view` | settlements | All | Financial | View settlements and dues | D-04.9, D-03.14 | F-099 |
| `settlements.manage` | settlements | All | Financial | Request an on-demand payout under delegation rules | D-04.9 (D) | F-099 |
| `settlements.accounts.manage` | settlements | All | Financial | Manage payout accounts; owner-only, non-delegable | — (D) | F-099 |
| `status_history.view` | reports | Company | Normal | View business status-history records (booking, visa, refund, payout-request and tour-review status transitions). Does **not** authorise a general activity feed or user activity tracking | D-04.1 | F-091 |
| `customers.view` | customers | Company + Hotel | Sensitive | View customer contact for own bookings | D-04.5, D-05.4 | F-095, F-104 |

**Catalog totals: 37 platform + 48 organization = 85 permissions (proposed — pending approval).**

Former keys, retained here so no reader mistakes them for active permissions: `platform.audit.view` (removed — audit and activity logs are permanently excluded, `G0-DEC-01`), `platform.visa_requests.update_status` (removed — split into `review` + `decide`, `G0-DEC-04`), `org.profile.publish` (removed — superseded by `properties.publish`), `team_activity.view` (renamed `status_history.view`).

---

## 4. Role → permission assignment

### 4.1 Platform roles

| Permission | R-03 Super admin | R-04 Finance | R-05 Content | R-06 Support | R-18 Visa officer |
|---|---|---|---|---|---|
| `platform.overview.view` | ● | ● | ● | ● | ● |
| `platform.notifications.view` | ● | ● | ● | ● | ● |
| `platform.notifications.manage` | ● | ● | ● | ● | ● |
| `platform.bookings.view` | ● | ● | ○ | ● | ○ |
| `platform.bookings.export` | ● | ● | ○ | ◐ | ○ |
| `platform.partners.view` | ● | ● | ○ | ● | ○ |
| `platform.partners.create` | ● | ○ | ○ | ○ | ○ |
| `platform.partners.update` | ● | ○ | ○ | ○ | ○ |
| `platform.partners.suspend` | ● | ○ | ○ | ○ | ○ |
| `platform.partner_requests.view` | ● | ○ | ○ | ◐ | ○ |
| `platform.partner_requests.review` | ● | ○ | ○ | ○ | ○ |
| `platform.visa_catalog.view` | ● | ○ | ● | ● | ● |
| `platform.visa_catalog.manage` | ● | ○ | ● | ○ | ○ |
| `platform.visa_requests.view` | ● | ○ | ● | ● | ● |
| `platform.visa_requests.review` | ● | ○ | ○ | ○ | ● |
| `platform.visa_requests.assign` | ● | ○ | ○ | ○ | ● |
| `platform.visa_requests.decide` | ● | ○ | ○ | ○ | ● |
| `platform.esim_catalog.view` | ● | ● | ● | ○ | ○ |
| `platform.esim_catalog.manage` | ● | ○ | ● | ○ | ○ |
| `platform.esim_catalog.sync` | ● | ○ | ● | ○ | ○ |
| `platform.customers.view` | ● | ● | ○ | ● | ● |
| `platform.customers.update` | ● | ○ | ○ | ○ | ○ |
| `platform.customers.block` | ● | ○ | ○ | ○ | ○ |
| `platform.customers.export` | ● | ○ | ○ | ◐ | ○ |
| `platform.deletion_requests.view` | ● | ○ | ○ | ● | ○ |
| `platform.deletion_requests.process` | ● | ○ | ○ | ● | ○ |
| `platform.finance.view` | ● | ● | ○ | ○ | ○ |
| `platform.finance.settlements.execute` | ● | ● | ○ | ○ | ○ |
| `platform.finance.export` | ● | ● | ○ | ○ | ○ |
| `platform.settings.manage` | ● | ○ | ○ | ○ | ○ |
| `platform.roles.view` | ● | ○ | ○ | ○ | ○ |
| `platform.roles.manage` | ● | ○ | ○ | ○ | ○ |
| `platform.roles.assign` | ● | ○ | ○ | ○ | ○ |
| `platform.coupons.view` | ● | ○ | ● | ○ | ○ |
| `platform.coupons.manage` | ● | ○ | ● | ○ | ○ |
| `platform.organization_access.view` | ● | ○ | ○ | ○ | ○ |
| `platform.organization_access.manage` | ● | ○ | ○ | ○ | ○ |

### 4.2 Company roles

| Permission | R-07 Owner | R-08 Bookings | R-09 Visa offerings | R-10 Accountant | R-11 Content | R-16 Restricted | R-17 Service owner |
|---|---|---|---|---|---|---|---|
| `org.profile.view` | ● | ● | ● | ● | ● | ● | ● |
| `org.profile.update` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `org.documents.manage` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `org.notifications.view` | ● | ● | ● | ● | ● | ● | ● |
| `employees.view` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `employees.create` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `employees.update` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `employees.deactivate` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `roles.view` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `roles.manage` | ● | ○ | ○ | ○ | ○ | ○ | S (≤ own scope) |
| `roles.assign` | ● | ○ | ○ | ○ | ○ | ○ | S (≤ own scope) |
| `bookings.view` | ● | ● | ○ | ◐ (financial) | ○ | A | S |
| `bookings.create` | ● | ● | ○ | ○ | ○ | ○ | ○ |
| `bookings.update` | ● | ● | ○ | ○ | ○ | ○ | S |
| `bookings.cancel` | ● | ● | ○ | ○ | ○ | ○ | S |
| `bookings.refund` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `bookings.export` | ● | ● | ○ | ◐ | ○ | ○ | S |
| `tours.view` | ● | ● | ○ | ○ | ● | ○ | S |
| `tours.create` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.update` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.delete` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.submit` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.slots.manage` | ● | ○ | ○ | ○ | ● | ○ | S |
| `visa_requests.view` | ● | ○ | ● | ○ | ○ | A | S |
| `visa_requests.review` | ● | ○ | ● | ○ | ○ | A | S |
| `visa_offerings.view` | ● | ○ | ● | ○ | ● | ○ | S |
| `visa_offerings.manage` | ● | ○ | ● | ○ | ○ | ○ | S |
| `coupons.view` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `coupons.create` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `coupons.manage` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `reports.view` | ● | ○ | ○ | ● | ○ | ○ | S |
| `reports.export` | ● | ○ | ○ | ● | ○ | ○ | S |
| `settlements.view` | ● | ○ | ○ | ● | ○ | ○ | S |
| `settlements.manage` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `settlements.accounts.manage` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `status_history.view` | ● | ○ | ○ | ○ | ○ | ◐ | S |
| `customers.view` | ● | ● | ● | ○ | ○ | A | S |

### 4.3 Hotel roles

| Permission | R-12 Owner | R-13 Reception | R-14 Bookings mgr | R-15 Accountant |
|---|---|---|---|---|
| `org.profile.view` | ● | ● | ● | ● |
| `org.profile.update` | ● | ○ | ○ | ○ |
| `org.documents.manage` | ● | ○ | ○ | ○ |
| `org.notifications.view` | ● | ● | ● | ● |
| `employees.view` | ● | ○ | ○ | ○ |
| `employees.create` | ● | ○ | ○ | ○ |
| `employees.update` | ● | ○ | ○ | ○ |
| `employees.deactivate` | ● | ○ | ○ | ○ |
| `roles.view` | ● | ○ | ○ | ○ |
| `roles.manage` | ● | ○ | ○ | ○ |
| `roles.assign` | ● | ○ | ○ | ○ |
| `bookings.view` | ● | ● | ● | ◐ |
| `bookings.create` | ● | ○ | ● | ○ |
| `bookings.update` | ● | ◐ | ● | ○ |
| `bookings.cancel` | ● | ○ | ● | ○ |
| `bookings.refund` | ● | ○ | ○ | ○ |
| `bookings.checkin` | ● | ● | ● | ○ |
| `bookings.checkout` | ● | ● | ● | ○ |
| `bookings.export` | ● | ○ | ● | ● |
| `properties.view` | ● | ◐ | ● | ● |
| `properties.create` | ● | ○ | ○ | ○ |
| `properties.update` | ● | ○ | ○ | ○ |
| `properties.publish` | ● | ○ | ○ | ○ |
| `rooms.view` | ● | ● | ● | ○ |
| `rooms.manage` | ● | ○ | ● | ○ |
| `rates.view` | ● | ● | ● | ○ |
| `rates.update` | ● | ○ | ● | ○ |
| `rates.stop_sell` | ● | ○ | ● | ○ |
| `reports.view` | ● | ○ | ◐ | ● |
| `reports.export` | ● | ○ | ● | ● |
| `settlements.view` | ● | ○ | ○ | ● |
| `settlements.manage` | ● | ○ | ○ | ○ |
| `settlements.accounts.manage` | ● | ○ | ○ | ○ |
| `status_history.view` | ● | ○ | ○ | ○ |
| `customers.view` | ● | ● | ● | ○ |

### 4.4 Service provider role

P-09 recommendation: Service Providers do not receive booking or customer permissions until their service catalogue, offering model, and booking lifecycle are explicitly defined.

| Permission | R-19 Provider owner |
|---|---|
| `org.profile.view` | ● |
| `org.profile.update` | ● |
| `org.documents.manage` | ● |
| `org.notifications.view` | ● |
| `employees.view` | ● |
| `employees.create` | ● |
| `employees.update` | ● |
| `employees.deactivate` | ● |
| `roles.view` | ● |
| `roles.manage` | ● |
| `roles.assign` | ● |
| `reports.view` | ● |
| `reports.export` | ● |
| `settlements.view` | ● |
| `settlements.manage` | ● |
| `settlements.accounts.manage` | ● |

The R-19 role receives no `bookings.*` or `customers.view` permissions.

This is the proposed assignment pending explicit P-09 approval. It does not authorize access to customer bookings through another permission or an unscoped endpoint.

### 4.4 Assignment summary

| Role | Explicit keys | Notes |
|---|---|---|
| R-03 | 37 | All platform permissions |
| R-04 | 12 | Financial + read operations |
| R-05 | 10 | Catalogues, coupons, notifications |
| R-06 | 11 | Read/assist + deletion processing (includes one partial export) |
| R-07 | 37 | All company-applicable organization permissions |
| R-08 | 8 | Bookings + customer contact |
| R-09 | 7 | Visa queue + offerings view |
| R-10 | 7 | Reports/settlements read + export |
| R-11 | 8 | Tours + visa offerings view |
| R-12 | 30 | All hotel-applicable permissions |
| R-13 | 9 | Arrivals, check-in/out, room and rate view |
| R-14 | 18 | Bookings, rooms, rates, reports |
| R-15 | 7 | Reports/settlements read |
| R-16 | 7 | Assigned visas, read-only bookings |
| R-17 | 34 company / 26 hotel | Employees/roles and the operational keys of the service scope |

R-01/R-02 have no catalog keys.

---

## 5. Scope matrix

| Role | Organization scope | Service scope | Assignment filter | Cross-tenant | Grant ceiling |
|---|---|---|---|---|---|
| R-01 Traveller | Self | n/a | Own rows only | None | None |
| R-02 Guest | Public catalogue | n/a | n/a | None | None |
| R-03 Super admin | Platform (all tenants) | All | None | **Yes**, through explicit platform permissions; no audit log | All platform permissions; org assignments via `platform.organization_access.manage` |
| R-04 Finance | Platform | Finance | None | Yes (financial data only) | None |
| R-05 Content | Platform | Catalogue, coupons | None | Yes (catalogue/coupon data) | None |
| R-06 Support | Platform | Customers, bookings | None | Yes (read/assist) | None |
| R-07 Company owner | Own company | All company services | None | None | All org permissions they hold |
| R-08 Bookings employee | Own company | Bookings (flights, hotels, tours) | None | None | None |
| R-09 Visa employee | Own company | Visas | None | None | None |
| R-10 Accountant | Own company | Finance | None | None | None |
| R-11 Content manager | Own company | Tours | None | None | None |
| R-12 Hotel owner | Own hotel | All hotel services | None | None | All hotel-applicable permissions |
| R-13 Reception | Own hotel | Arrivals/stays | None | None | None |
| R-14 Hotel bookings manager | Own hotel | Bookings, rooms, rates | None | None | None |
| R-15 Hotel accountant | Own hotel | Finance | None | None | None |
| R-16 Restricted employee | Own company | Visas | **Assigned only** | None | None |
| R-17 Service owner | Own organization | Selected services | None | None | Subset of own effective set, inside service scope |

### 5.1 Scope composition rules

1. **Organization scope is mandatory for every organization permission.** There is no "global org permission".
2. **Service scope can only narrow.** A membership with no service scopes and a non-owner role has no service access.
3. **Assignment filter is applied last.** An assigned-only member still needs the permission and the correct organization.
4. **Platform scope never combines with organization scope in one role.** A user who is both staff and partner owner holds two memberships and chooses a context at login.
5. **Service scope changes take effect immediately.** The current membership and scope state is authoritative; no general audit event or user-activity history is recorded.

---

## 6. Data visibility matrix

Legend: ● full within role scope · ◐ subset/masked · ○ none · **A** assigned only · **P** requires a specific platform permission.

| Data class | Traveller | Guest | Platform roles | Company owner | Company employee | Hotel owner | Hotel employee | Service owner | Restricted employee |
|---|---|---|---|---|---|---|---|---|---|
| Own profile | ● | ○ | ○ | ● (own) | ● (own) | ● (own) | ● (own) | ● (own) | ● (own) |
| Own bookings | ● | ○ | ○ | ● | ● | ● | ● | ● | A |
| All company bookings | ○ | ○ | P | ● | ◐ (role-dependent) | ○ | ○ | S | ○ |
| All hotel bookings | ○ | ○ | P | ○ | ○ | ● | ◐ (role-dependent) | S | ○ |
| Visa applications | ○ (own only) | ○ | P | ● | ◐ (visa roles) | ○ | ○ | S | A |
| Tours | Public catalogue | Public | P | ● | ◐ (content) | ○ | ○ | S | ○ |
| Rooms & rates | Public availability | Public | P | ○ | ○ | ● | ◐ | S | ○ |
| Employees & roles | ○ | ○ | P | ● | ○ | ● | ○ | S | ○ |
| Customer PII | Own | ○ | P | ◐ (own bookings) | ◐ (own bookings) | ◐ (own bookings) | ◐ (own bookings) | ◐ (own bookings) | A |
| Payments & refunds | Own | ○ | P | ● | ○ | ● | ○ | S (financial) | ○ |
| Settlements | ○ | ○ | P | ● | ○ | ● | ○ | S (financial) | ○ |
| Audit log | ○ | ○ | P | ◐ (own organization) | ○ | ◐ (own organization) | ○ | ○ | ○ |

**Rule:** after customer anonymisation (DEC-09), every partner-facing cell that would show personal data becomes a masked reference. Platform roles may access tenant data only through explicit platform permissions and the applicable legal/financial scope. No audit log or user-activity history is created.

---

## 7. Delegation and grant matrix

| Grantor | Platform roles | Organization roles | Direct permissions | Service scopes | Ownership transfer |
|---|---|---|---|---|---|
| R-03 Super admin | ● create/modify/assign | ● via `platform.organization_access.manage` | ● across tenants, through explicit platform permissions; no audit record | ● | ● |
| R-04 Finance | ○ | ○ | ○ | ○ | ○ |
| R-05 Content | ○ | ○ | ○ | ○ | ○ |
| R-06 Support | ○ | ○ | ○ | ○ | ○ |
| R-07 Company owner | ○ | ● within own company, ≤ own ceiling | ● ≤ own ceiling | ● within own company | ● to an active member |
| R-08–R-11 employees | ○ | Only if granted `roles.manage`/`roles.assign` | Only if granted the corresponding access permission | ○ | ○ |
| R-12 Hotel owner | ○ | ● within own hotel, ≤ own ceiling | ● ≤ own ceiling | ● hotel services only | ● to an active member |
| R-13–R-15 hotel employees | ○ | Only if granted `roles.manage`/`roles.assign` | Same rule | ○ | ○ |
| R-16 Restricted employee | ○ | ○ | ○ | ○ | ○ |
| R-17 Service owner | ○ | ● within own service scope, ≤ own ceiling | ● within service scope | ● subset of own | ○ |

### 7.1 Delegation invariants

1. A grantor cannot grant a key they do not hold (`PE-01`).
2. A grantor cannot grant a scope broader than their own (`PE-02`).
3. Platform and organization families never mix (`PE-03`).
4. No one grants the owner role; ownership is transferred, not assigned (`PE-05`).
5. A delegated `roles.manage` holder can create roles only from the keys they hold; they cannot edit the owner role or system roles.
6. Service owners can manage employees only inside their service scope; the employee's membership must be restricted to the same or a narrower service set.
7. Direct grants are recorded with the grantor and the ceiling at grant time.

---

## 8. Lifecycle effects matrix

| Event | Session effect | Permission effect | Data effect | Operational record |
|---|---|---|---|---|
| Invitation created | None (cannot log in) | None | Invitation record created | Current invitation state |
| Invitation accepted | New session allowed | Role becomes effective | Member becomes active | Current membership state |
| Role changed | Continues | Immediate (next request) | Current role assignment updated | Current role assignment only |
| Direct grant added/removed | Continues | Immediate | Direct grant state updated | Grant record with grantor attribution |
| Service scope changed | Continues | Immediate narrowing/widening | Accessible records change | Current membership scope |
| Suspended | All sessions for that membership revoked | None effective | Records retained; assignment flagged | Current membership status |
| Removed | Sessions revoked | None | Membership becomes inactive/removed | Retained domain references where required |
| Moved to another organization | Old sessions revoked | Roles reset | New membership, no carried access | Separate old and new membership records |
| Ownership transferred | Both users' sessions refreshed | Old owner loses owner role | Exactly one owner remains | Current ownership and membership state |
| Role deleted | Affected members continue with remaining roles | Requires zero members first | Reassignment enforced | Current role and assignment state |
| Permission deprecated | None | Old key remains valid until migration | Catalog/migration state updated | Current catalog state |

These are operational domain records, not an audit log. The system does not retain a general before/after event history of administrative or user actions.
---

## 9. Coverage and validation

| # | Check | Result |
|---|---|---|
| 1 | Permission keys defined | 85 (37 platform, 48 organization) — proposed |
| 2 | Keys derived from screens/features | 73 by screen/feature; 12 decision-backed (`platform.settings.manage`, `platform.coupons.*`, `platform.organization_access.*`, `platform.visa_requests.decide`, `visa_offerings.view`, `properties.create`, `properties.publish`, `settlements.manage`, `settlements.accounts.manage`) |
| 3 | Roles using the catalog | 17 (15 RBAC roles; R-01/R-02 ownership-based) |
| 4 | Platform/organization family mixing | 0 roles |
| 5 | Every organization permission declares organization-type applicability | 48/48 |
| 6 | Owner roles contain all applicable keys | Company 37/37; hotel 34/34 |
| 7 | Roles with zero permissions | R-01, R-02 (intentional) |
| 8 | Service scope defined per role | 17/17 |
| 9 | Delegation ceiling defined | 17/17 |
| 10 | Screens with no owning permission | 0 (each screen maps to a feature; each mutating operation maps to a key) |
| 11 | Permission keys with no feature | 0 |
| 12 | Wildcard or umbrella permissions | 0 |

### 9.1 Remaining ambiguity

* **R-17 Service owner** is modelled as an organization-level, service-scoped manager because that is the only interpretation that fits "manage employees and access within their own service scope" alongside company/hotel owners. **Q-45 is answered by the authorization requirement.** If a platform-level service-line owner is later required, it is an additive platform role family.
* **`bookings.create`** has no drawn screen. It exists because the operation is required by the permission model and is a standard partner capability; if partner-created bookings are out of scope, the key can be dropped without affecting anything else.
* **Hotel property scope is modelled** — `G0-DEC-03` confirms a hotel organization owns many properties, `hotels` is the property entity and `membership_hotel_scopes` scopes staff (Q-49 answered).
* **Service-provider scope is narrow by design** — `bookings.*` and `customers.view` stay closed to service providers until a provider service catalogue exists (P-09, pending approval). The `All` org type in §3.2 therefore means company plus hotel for those keys.
* **`status_history.view`** (formerly `team_activity.view`) reads business status transitions only. No audit log, activity log or user activity tracking exists or is planned (`G0-DEC-01`).



## 10. Multi-service provider matrix — authoritative update (2026-11-24)

R-19 (`service_provider_owner`) represents an organization that may have **multiple enabled service lines**, not a single provider category. Its organization-wide scope means all services enabled for that organization; it does not mean all platform services. Employees may have multiple explicit service scopes, each intersected with the applicable resource/property scope. Empty service/resource scope grants nothing.

The old `Company + Hotel` restriction and the old blanket P-09 exclusion are not valid for multi-service provider organizations. However, this does **not** mean every organization permission becomes available to every provider. Each permission must declare the service(s) it operates on and the allowed organization capability; actual access is filtered by the organization's enabled services and the member's service/resource scopes.

Use [`MULTI-SERVICE-PROVIDER-AUTHORIZATION.md`](./MULTI-SERVICE-PROVIDER-AUTHORIZATION.md) as the controlling service/resource policy. The 85-key count and exact org-type/service eligibility are now **under recalculation**, not approved. Do not use the old count as a Gate 0A final catalog or seed target.

Flights remain platform-only. Visa request review is provider/company-scoped where the organization has Visas enabled, but final approve/reject remains platform-only.
