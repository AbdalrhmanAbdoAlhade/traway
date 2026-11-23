# Phase 2 — Role, Permission & Scope Matrix

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Complete — awaiting approval (`APPROVE PHASE 3`)
**Companion:** [`AUTHORIZATION-ARCHITECTURE.md`](./AUTHORIZATION-ARCHITECTURE.md)
**Date:** 2026-11-23

---

## 1. How to read this document

| Symbol | Meaning |
|---|---|
| ● | Full capability within the role's scope |
| ◐ | Partial — only the listed subset |
| ○ | No access |
| **S** | Conditional on service scope (membership must include the service) |
| **A** | Assigned records only |
| **D** | Decision-backed capability with no drawn screen (DEC-03 / DEC-06 / authorization requirement) |

Two families are kept separate throughout:

* **Platform permissions** (`platform.*`) apply across tenants and are held only by platform-role members.
* **Organization permissions** (all other keys) apply inside one company or hotel and are held only by members of that organization.

---

## 2. Role summary (17 roles)

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
| R-09 | Visa employee | Organization | Company | Service-scoped: visas |
| R-10 | Accountant | Organization | Company | Organization-wide (read finance) |
| R-11 | Content manager | Organization | Company | Service-scoped: tours |
| R-12 | Hotel owner | Organization | Hotel | Organization-wide |
| R-13 | Reception | Organization | Hotel | Operational, arrivals |
| R-14 | Hotel bookings manager | Organization | Hotel | Bookings + rooms/rates |
| R-15 | Hotel accountant | Organization | Hotel | Read financial |
| R-16 | Restricted employee | Organization | Company | Assigned-only + service scope |
| R-17 | Service owner | Organization | Company or hotel | Service-scoped management |

**Customers (R-01/R-02) do not use this catalog.** Their access is enforced by ownership policies (a traveller may only read rows whose `user_id` is their own) and by public endpoint allow-lists. Assigning RBAC permissions to customers would be both unnecessary and risky.

---

## 3. Permission catalog (76 keys)

### 3.1 Platform family (31)

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
| `platform.roles.view` | access | Normal | View platform roles and permissions | D-03.15 | F-090, F-112 |
| `platform.roles.manage` | access | Access-control | Create, edit and delete platform roles | D-03.15 | F-090, F-112 |
| `platform.roles.assign` | access | Access-control | Assign platform roles to staff | D-03.15 | F-090, F-112 |
| `platform.coupons.view` | coupons | Normal | View platform-wide coupons | — (D) | F-111 |
| `platform.coupons.manage` | coupons | Sensitive | Create, edit, deactivate platform coupons | — (D) | F-111 |
| `platform.organization_access.view` | access | Sensitive | View memberships, roles and grants of any organization | — (D) | F-112 |
| `platform.organization_access.manage` | access | Access-control | Assign roles and permissions to owners and employees of any organization | — (D) | F-112 |
| `platform.audit.view` | audit | Access-control | Read the audit log | — (D) | F-112 |

### 3.2 Organization family (45)

| Key | Domain | Org type | Sensitivity | Description | Screens | Features |
|---|---|---|---|---|---|---|
| `org.profile.view` | profile | Both | Normal | View organization profile and settings | D-04.10, D-05.6 | F-100, F-106 |
| `org.profile.update` | profile | Both | Sensitive | Edit organization profile and settings | D-04.10, D-05.6 | F-100, F-106 |
| `org.profile.publish` | profile | Hotel | Sensitive | Publish the hotel profile to customers | D-05.6 | F-106 |
| `org.documents.manage` | profile | Both | Sensitive | Upload/replace legal documents | D-04.10, D-05.6 | F-100, F-106 |
| `org.notifications.view` | notifications | Both | Normal | View organization notification inbox | D-04.2, D-05.2, D-06.3 | F-092, F-102, F-110 |
| `employees.view` | access | Both | Normal | View employees and their roles | D-04.7, D-05.5 | F-097, F-105, F-112 |
| `employees.create` | access | Both | Access-control | Invite an employee | D-04.7, D-05.5 | F-097, F-105, F-112 |
| `employees.update` | access | Both | Access-control | Edit employee profile, role, service scope | D-04.7, D-05.5 | F-097, F-105, F-112 |
| `employees.deactivate` | access | Both | Access-control | Suspend or remove an employee | D-04.7, D-05.5 | F-097, F-105, F-112 |
| `roles.view` | access | Both | Normal | View roles and their permissions | D-04.7, D-04.8, D-05.5 | F-097, F-098, F-105, F-112 |
| `roles.manage` | access | Both | Access-control | Create/edit/delete custom roles | D-04.8, D-05.5 | F-098, F-105, F-112 |
| `roles.assign` | access | Both | Access-control | Assign roles to employees | D-04.7, D-05.5 | F-097, F-105, F-112 |
| `bookings.view` | bookings | Both | Sensitive | View bookings and guest data | D-04.5, D-05.4, D-06.2 | F-095, F-104, F-109 |
| `bookings.create` | bookings | Both | Sensitive | Create a booking on behalf of a customer | D-04.5, D-05.4 (not drawn) | F-095, F-104 |
| `bookings.update` | bookings | Both | Sensitive | Modify booking details | D-04.5, D-05.4 | F-095, F-104 |
| `bookings.cancel` | bookings | Both | Sensitive | Cancel a booking | D-04.5, D-05.4 | F-095, F-104 |
| `bookings.refund` | bookings | Both | Financial | Initiate an eligible refund | D-04.5, D-05.4 | F-095, F-104 |
| `bookings.checkin` | bookings | Hotel | Normal | Check a guest in | D-05.4 | F-104 |
| `bookings.checkout` | bookings | Hotel | Normal | Check a guest out | D-05.4 | F-104 |
| `bookings.export` | bookings | Both | Sensitive | Export booking lists | D-04.5, D-05.4 | F-095, F-104 |
| `tours.view` | tours | Company | Normal | View tours and programmes | D-04.3 | F-093 |
| `tours.create` | tours | Company | Sensitive | Create a tour | D-04.4 | F-094 |
| `tours.update` | tours | Company | Sensitive | Edit a tour | D-04.4 | F-094 |
| `tours.delete` | tours | Company | Sensitive | Delete/withdraw a tour | D-04.3 | F-093 |
| `tours.submit` | tours | Company | Sensitive | Submit a tour for platform review | D-04.4 | F-094 |
| `tours.slots.manage` | tours | Company | Sensitive | Manage departure slots and seat capacity | D-04.4 | F-094 |
| `visa_requests.view` | visas | Company | Sensitive | View visa applications and documents | D-04.6, D-06.1 | F-096, F-108 |
| `visa_requests.review` | visas | Company | Sensitive | Accept/reject documents | D-04.6, D-06.1 | F-096, F-108 |
| `visa_requests.update_status` | visas | Company | Sensitive | Record customer-visible status and decisions | D-04.6, D-06.1 | F-096, F-108 |
| `visa_offerings.view` | visas | Company | Normal | View the company's visa offerings | D-03.9 (company scope, D) | F-084 |
| `visa_offerings.manage` | visas | Company | Sensitive | Create/update own visa offerings and prices | D-03.9 (company scope, D) | F-084 |
| `rooms.view` | rooms | Hotel | Normal | View room types and availability | D-05.3 | F-103 |
| `rooms.manage` | rooms | Hotel | Sensitive | Create/edit/delete room types | D-05.3 | F-103 |
| `rates.view` | rooms | Hotel | Normal | View the rate calendar | D-05.3 | F-103 |
| `rates.update` | rooms | Hotel | Sensitive | Update nightly prices and bulk adjustments | D-05.3 | F-103 |
| `rates.stop_sell` | rooms | Hotel | Sensitive | Apply or lift stop-sell | D-05.3 | F-103 |
| `coupons.view` | coupons | Company | Normal | View the company's coupons | — (D) | F-111 |
| `coupons.create` | coupons | Company | Sensitive | Create company-scoped coupons | — (D) | F-111 |
| `coupons.manage` | coupons | Company | Sensitive | Edit, limit and deactivate own coupons | — (D) | F-111 |
| `reports.view` | reports | Both | Sensitive | View operational and financial reports | D-04.9, D-05.7 | F-099, F-107 |
| `reports.export` | reports | Both | Sensitive | Export reports | D-04.9, D-05.7 | F-099, F-107 |
| `settlements.view` | settlements | Both | Financial | View settlements and dues | D-04.9, D-03.14 | F-099 |
| `settlements.manage` | settlements | Both | Financial | Request an on-demand payout | D-04.9 (D) | F-099 |
| `team_activity.view` | reports | Company | Normal | View the team activity feed | D-04.1 | F-091 |
| `customers.view` | customers | Both | Sensitive | View customer contact for own bookings | D-04.5, D-05.4 | F-095, F-104 |

**Catalog totals: 33 platform + 45 organization = 78 permissions.**

---

## 4. Role → permission assignment

### 4.1 Platform roles

| Permission | R-03 Super admin | R-04 Finance | R-05 Content | R-06 Support |
|---|---|---|---|---|
| `platform.overview.view` | ● | ● | ● | ● |
| `platform.notifications.view` | ● | ● | ● | ● |
| `platform.notifications.manage` | ● | ● | ● | ● |
| `platform.bookings.view` | ● | ◐ (financial columns) | ○ | ● |
| `platform.bookings.export` | ● | ● | ○ | ○ |
| `platform.partners.view` | ● | ● | ○ | ● |
| `platform.partners.create` | ● | ○ | ○ | ○ |
| `platform.partners.update` | ● | ○ | ○ | ○ |
| `platform.partners.suspend` | ● | ○ | ○ | ○ |
| `platform.partner_requests.view` | ● | ○ | ○ | ◐ (read) |
| `platform.partner_requests.review` | ● | ○ | ○ | ○ |
| `platform.visa_catalog.view` | ● | ○ | ● | ● |
| `platform.visa_catalog.manage` | ● | ○ | ● | ○ |
| `platform.esim_catalog.view` | ● | ● (margins) | ● | ○ |
| `platform.esim_catalog.manage` | ● | ○ | ● | ○ |
| `platform.esim_catalog.sync` | ● | ○ | ● | ○ |
| `platform.customers.view` | ● | ● (payment context) | ○ | ● |
| `platform.customers.update` | ● | ○ | ○ | ○ |
| `platform.customers.block` | ● | ○ | ○ | ○ |
| `platform.customers.export` | ● | ○ | ○ | ◐ (approved cases) |
| `platform.deletion_requests.view` | ● | ○ | ○ | ● |
| `platform.deletion_requests.process` | ● | ○ | ○ | ● (audited) |
| `platform.finance.view` | ● | ● | ○ | ○ |
| `platform.finance.settlements.execute` | ● | ● | ○ | ○ |
| `platform.finance.export` | ● | ● | ○ | ○ |
| `platform.roles.view` | ● | ○ | ○ | ○ |
| `platform.roles.manage` | ● | ○ | ○ | ○ |
| `platform.roles.assign` | ● | ○ | ○ | ○ |
| `platform.coupons.view` | ● | ○ | ● | ○ |
| `platform.coupons.manage` | ● | ○ | ● | ○ |
| `platform.organization_access.view` | ● | ○ | ○ | ○ |
| `platform.organization_access.manage` | ● | ○ | ○ | ○ |
| `platform.audit.view` | ● | ● | ○ | ○ |

### 4.2 Company roles

| Permission | R-07 Owner | R-08 Bookings | R-09 Visa | R-10 Accountant | R-11 Content | R-16 Restricted | R-17 Service owner |
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
| `bookings.create` | ● | ● | ○ | ○ | ○ | ○ | S |
| `bookings.update` | ● | ● | ○ | ○ | ○ | ○ | S |
| `bookings.cancel` | ● | ● | ○ | ○ | ○ | ○ | S |
| `bookings.refund` | ● | ○ | ○ | ○ | ○ | ○ | S (financial) |
| `bookings.export` | ● | ● | ○ | ◐ | ○ | ○ | S |
| `customers.view` | ● | ● | ● | ○ | ○ | A | S |
| `tours.view` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.create` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.update` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.delete` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.submit` | ● | ○ | ○ | ○ | ● | ○ | S |
| `tours.slots.manage` | ● | ○ | ○ | ○ | ● | ○ | S |
| `visa_requests.view` | ● | ○ | ● | ○ | ○ | A | S |
| `visa_requests.review` | ● | ○ | ● | ○ | ○ | A | S |
| `visa_requests.update_status` | ● | ○ | ● | ○ | ○ | A | S |
| `visa_offerings.view` | ● | ○ | ● | ○ | ○ | ○ | S |
| `visa_offerings.manage` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `coupons.view` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `coupons.create` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `coupons.manage` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `reports.view` | ● | ○ | ○ | ● | ○ | ○ | S |
| `reports.export` | ● | ○ | ○ | ● | ○ | ○ | S |
| `settlements.view` | ● | ○ | ○ | ● | ○ | ○ | S |
| `settlements.manage` | ● | ○ | ○ | ○ | ○ | ○ | ○ |
| `team_activity.view` | ● | ○ | ○ | ○ | ○ | ○ | S |
| `customers.view` | ● | ● | ● | ○ | ○ | A | S |

### 4.3 Hotel roles

| Permission | R-12 Owner | R-13 Reception | R-14 Bookings mgr | R-15 Accountant | R-17 Service owner (hotel) |
|---|---|---|---|---|---|
| `org.profile.view` | ● | ● | ● | ● | ● |
| `org.profile.update` | ● | ○ | ○ | ○ | ○ |
| `org.profile.publish` | ● | ○ | ○ | ○ | ○ |
| `org.documents.manage` | ● | ○ | ○ | ○ | ○ |
| `org.notifications.view` | ● | ● | ● | ● | ● |
| `employees.view` | ● | ○ | ○ | ○ | S |
| `employees.create` | ● | ○ | ○ | ○ | S |
| `employees.update` | ● | ○ | ○ | ○ | S |
| `employees.deactivate` | ● | ○ | ○ | ○ | S |
| `roles.view` | ● | ○ | ○ | ○ | S |
| `roles.manage` | ● | ○ | ○ | ○ | S (≤ own scope) |
| `roles.assign` | ● | ○ | ○ | ○ | S (≤ own scope) |
| `bookings.view` | ● | ● | ● | ◐ | S |
| `bookings.create` | ● | ○ | ● | ○ | S |
| `bookings.update` | ● | ● | ● | ○ | S |
| `bookings.cancel` | ● | ○ | ● | ○ | S |
| `bookings.refund` | ● | ○ | ● | ○ | S |
| `bookings.checkin` | ● | ● | ● | ○ | S |
| `bookings.checkout` | ● | ● | ● | ○ | S |
| `bookings.export` | ● | ○ | ● | ◐ | S |
| `rooms.view` | ● | ● | ● | ○ | S |
| `rooms.manage` | ● | ○ | ● | ○ | S |
| `rates.view` | ● | ● | ● | ○ | S |
| `rates.update` | ● | ○ | ● | ○ | S |
| `rates.stop_sell` | ● | ○ | ● | ○ | S |
| `reports.view` | ● | ○ | ◐ | ● | S |
| `reports.export` | ● | ○ | ◐ | ● | S |
| `settlements.view` | ● | ○ | ○ | ● | S |
| `settlements.manage` | ● | ○ | ○ | ○ | ○ |
| `customers.view` | ● | ● | ● | ○ | S |

### 4.4 Assignment summary

| Role | Explicit keys | Notes |
|---|---|---|
| R-03 | 33 | All platform permissions |
| R-04 | 12 | Financial + read operations + audit |
| R-05 | 10 | Catalogues, coupons, notifications |
| R-06 | 9 | Read/assist + deletion processing (plus one partial export) |
| R-07 | 37 | All company-applicable organization permissions |
| R-08 | 8 | Bookings + customer contact |
| R-09 | 7 | Visa queue + offerings view |
| R-10 | 6 | Reports/settlements read + export |
| R-11 | 9 | Tours + visa offerings view |
| R-12 | 30 | All hotel-applicable permissions |
| R-13 | 9 | Arrivals, check-in/out, room and rate view |
| R-14 | 18 | Bookings, rooms, rates, reports |
| R-15 | 6 | Reports/settlements read |
| R-16 | 6 | Assigned visas, read-only bookings |
| R-17 | base 14 + service bundle | Employees/roles scoped to service, plus the service's operational keys |

R-01/R-02 have no catalog keys.

---

## 5. Scope matrix

| Role | Organization scope | Service scope | Assignment filter | Cross-tenant | Grant ceiling |
|---|---|---|---|---|---|
| R-01 Traveller | Self | n/a | Own rows only | None | None |
| R-02 Guest | Public catalogue | n/a | n/a | None | None |
| R-03 Super admin | Platform (all tenants) | All | None | **Yes**, audited | All platform permissions; org assignments via `platform.organization_access.manage` |
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
5. **Service scope changes take effect immediately** and are audited.

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

**Rule:** after customer anonymisation (DEC-09), every partner-facing cell that would show personal data becomes a masked reference; platform roles retain access only through an audited permission and only where a legal/financial obligation exists.

---

## 7. Delegation and grant matrix

| Grantor | Platform roles | Organization roles | Direct permissions | Service scopes | Ownership transfer |
|---|---|---|---|---|---|
| R-03 Super admin | ● create/modify/assign | ● via `platform.organization_access.manage` | ● across tenants, audited | ● | ● |
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

| Event | Session effect | Permission effect | Data effect | Audit |
|---|---|---|---|---|
| Invitation created | None (cannot log in) | None | Member listed as invited | Yes |
| Invitation accepted | New session allowed | Role becomes effective | Member active | Yes |
| Role changed | Continues | Immediate (next request) | None | Yes, before/after |
| Direct grant added/removed | Continues | Immediate | None | Yes |
| Service scope changed | Continues | Immediate narrowing/widening | Accessible records change | Yes |
| Suspended | All sessions for that membership revoked | None effective | Records retained; assignment flagged | Yes |
| Removed | Sessions revoked | None | Historical attribution retained | Yes |
| Moved to another organization | Old sessions revoked | Roles reset | New membership, no carried access | Yes |
| Ownership transferred | Both users' sessions refreshed | Old owner loses owner role | Exactly one owner remains | Yes |
| Role deleted | Affected members continue with remaining roles | Requires zero members first | Reassignment enforced | Yes |
| Permission deprecated | None | Old key remains valid until migration | None | Yes |

---

## 9. Coverage and validation

| # | Check | Result |
|---|---|---|
| 1 | Permission keys defined | 78 (33 platform, 45 organization) |
| 2 | Keys derived from screens/features | 74 by screen/feature; 4 decision-backed (`platform.coupons.*`, `platform.organization_access.*`, `platform.audit.view`) |
| 3 | Roles using the catalog | 17 (15 RBAC roles; R-01/R-02 ownership-based) |
| 4 | Platform/organization family mixing | 0 roles |
| 5 | Every organization permission declares organization-type applicability | 45/45 |
| 6 | Owner roles contain all applicable keys | Company 37/37; hotel 30/30 |
| 7 | Roles with zero permissions | R-01, R-02 (intentional) |
| 8 | Service scope defined per role | 17/17 |
| 9 | Delegation ceiling defined | 17/17 |
| 10 | Screens with no owning permission | 0 (each screen maps to a feature; each mutating operation maps to a key) |
| 11 | Permission keys with no feature | 0 |
| 12 | Wildcard or umbrella permissions | 0 |

### 9.1 Remaining ambiguity

* **R-17 Service owner** is modelled as an organization-level, service-scoped manager because that is the only interpretation that fits "manage employees and access within their own service scope" alongside company/hotel owners. If the intended meaning is a *platform* service-line owner (for example, the platform's head of visas), the role family and scope change. This is Q-45 and blocks Phase 3.
* **`bookings.create`** has no drawn screen. It exists because the operation is required by the permission model and is a standard partner capability; if partner-created bookings are out of scope, the key can be dropped without affecting anything else.
* **Hotel branch scope** is not modelled. If a hotel organization can own multiple properties, a branch scope level must be added (Q-49).

