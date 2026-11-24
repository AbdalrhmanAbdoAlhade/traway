# Permission Catalog Reconciliation

**Project:** Traway
**Date:** 2026-11-24
**Status:** Proposed canonical catalog — awaiting owner approval (`G0.2`)
**Companion documents:** [`GATE-0-DECISION-REGISTER.md`](./GATE-0-DECISION-REGISTER.md) · [`IMPLEMENTATION-GAP-ANALYSIS.md`](./IMPLEMENTATION-GAP-ANALYSIS.md) · [`phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md`](./phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md) · [`phase-02/AUTHORIZATION-ARCHITECTURE.md`](./phase-02/AUTHORIZATION-ARCHITECTURE.md)

---

## 1. Purpose and method

The permission key count has been reported as **83**, **84**, **78** and **80** in different places. Every figure is reproducible; none of them is the catalogue. This document inventories every key that exists in the documentation and in the codebase, resolves the discrepancies, and proposes **one canonical catalog** with an explanation for every addition, removal and rename.

**Sources inspected**

| Source | What it contributed |
|---|---|
| `database/seeders/RolesAndPermissionsSeeder.php` | The 80 keys currently created at runtime, with domain, sensitivity, description, scope requirement and allowed organization types |
| `docs/phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md` §3.1–3.2 | The documented catalogue tables (36 platform rows, 47 organization rows) |
| `docs/phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md` §9 | The stale self-validation totals (78 = 33 + 45) |
| `docs/phase-02/AUTHORIZATION-ARCHITECTURE.md` §5.5, §7.2, §14.1 | Delegation rules and the non-delegable surfaces |
| `config/authorization.php` | The single source of truth for delegability, owner templates, service scopes and owner-only actions |
| `docs/phase-02/FEATURES-CATALOG.md` | Feature IDs used in the feature column |

**Method**

1. Extract every backticked dotted token from the matrix → 84 distinct keys.
2. Extract every catalogue row key from the seeder → 80 distinct keys.
3. Diff in both directions; classify each key as present-in-both, docs-only or code-only.
4. Judge each docs-only and code-only key against the approved Gate 0 decisions and the feature catalogue.
5. Emit one canonical list and a delta table.

**Nothing was changed in application code, the seeder, or any migration during this reconciliation.**

---

## 2. Why the four counts disagreed

| Reported count | Where it comes from | What it actually counts | Verdict |
|---|---|---|---|
| **83** | Matrix §3 header ("Permission catalog (83 keys)") and its totals line "36 platform + 47 organization = 83" | The catalogue tables as authored | **Correct for the documents** — §3.1 contains exactly 36 rows and §3.2 exactly 47 rows (verified by row count) |
| **84** | Any naive extraction of backticked keys in the matrix | The 83 catalogue keys **plus `platform.audit.view`**, which appears only once — inside the §9 validation note "5 decision-backed (`platform.coupons.*`, `platform.organization_access.*`, `platform.audit.view`)" — and has **no catalogue row**, no description, no domain, and no role assignment it belongs to | **Phantom key.** It is not part of the 83-key catalogue despite the matrix naming it |
| **78** | Matrix §9 row 1 ("Permission keys defined: 78 (33 platform, 45 organization)") | A stale self-check written against an earlier draft | **Wrong.** It contradicts the same document's own tables by 5 keys (3 platform + 2 organization) |
| **80** | `IMPLEMENTATION-STATUS.md` and the seeder | The keys actually created at runtime: 36 platform + 44 organization | **Correct for the code**, but not the catalogue — it is missing 8 documented keys and adds 4 undocumented ones |

The "5 decision-backed" note in §9 is itself inconsistent: it lists `platform.coupons.*` (2 keys), `platform.organization_access.*` (2 keys) and `platform.audit.view` (1) = 5, but `platform.settings.manage`, `settlements.accounts.manage` and `properties.create` are also decision-backed (`D`) and are not counted there. The note should not be used as a source of truth.

**Conclusion:** the documentation's 83 keys and the implementation's 80 keys are two different sets of 80-odd keys that overlap in 76. Neither is canonical. The reconciliation below produces the canonical set.

---

## 3. Set arithmetic

```
Documented catalogue rows .......................... 83   (36 platform + 47 organization)
Documented keys including the phantom audit key .... 84   (37 + 47)
Implemented in seeder .............................. 80   (36 + 44)
Intersection (key identical in both) ............... 76
Union (every key that exists anywhere) ............. 88   (39 platform + 49 organization)
```

| Direction | Keys |
|---|---|
| **Docs-only (8)** — the reported "missing from the seeder" set | `platform.audit.view`, `platform.settings.manage`, `platform.visa_requests.update_status`, `properties.view`, `properties.create`, `properties.update`, `properties.publish`, `settlements.accounts.manage` |
| **Code-only (4)** — the reported "undocumented" set | `org.profile.publish`, `platform.visa_requests.assign`, `platform.visa_requests.decide`, `visa_requests.review` |

Union 88 − 3 removals = **85 canonical keys**. No key is invented; every addition is a key that already exists in one of the two sources.

---

## 4. Canonical platform family (37 keys)

Columns: **Scope** is always platform-global and cross-tenant. **Delegable** is `no` for every `platform.*` key — `AUTHORIZATION-ARCHITECTURE.md` §7.2 and `config/authorization.php` both forbid attaching a platform key to an organization role, so delegability is not repeated per row except where behavior differs.

| # | Canonical key | Description | Domain | Sensitivity | Origin | Feature(s) | Verdict |
|---:|---|---|---|---|---|---|---|
| 1 | `platform.overview.view` | View platform KPIs, charts and the pending queue | overview | normal | both | F-078 | keep |
| 2 | `platform.notifications.view` | View the platform notification inbox | notifications | normal | both | F-079 | keep |
| 3 | `platform.notifications.manage` | Mark notifications read; manage inbox state | notifications | normal | both | F-079 | keep |
| 4 | `platform.bookings.view` | View bookings across all tenants | bookings | normal | both | F-080 | keep |
| 5 | `platform.bookings.export` | Export the all-bookings table | bookings | sensitive | both | F-080 | keep |
| 6 | `platform.partners.view` | View partner directory and partner details | partners | normal | both | F-081, F-083 | keep |
| 7 | `platform.partners.create` | Create a partner and invite its owner | partners | sensitive | both | F-082 | keep |
| 8 | `platform.partners.update` | Edit partner commission, services and data | partners | sensitive | both | F-083 | keep |
| 9 | `platform.partners.suspend` | Suspend or reinstate a partner | partners | sensitive | both | F-083 | keep |
| 10 | `platform.partner_requests.view` | View join requests | partners | normal | both | F-077, F-081 | keep |
| 11 | `platform.partner_requests.review` | Accept or reject join requests and verify documents | partners | sensitive | both | F-077 | keep |
| 12 | `platform.visa_catalog.view` | View visa offerings, fees and document requirements | catalogue | normal | both | F-084 | keep |
| 13 | `platform.visa_catalog.manage` | Create, update and deactivate visa offerings | catalogue | sensitive | both | F-084 | keep |
| 14 | `platform.visa_requests.view` | View all visa applications and uploaded documents | visas | sensitive | both | F-096 | keep |
| 15 | `platform.visa_requests.review` | Review visa documents and request additional documents | visas | sensitive | both | F-096 | **description changed** |
| 16 | `platform.visa_requests.assign` | Assign visa applications to platform staff | visas | sensitive | **code only** | F-096 | **add** |
| 17 | `platform.visa_requests.decide` | Record the final approval or rejection (DEC-04) | visas | sensitive | **code only** | F-096 | **add** |
| 18 | `platform.esim_catalog.view` | View eSIM packages, costs and margins | catalogue | normal | both | F-085 | keep |
| 19 | `platform.esim_catalog.manage` | Create and update eSIM packages | catalogue | sensitive | both | F-085 | keep |
| 20 | `platform.esim_catalog.sync` | Trigger a provider catalogue sync | catalogue | sensitive | both | F-085 | keep |
| 21 | `platform.customers.view` | View the customer directory and profiles | customers | sensitive | both | F-087, F-088 | keep |
| 22 | `platform.customers.update` | Edit customer profile data | customers | sensitive | both | F-088 | keep |
| 23 | `platform.customers.block` | Block or unblock a customer account | customers | sensitive | both | F-088 | keep |
| 24 | `platform.customers.export` | Export customer data | customers | sensitive | both | F-087 | keep |
| 25 | `platform.deletion_requests.view` | View account-deletion requests | customers | sensitive | both | F-086 | keep |
| 26 | `platform.deletion_requests.process` | Approve, cancel or execute an account deletion | customers | sensitive | both | F-086 | keep |
| 27 | `platform.finance.view` | View payments, commissions, dues and transactions | finance | financial | both | F-089 | keep |
| 28 | `platform.finance.settlements.execute` | Execute or mark a settlement transfer | finance | financial | both | F-089 | keep |
| 29 | `platform.finance.export` | Export financial data | finance | financial | both | F-089 | keep |
| 30 | `platform.settings.manage` | Manage platform settings, global payment rules and commission defaults | settings | access-control | **docs only** | F-112 | **add** |
| 31 | `platform.roles.view` | View platform roles and their permissions | access | normal | both | F-090, F-112 | keep |
| 32 | `platform.roles.manage` | Create, edit and delete platform roles | access | access-control | both | F-090, F-112 | keep |
| 33 | `platform.roles.assign` | Assign platform roles to platform staff | access | access-control | both | F-090, F-112 | keep |
| 34 | `platform.coupons.view` | View platform-wide coupons | coupons | normal | both | F-111 | keep |
| 35 | `platform.coupons.manage` | Create, edit and deactivate platform coupons | coupons | sensitive | both | F-111 | keep |
| 36 | `platform.organization_access.view` | View memberships, roles and grants of any organization | access | sensitive | both | F-112 | keep |
| 37 | `platform.organization_access.manage` | Assign roles and permissions inside any organization | access | access-control | both | F-112 | keep |
| — | ~~`platform.audit.view`~~ | — | — | — | docs only | — | **REMOVED (G0-DEC-01)** |
| — | ~~`platform.visa_requests.update_status`~~ | — | — | — | docs only | — | **REMOVED (G0-DEC-04)** |

---

## 5. Canonical organization family (48 keys)

Organization keys are never held by platform-role members through an organization role, and `platform.*` keys are never attached to an organization role. **Delegable = `no`** marks the keys an organization owner may hold but may never grant to an employee, directly or through a custom role (`config/authorization.php` `non_delegable`).

| # | Canonical key | Description | Org types | Scope requirement | Delegable | Origin | Feature(s) | Verdict |
|---:|---|---|---|---|---|---|---|---|
| 1 | `org.profile.view` | View organization profile and settings | All | organization | yes | both | F-100, F-106, F-114 | keep |
| 2 | `org.profile.update` | Edit organization profile and settings | All | organization | yes | both | F-100, F-106, F-114 | keep |
| 3 | `org.documents.manage` | Upload or replace legal documents | All | organization | yes | both | F-100, F-106, F-114 | keep |
| 4 | `org.notifications.view` | View the organization notification inbox | All | organization | yes | both | F-092, F-102, F-110 | keep |
| 5 | `employees.view` | View employees and their roles | All | organization | yes | both | F-097, F-105, F-112 | keep |
| 6 | `employees.create` | Invite an employee | All | organization | **no** | both | F-097, F-105, F-112 | keep |
| 7 | `employees.update` | Edit employee profile, role and scopes | All | organization | **no** | both | F-097, F-105, F-112 | keep |
| 8 | `employees.deactivate` | Suspend or remove an employee | All | organization | **no** | both | F-097, F-105, F-112 | keep |
| 9 | `roles.view` | View roles and their permissions | All | organization | yes | both | F-097, F-098, F-105 | keep |
| 10 | `roles.manage` | Create, edit and delete custom roles | All | organization | **no** | both | F-098, F-105 | keep |
| 11 | `roles.assign` | Assign roles to employees | All | organization | **no** | both | F-097, F-105 | keep |
| 12 | `bookings.view` | View bookings and guest data | All | organization | yes | both | F-095, F-104, F-109 | keep — **org types confirmed by catalog** |
| 13 | `bookings.create` | Create a booking on behalf of a customer | All | organization | yes | both | F-095, F-104 | keep |
| 14 | `bookings.update` | Modify booking details | All | organization | yes | both | F-095, F-104 | keep |
| 15 | `bookings.cancel` | Cancel a booking | All | organization | yes | both | F-095, F-104 | keep |
| 16 | `bookings.refund` | Initiate an eligible refund | All | organization | **no** | both | F-095, F-104 | keep |
| 17 | `bookings.checkin` | Check a guest in | Hotel | organization | yes | both | F-104 | keep |
| 18 | `bookings.checkout` | Check a guest out | Hotel | organization | yes | both | F-104 | keep |
| 19 | `bookings.export` | Export booking lists | All | organization | yes | both | F-095, F-104 | keep |
| 20 | `tours.view` | View tours and programmes | Company | service: tours | yes | both | F-093 | keep |
| 21 | `tours.create` | Create a tour | Company | service: tours | yes | both | F-094 | keep |
| 22 | `tours.update` | Edit a tour | Company | service: tours | yes | both | F-094 | keep |
| 23 | `tours.delete` | Delete or withdraw a tour | Company | service: tours | yes | both | F-093 | keep |
| 24 | `tours.submit` | Submit a tour for platform review | Company | service: tours | yes | both | F-094 | keep |
| 25 | `tours.slots.manage` | Manage departure slots and seat capacity | Company | service: tours | yes | both | F-094 | keep |
| 26 | `visa_requests.view` | View visa applications attached to the company's own offerings | Company | service: visas | yes | both | F-096, F-108 | keep |
| 27 | `visa_requests.review` | Validate documents and flag missing items; no final decision (DEC-04) | Company | service: visas | yes | **code only** | F-096, F-108 | **add** |
| 28 | `visa_offerings.view` | View the company's visa offerings | Company | service: visas | yes | both | F-084 | keep |
| 29 | `visa_offerings.manage` | Create and update own visa offerings and prices | Company | service: visas | yes | both | F-084 | keep |
| 30 | `properties.view` | View hotel properties owned by the organization | Hotel | property | yes | **docs only** | F-101, F-106 | **add** |
| 31 | `properties.create` | Add a hotel property | Hotel | property | yes | **docs only** | F-106 | **add** |
| 32 | `properties.update` | Edit property details, address, location and documents | Hotel | property | yes | **docs only** | F-106 | **add** |
| 33 | `properties.publish` | Publish or unpublish a hotel property | Hotel | property | yes | **docs only** | F-106 | **add** |
| 34 | `rooms.view` | View room types and availability | Hotel | property | yes | both | F-103 | keep |
| 35 | `rooms.manage` | Create, edit and delete room types | Hotel | property | yes | both | F-103 | keep |
| 36 | `rates.view` | View the rate calendar | Hotel | property | yes | both | F-103 | keep |
| 37 | `rates.update` | Update nightly prices and bulk adjustments | Hotel | property | yes | both | F-103 | keep |
| 38 | `rates.stop_sell` | Apply or lift stop-sell | Hotel | property | yes | both | F-103 | keep |
| 39 | `coupons.view` | View the company's coupons | Company | organization | yes | both | F-111 | keep |
| 40 | `coupons.create` | Create company-scoped coupons | Company | organization | yes | both | F-111 | keep |
| 41 | `coupons.manage` | Edit, limit and deactivate own coupons | Company | organization | yes | both | F-111 | keep |
| 42 | `reports.view` | View operational and financial reports | All | organization | yes | both | F-099, F-107 | keep |
| 43 | `reports.export` | Export reports | All | organization | yes | both | F-099, F-107 | keep |
| 44 | `settlements.view` | View settlements and partner dues | All | organization | yes | both | F-099 | keep |
| 45 | `settlements.manage` | Request an on-demand payout | All | organization | **no** | both | F-099 | keep |
| 46 | `settlements.accounts.manage` | Manage payout destination accounts; owner-only | All | organization | **no** | **docs only** | F-099 | **add** |
| 47 | `team_activity.view` | View the team activity feed derived from `status_histories` | Company | organization | yes | both | F-091 | keep — **scope restated under G0-DEC-01** |
| 48 | `customers.view` | View customer contact for own bookings | All | organization | yes | both | F-095, F-104 | keep |
| — | ~~`org.profile.publish`~~ | Hotel profile publishing | Hotel | — | — | code only | F-106 | **REMOVED (superseded by `properties.publish`)** |

---

## 6. The eight "missing" keys reviewed individually

These are the keys the matrix defines but the seeder never creates.

| # | Key | Documented purpose | Decision | Reasoning |
|---:|---|---|---|---|
| 1 | `platform.audit.view` | None. The matrix never gives it a row, description, domain or role — it appears only in the §9 note "5 decision-backed (`platform.coupons.*`, `platform.organization_access.*`, `platform.audit.view`)" | **REMOVE** | `G0-DEC-01` permanently excludes audit and activity logs. This key has no other documented function, so there is nothing to preserve it for. It is **not** re-purposed, and no audit table, service, event or endpoint is introduced to justify it. The §9 note must be corrected to drop it and to stop claiming 5 decision-backed keys |
| 2 | `platform.settings.manage` | "Manage platform settings, global payment rules and commission defaults" (matrix row 92; feature F-112) | **ADD as-is** | Genuinely required: `platform_settings` already exists and is unused, and F-112 requires platform-wide payment rules and commission defaults. No governance/audit capability is implied — settings changes are ordinary configuration writes. Must be added to the platform catalogue, the `platform_super_admin` role, and probably a settings-oriented role |
| 3 | `platform.visa_requests.update_status` | "Move an application through the DEC-07 statuses" (matrix row 79) | **REMOVE — replaced by `review` + `decide`** | `G0-DEC-04` splits the capability: document review (no decision) and final decision (platform only). A single generic "move through statuses" key would let its holder record the final decision without the decision key, defeating DEC-04. The operational transitions it covered are exactly (a) request documents → `review`, (b) approve/reject → `decide`. Nothing is lost |
| 4 | `properties.view` | "View hotel properties of the organization" (matrix row 135) | **ADD as-is** | `G0-DEC-03` makes the hotel property a first-class entity owned by a hotel organization. Viewing your organization's properties is distinct from viewing the organization profile |
| 5 | `properties.create` | "Add a hotel property" (row 136) | **ADD as-is** | Required by multi-property ownership. Note the matrix binds it to F-113, which does not exist in the catalogue (see §8, C-15); the real driver is F-106 (hotel profile & publishing) plus DEC-03 |
| 6 | `properties.update` | "Edit property details, address, location and documents" (row 137) | **ADD as-is** | Required by F-106 and DEC-03 |
| 7 | `properties.publish` | "Publish or unpublish a hotel property" (row 138) | **ADD — replaces `org.profile.publish`** | Publishing is a per-property action once an organization owns several properties, so it cannot stay an organization-wide key. `BR-110` (publishing requires approved legal documents) hangs off this key |
| 8 | `settlements.accounts.manage` | "Manage payout accounts; owner-only, non-delegable" (row 151) | **ADD as-is, and add to the non-delegable list** | `payout_accounts` already exists with a one-default-per-organization constraint and verified/pending states. Changing where money is sent is a financial-control action: it must be owner-only and non-delegable, which requires it to be named in `config/authorization.php` `non_delegable`, not only described in prose |

---

## 7. The four "undocumented" keys reviewed individually

These are the keys the seeder creates but the documented catalogue does not contain.

| # | Key | What the code does with it | Decision | Reasoning |
|---:|---|---|---|---|
| 1 | `org.profile.publish` | Hotel-only; "Publish the hotel profile to customers" | **REMOVE — superseded by `properties.publish`** | Under DEC-03 the publishable unit is a property, not the organization profile. Keeping both would create two keys for one gate and make the `BR-110` document-verification check ambiguous |
| 2 | `platform.visa_requests.assign` | Assign visa applications to platform staff | **ADD (promote into the catalogue)** | Operational necessity that the documents never wrote down: the platform visa queue (D-03.3) shows assignment, and the seeded `platform_support` / `platform_visa_officer` roles depend on it. Without it, assignment would require `decide`, which is over-privileged |
| 3 | `platform.visa_requests.decide` | Record the final approval or rejection | **ADD (promote into the catalogue)** | `G0-DEC-04` makes this the single authoritative decision right; it must be a documented key, not an implementation detail |
| 4 | `visa_requests.review` | Company-side document validation; flags missing items | **ADD (promote into the catalogue)** | The documented `visa_requests.view` row explicitly says "no decision", which leaves the company's documented review work (D-04.6, F-037, F-108 "per-document accept/reject") with no key. `review` is the missing capability, and DEC-04 confirms it excludes the final decision |

**Why promote rather than absorb into existing keys?** Because in each case the alternative is a privilege escalation in the key that already exists (`visa_requests.view` would have to become a write key, or `decide` would have to cover assignment). Promotion keeps the least-privilege boundary intact.

---

## 8. Duplicates, naming inconsistencies and other catalog defects

| ID | Issue | Detail | Resolution |
|---|---|---|---|
| P-01 | `platform.audit.view` | Named in a note, absent from the catalogue, no description, no role | Remove (see §6 #1) |
| P-02 | Count drift | 83 / 84 / 78 / 80 as analysed in §2 | This document replaces all four. The matrix §3 header, §9 row 1 and §9 note must be corrected to the approved figure |
| P-03 | `org.profile.publish` vs `properties.publish` | Two keys for one publish gate | Remove `org.profile.publish` |
| P-04 | `platform.visa_requests.update_status` vs `review`/`decide` | Docs define a generic transition key; code splits it | Remove the generic key (DEC-04) |
| P-05 | `platform.visa_requests.review` description | Docs: "Review documents **and record the final decision**". Code: review only. The documented description contradicts DEC-04 | Adopt the code's narrower description |
| P-06 | `visa_requests.view` description | Docs: "…no decision" — correct, but the documented catalogue then offers no company review key at all | Add `visa_requests.review`; clarify that `view` alone is read-only |
| P-07 | `org.delete` | `AUTHORIZATION-ARCHITECTURE.md` §7.2 lists `org.delete` as a non-delegable **key**, but no such permission exists in either catalogue — deletion is an owner-only *action* (`organization.delete` in `config/authorization.php` `owner_only_actions`) | Keep deletion as an owner-only action, not a permission. Correct the §7.2 table to say "owner-only action, no assignable key" |
| P-08 | `team_activity.view` | "Team activity feed" reads like a general activity log, which DEC-01 forbids | Keep the key; bind it to `status_histories` only (the matrix description already says "derived from status histories"). It shows business status transitions of bookings/visa/refunds/payouts, never user actions |
| P-09 | `bookings.*` org types | Docs: `All` (including service provider). Code: `['company','hotel']` | Canonical = docs (`All`), **but** the service-provider service catalogue is still undefined, so service providers would hold booking permissions for a service they cannot sell. **Open sub-item** — see §10 |
| P-10 | `customers.view` org types | Docs: `All`. Code: `['company','hotel']` | Same reasoning as P-09. **Open sub-item** |
| P-11 | `properties.*` feature references | Rows cite F-113, which does not exist (catalogue ends at F-112) | Feature references corrected to F-101/F-106; matrix references fixed |
| P-12 | `platform.visa_requests.*` feature references | Rows cite F-115, which does not exist | Corrected to F-096/F-108 |
| P-13 | Prefix families | Platform keys use the `platform.` prefix; every other key has **no** prefix, while `config/authorization.php` `permission_prefixes` declares an `org.` prefix and 4 keys use it (`org.profile.*`, `org.documents.*`, `org.notifications.*`) | Cosmetic inconsistency only. **No rename** is recommended: renaming 44 keys would invalidate every seeded role, test and future custom role for zero functional gain. Record that the `org.` prefix is optional and the family is decided by the `platform.` prefix, not by the presence of `org.` |
| P-14 | Sensitivity vocabulary | Code uses `normal | sensitive | financial | access_control`; docs use `Normal | Sensitive | Financial | Access-control` | Documented values are canonical; the seeded `access_control` value should be written `access-control` for consistency (data value only, no schema change) |

---

## 9. Delegation rules (canonical)

Two mechanisms, one source of truth (`config/authorization.php`), enforced in the Authorization Service on every grant path — role create/edit, direct grant, template copy, invitation, and transfer:

1. **Family rule.** A `platform.*` key can never be attached to an organization role; an organization key can never be attached to a platform role. No exceptions.
2. **Non-delegable list.** An owner may hold these but may never grant them:

| Key | Why non-delegable |
|---|---|
| `employees.create` | Controls who enters the organization |
| `employees.update` | Can change another member's role and scopes |
| `employees.deactivate` | Can remove access |
| `roles.manage` | Can rewrite the permission set itself |
| `roles.assign` | Can hand out any delegable key |
| `settlements.manage` | Moves money out of the organization |
| `settlements.accounts.manage` | Redirects where money is sent (**added by this reconciliation**) |
| `bookings.refund` | Moves money back to a customer |

3. **Owner-only structural actions** — never expressible as a permission string, never delegable: `ownership.transfer`, `organization.close`, `organization.delete`.
4. **Owner templates** (`company_owner`, `hotel_owner`, `service_provider_owner`) are immutable and system-only.

`platform.audit.view`'s removal does not affect delegation: it was never in the non-delegable list and never in any role.

---

## 10. Final counts and the two open sub-items

| Family | Canonical count |
|---|---:|
| Platform | **37** |
| Organization | **48** |
| **Total** | **85** |

Recomputed from the canonical list, counting a key once per organization type it may be held in:

| Org type | Keys available | Composition |
|---|---:|---|
| Company | 37 | 16 open to all types + 7 company-or-hotel + 14 company-only |
| Hotel | 34 | 16 open to all types + 7 company-or-hotel + 11 hotel-only |
| Service provider | 16 | structural keys only (no provider service catalogue yet — P-09) |

### Delta summary

| Action | Count | Keys |
|---|---:|---|
| Kept unchanged | 76 | every key present in **both** sources, minus the ones listed below |
| Retained from a single source | 9 | Docs-only: `platform.settings.manage`, `properties.view`, `properties.create`, `properties.update`, `properties.publish`, `settlements.accounts.manage`. Code-only: `platform.visa_requests.assign`, `platform.visa_requests.decide`, `visa_requests.review` |
| **Removed** | 3 | `platform.audit.view`, `platform.visa_requests.update_status`, `org.profile.publish` |
| **Renamed** | 0 | No rename is recommended (P-13) |
| **Description changed** | 1 | `platform.visa_requests.review` |

**Arithmetic check:** 76 shared + 9 single-source = 85 canonical keys; equivalently 88 (union) − 3 (removals) = 85. Of the 8 documented-but-missing keys, 6 are retained and 2 are removed; of the 4 undocumented keys, 3 are promoted and 1 is superseded.

### Open sub-items requiring a decision

| ID | Question | Recommendation |
|---|---|---|
| P-09 | Should `bookings.*` / `customers.view` be open to `service_provider` at launch? | **No.** Keep the implementation's `['company','hotel']` restriction until the service-provider service catalogue is defined, and record it as a deliberate narrowing in the matrix. Widening later is additive and needs no migration |
| P-14 | Sensitivity value spelling | Normalise `access_control` → `access-control` when the seeder is next touched |

---

## 11. What this means for the next implementation step

No code change was made. When `G0.2` is approved, the concrete work is a **seeder-only change** plus documentation corrections:

1. Add the 9 retained single-source keys, remove the 3, update the `review` description.
2. Add `properties.` to the `scopeRequirement()` prefix list as a `property` scope, alongside `rooms.` and `rates.`.
3. Add `settlements.accounts.manage` to `config/authorization.php` `non_delegable`.
4. Correct `ROLE-PERMISSION-SCOPE-MATRIX.md` §3 header, §9 row 1, §9 note, the phantom feature references and the `org.delete` row.
5. Re-derive owner templates from the updated catalogue (the seeder already computes `company_owner`/`hotel_owner`/`service_provider_owner` from `organizationKeysFor()`).

No migration is required: the permission table already stores `family`, `domain`, `sensitivity`, `description`, `scope_requirement` and `allowed_org_types`.
