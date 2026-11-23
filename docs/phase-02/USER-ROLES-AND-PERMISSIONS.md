# Phase 2 — User Roles & Permissions

**Project:** Traway
**Phase:** 2 of 7 — Functional & Business Analysis
**Status:** Complete — awaiting approval (`APPROVE PHASE 2`)
**Inputs:** [`FEATURES-CATALOG.md`](./FEATURES-CATALOG.md), [`BUSINESS-LOGIC.md`](./BUSINESS-LOGIC.md)
**Date:** 2026-11-23

---

## 1. Role model overview

The design establishes **two independent permission families** that must not be merged:

1. **Platform family** — Traway staff. Grants rights across all tenants (partner approvals, catalogues, customers, finance, platform roles).
2. **Tenant family** — partner staff. Grants rights only inside one partner tenant (company or hotel). A tenant role is never global.

The restricted employee portal is **not a third family**: it is the company tenant with a narrow role (R-16) and locked modules.

The public surface (login, join request) is unauthenticated and therefore not a role. A person submitting a join request has no permissions; they receive an activation link only after approval.

### 1.1 Permission vocabulary

| Code | Meaning |
|---|---|
| **V** | View / read list and detail |
| **C** | Create |
| **U** | Update |
| **D** | Delete / destructive action |
| **X** | No access (resource must be rejected server-side) |
| **RO** | Read-only by design (not merely missing a button) |

Special actions (publish, confirm, refund, export, suspend, transfer) are written explicitly in the matrix because the design treats them as separate permissions (D-04.8 lists عرض/إضافة/تعديل/حذف/نشر/تأكيد/استرداد).

### 1.2 Role index

| ID | Role | Application | Family | Scope |
|---|---|---|---|---|
| R-01 | Traveller (registered customer) | MOB | Customer | Own account and own bookings only |
| R-02 | Guest (unauthenticated) | MOB | Customer | Public content only |
| R-03 | Platform super admin | ADM | Platform | Entire platform |
| R-04 | Platform finance staff | ADM | Platform | Financial data, settlement execution |
| R-05 | Platform content manager | ADM | Platform | Catalogues (visas, eSIM, destinations) |
| R-06 | Platform support agent | ADM | Platform | Customers and bookings, read/assist |
| R-07 | Company owner | COM | Tenant (company) | Own tourism company |
| R-08 | Bookings employee (company) | COM | Tenant (company) | Bookings of own company |
| R-09 | Visa employee (company) | COM | Tenant (company) | Visa requests of own company |
| R-10 | Accountant (company) | COM | Tenant (company) | Financial reports of own company (read) |
| R-11 | Content manager (company) | COM | Tenant (company) | Tours/programmes of own company |
| R-12 | Hotel owner | HTL | Tenant (hotel) | Own hotel |
| R-13 | Reception | HTL | Tenant (hotel) | Arrivals, check-in/out |
| R-14 | Bookings manager (hotel) | HTL | Tenant (hotel) | Hotel bookings, rooms & rates |
| R-15 | Hotel accountant | HTL | Tenant (hotel) | Hotel reports (read) |
| R-16 | Restricted employee | EMP | Tenant (company), narrow role | Assigned visa requests and assigned bookings |

---

## 2. Role definitions

### R-01 — Traveller (registered customer)

* **Entry:** mobile app after any authentication method (phone OTP, e-mail/password, Apple, Google).
* **Data scope:** own profile, own travellers, own documents, own bookings, own payment instruments, own favourites, own notifications.
* **Can:** browse, search, book flights/hotels/tours, apply for visas, buy eSIMs, manage bookings (reschedule, baggage, cancel), manage travellers and documents, manage payment methods, change language/notifications, delete account.
* **Cannot:** see another customer's data, see partner cost/commission, access any dashboard, change catalogue prices, self-approve a visa, self-issue a refund beyond policy.
* **Key screens:** M-014 – M-068 (all authenticated mobile states).

### R-02 — Guest (unauthenticated)

* **Entry:** «تصفح كضيف» on M-005.
* **Data scope:** public catalogue content only.
* **Can:** browse home, search, view flight/hotel/tour/visa/eSIM catalogue and details.
* **Cannot:** book, pay, apply for a visa, purchase an eSIM, save favourites, message support with an account context, or view any booking. Any commerce action redirects to authentication.
* **Key screens:** M-005, M-014 – M-035 (public portions), M-057 – M-068 (public portions).

### R-03 — Platform super admin

* **Entry:** D-01.1 with account type «إدارة Traway».
* **Data scope:** all tenants, all customers, all bookings, all money.
* **Can:** everything in the admin portal, including partner approval and suspension, commission changes, catalogue publishing, customer blocking, role creation, settlement transfer, exports.
* **Cannot:** impersonate a partner owner silently (no impersonation UI exists); delete financial records (BR-102).
* **Key screens:** D-03.1 – D-03.15 (all).
* **Design note:** the role matrix on D-03.15 shows the super admin as the top of the hierarchy; it is the only role that can create platform roles.

### R-04 — Platform finance staff

* **Entry:** D-01.1 → finance account.
* **Data scope:** payments, refunds, commissions, partner dues, settlements, transaction log.
* **Can:** view D-03.14 and financial KPIs on D-03.1; execute/confirm transfers; export financial data; view partner financial summaries on D-03.7/D-03.8.
* **Cannot:** approve partners (D-03.6), edit catalogues (D-03.9/D-03.10), change platform roles (D-03.15), block customers (D-03.13).
* **Key screens:** D-03.1 (financial widgets), D-03.7/D-03.8 (financial tab), D-03.14.

### R-05 — Platform content manager

* **Entry:** D-01.1 → content account.
* **Data scope:** visa catalogue, eSIM catalogue, destinations/content.
* **Can:** view (V), add (C), edit (U) and delete (D) catalogue content per the D-03.15 matrix; toggle country/type availability; edit required documents; sync eSIM provider; export price comparison.
* **Cannot:** touch money (finance), partners, customers, or roles. Cannot publish a commission change.
* **Key screens:** D-03.9, D-03.10; catalogue widgets on D-03.1.
* **Note:** D-03.15 shows the section × action matrix specifically for this role, confirming it is the reference implementation of fine-grained permissions.

### R-06 — Platform support agent

* **Entry:** D-01.1 → support account.
* **Data scope:** customers and bookings; may read partner contact data but not commercial terms.
* **Can:** search customers (D-03.12), view customer detail (D-03.13), view bookings (D-03.3), send a report, mark notifications read.
* **Cannot:** block a customer, approve a partner, change a commission, edit catalogues, execute a transfer, create roles.
* **Key screens:** D-03.2, D-03.3 (read), D-03.12, D-03.13 (read).

### R-07 — Company owner

* **Entry:** D-01.1 with account type «شركة سياحة», after activation.
* **Data scope:** own company tenant only.
* **Can:** everything in the company portal: overview, tours (create/submit/publish workflow), bookings, visa review, team & roles (invite, edit, deactivate), financial reports, company settings, settlements view.
* **Cannot:** access another tenant, set its own commission, publish without platform review, approve its own join request, transfer ownership to a non-member.
* **Key screens:** D-04.1 – D-04.10 (all).
* **Constraint:** sole role allowed to transfer ownership or delete the tenant (BR-086).

### R-08 — Bookings employee (company)

* **Entry:** company portal with assigned role.
* **Data scope:** bookings belonging to the company.
* **Can:** view bookings (V), confirm/cancel within policy, export booking lists, view related customer contact for the booking.
* **Cannot:** edit tours, review visa documents, see financial reports, manage team, change company settings.
* **Key screens:** D-04.5, relevant widgets on D-04.1.

### R-09 — Visa employee (company)

* **Entry:** company portal with assigned role.
* **Data scope:** visa requests of the company.
* **Can:** view requests, accept/reject documents, accept all documents, advance to next applicant, request additional permission from owner (as in D-06.1).
* **Cannot:** see bookings beyond context, edit tours, view finance, manage team.
* **Key screens:** D-04.6, queue widgets on D-04.1.

### R-10 — Accountant (company)

* **Entry:** company portal with assigned role.
* **Data scope:** company financial data.
* **Can:** view financial reports (D-04.9), view settlements and card on file, export Excel.
* **Cannot:** change prices, publish tours, confirm bookings, manage team. Read-only by design.
* **Key screens:** D-04.9.

### R-11 — Content manager (company)

* **Entry:** company portal with assigned role.
* **Data scope:** tours and programmes of the company.
* **Can:** create/edit tours, manage schedule slots, gallery, itinerary, submit for review, cancel a submission.
* **Cannot:** publish directly to customers, see bookings, see visa requests, see finance.
* **Key screens:** D-04.3, D-04.4.

### R-12 — Hotel owner

* **Entry:** D-01.1 with account type «فندق», after activation.
* **Data scope:** own hotel tenant only.
* **Can:** everything in the hotel portal: overview, rooms & rates, bookings and check-in/out, employees & permissions, hotel profile publishing, reports, settlement view.
* **Cannot:** access company portal, edit platform commission, publish without approved documents (BR-110), see another hotel.
* **Key screens:** D-05.1 – D-05.7 (all).

### R-13 — Reception

* **Entry:** hotel portal with reception role.
* **Data scope:** today's arrivals and in-house guests.
* **Can:** view bookings, check guests in and out, view room availability.
* **Cannot:** change prices, stop-sell, view financial reports, manage employees, edit the hotel profile.
* **Key screens:** D-05.1 (arrivals), D-05.4 (arrivals/in-house tabs).

### R-14 — Bookings manager (hotel)

* **Entry:** hotel portal with bookings-manager role.
* **Data scope:** hotel bookings and room inventory.
* **Can:** view and manage bookings, check-in/out, update prices, apply stop-sell, view availability.
* **Cannot:** manage employees, edit the hotel profile, view the accountant's financial reports (unless separately granted).
* **Key screens:** D-05.3, D-05.4, D-05.1.

### R-15 — Hotel accountant

* **Entry:** hotel portal with accountant role.
* **Data scope:** hotel financial data.
* **Can:** view reports, export Excel, view settlement transfers.
* **Cannot:** change rates, check guests in/out, edit profile, manage employees. Read-only by design.
* **Key screens:** D-05.7.

### R-16 — Restricted employee

* **Entry:** D-01.1 with account type «موظف» — same shell as the company portal but a narrow role.
* **Data scope:** only records assigned to this user.
* **Can:** view assigned visa requests, accept/reject their documents, view assigned bookings read-only, read own notifications, request additional permission from the owner.
* **Cannot:** see unassigned records, mutate bookings, access team/finance/settings (locked modules), invite staff, change roles.
* **Key screens:** D-06.1, D-06.2, D-06.3.
* **Constraint:** enforcement is server-side (BR-115, BR-116, BR-117); the locked sidebar is cosmetic.

---

## 3. Permission matrix — mobile

| Capability | R-01 Traveller | R-02 Guest |
|---|---|---|
| Browse home / search / catalogue | V | V |
| View offers & destinations | V | V |
| Book flight / hotel / tour | C | X (redirect to auth) |
| Apply for visa | C | X |
| Buy eSIM | C | X |
| View own bookings | V | X |
| Reschedule / add baggage / cancel | U | X |
| Manage travellers & passport scan | C/U/D | X |
| Manage payment methods | C/U/D | X |
| Favourites | C/D | X |
| Notifications | V/U | X |
| Support contact | V | V (public channels) |
| Delete account | D (own) | X |

---

## 4. Permission matrix — platform portals (APP-ADM)

Legend: V view · C create · U update · D delete · X none · `P` publish/approve · `T` transfer/settle · `RO` read-only.

| Module / screen | R-03 Super admin | R-04 Finance | R-05 Content | R-06 Support |
|---|---|---|---|---|
| Overview D-03.1 | V | V (financial) | V (catalogue widgets) | V (operational) |
| Notifications D-03.2 | V/U | V/U | V/U | V/U |
| All bookings D-03.3 | V, export | V, export | X | V, RO |
| Partners D-03.4 | V/C/U/D | V (financial columns) | X | V |
| Add partner D-03.5 | C | X | X | X |
| Join review D-03.6 | V/P (accept/reject) | X | X | X |
| Partner detail D-03.7, D-03.8 | V/U, suspend | V (financial) | X | V |
| Visa catalogue D-03.9 | V/C/U/D | X | V/C/U/D | V |
| eSIM catalogue D-03.10 | V/C/U/D, sync | V (margins) | V/C/U/D, sync | V |
| Deletion requests D-03.11 | V/U | X | X | V, RO |
| Customers D-03.12 | V, export | X | X | V, export |
| Customer detail D-03.13 | V/U, block | V (payments) | X | V, RO |
| Finance D-03.14 | V, T | V, T, export | X | X |
| Roles D-03.15 | V/C/U/D | X | X | X |

**Platform rules**

1. Super admin is the only role that can create or modify platform roles (BR-084).
2. Finance can execute a transfer only in `pending`/`failed` state; `transferred` is terminal (BR-099).
3. Support can never block an account; only super admin can (Q-28).
4. Content manager cannot change commission or approve partners.

---

## 5. Permission matrix — company portal (APP-COM)

| Module / screen | R-07 Owner | R-08 Bookings emp | R-09 Visa emp | R-10 Accountant | R-11 Content mgr |
|---|---|---|---|---|---|
| Overview D-04.1 | V | V (bookings queue) | V (visa queue) | V (financial) | V (tour queue) |
| Notifications D-04.2 | V/U | V/U | V/U | V/U | V/U |
| Tours D-04.3 | V/C/U/D, publish-submit | V | X | X | V/C/U/D |
| Tour editor D-04.4 | C/U/D | X | X | X | C/U/D |
| Bookings D-04.5 | V/U, export | V/U, export | X | V, RO | X |
| Visa requests D-04.6 | V/U, accept | X | V/U, accept | X | X |
| Team D-04.7 | V/C/U/D, invite | X | X | X | X |
| Custom role D-04.8 | C/U/D | X | X | X | X |
| Financial reports D-04.9 | V, export | X | X | V, export | X |
| Company settings D-04.10 | V/U | X | X | X | X |

**Company rules**

1. Only the owner can invite staff, edit roles, transfer ownership, delete the tenant (BR-086).
2. A delegated role cannot grant a permission it does not hold (BR-084).
3. The tour editor cannot self-publish; submission always routes to platform review (BR-092).
4. The accountant is read-only on money and has no access to operations.
5. The visa employee acts on documents only; the decision to submit to the embassy is not depicted and remains a platform-level unknown (`Q-13`).

---

## 6. Permission matrix — hotel portal (APP-HTL)

| Module / screen | R-12 Hotel owner | R-13 Reception | R-14 Bookings mgr | R-15 Accountant |
|---|---|---|---|---|
| Overview D-05.1 | V, U (prices) | V (arrivals) | V, U | V (financial) |
| Notifications D-05.2 | V/U | V/U | V/U | V/U |
| Rooms & rates D-05.3 | V/C/U/D, stop-sell | V (availability) | V/U, stop-sell | X |
| Bookings D-05.4 | V/U, check-in/out, export | V/U, check-in/out | V/U, check-in/out, export | V, RO |
| Employees D-05.5 | V/C/U/D | X | X | X |
| Hotel profile D-05.6 | V/U, publish | X | X | X |
| Reports D-05.7 | V, export | X | V (operational), export | V, export |

**Hotel rules**

1. Only the owner edits employees, roles and the profile (BR-110).
2. Reception cannot change prices or stop-sell.
3. Publishing the profile requires approved legal documents (BR-110, BR-101).
4. The 5 % pay-now discount is a commercial term, visible to the owner/bookings manager but not editable here (BR-067).

---

## 7. Permission matrix — restricted employee (APP-EMP)

| Capability | R-16 |
|---|---|
| View assigned visa requests | V |
| Accept / reject documents on assigned requests | U |
| Request additional permission from owner | C (request only) |
| View assigned bookings | RO |
| Mutate bookings | X |
| Team & permissions | X (locked) |
| Finance | X (locked) |
| Settings | X (locked) |
| Notifications (own) | V/U |

---

## 8. Role assignment & lifecycle rules

| Rule | Statement | Reference |
|---|---|---|
| RA-01 | A user has exactly one platform role or one tenant role at a time; roles are not additive across families. | D-03.15 |
| RA-02 | A tenant must always have at least one active owner; the last owner cannot be removed or demoted. | BR-086 |
| RA-03 | Deactivating an employee revokes access immediately and retains their audit trail. | D-04.2 «employee deactivated» |
| RA-04 | An invited employee cannot log in until the invitation is accepted. | D-04.7 invite, D-02.3 activation link |
| RA-05 | A custom role is a named set of permissions inside one tenant; it cannot exceed the owner's own permissions. | D-04.8 |
| RA-06 | Changing a role's permissions affects all members holding that role, immediately (BR-088). | D-04.8 |
| RA-07 | Deleting a role requires reassigning its members first; a role in use cannot be deleted silently. | D-03.15 team-role list |
| RA-08 | Account blocking is platform-only and applies across all applications and sessions. | D-03.13, BR-030 |

---

## 9. Traceability

| Role | Screens that define it |
|---|---|
| R-01 | M-014 – M-056 (authenticated) |
| R-02 | M-005, M-014 – M-020, M-027 – M-035, M-057 – M-066 (public views) |
| R-03 | D-03.1 – D-03.15 |
| R-04 | D-03.14, D-03.1 financial widgets, D-03.7/8 financial blocks |
| R-05 | D-03.9, D-03.10, D-03.15 matrix |
| R-06 | D-03.2, D-03.3, D-03.12, D-03.13 |
| R-07 | D-04.1 – D-04.10 |
| R-08 | D-04.5, D-04.1 queue |
| R-09 | D-04.6, D-04.1 queue |
| R-10 | D-04.9 |
| R-11 | D-04.3, D-04.4 |
| R-12 | D-05.1 – D-05.7 |
| R-13 | D-05.1, D-05.4 |
| R-14 | D-05.1, D-05.3, D-05.4 |
| R-15 | D-05.7 |
| R-16 | D-06.1, D-06.2, D-06.3 |

**Validation:** 16 roles; every role has at least one defining screen; every screen in Phase 1 that requires an actor is covered by at least one role; no role is granted access to a module that its design screens do not show.
