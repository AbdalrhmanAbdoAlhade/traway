# Authorization Data Model & Enforcement Design

**Project:** Traway  
**Status:** Proposed implementation design — Gate 0A approval required  
**Date:** 2026-11-24  
**Depends on:** G0-DEC-02, G0-DEC-03, G0-DEC-05, DEC-11..19, `SERVICE-AWARE-PERMISSION-CATALOG.md`

This document translates the approved multi-service organization model into a proposed persistence and enforcement design. It is not an implementation record. No migration, model, policy, Seeder or test is changed by this document.

---

## 1. Existing code baseline (verified on main)

The current schema/code already contains:

- `organizations` with `type` and `status`, but no organization-enabled-service relation.
- `memberships` for the user's tenant membership.
- `membership_service_scopes` for service values assigned to a membership.
- `membership_hotel_scopes` for hotel property scopes.
- `permissions.allowed_org_types` and `permissions.scope_requirement`, but no permission-to-service applicability metadata.
- `Booking` with `service`, `organization_id`, `hotel_id`, and `assigned_membership_id`.
- `Tour` and `VisaApplication` with organization ownership; visa applications also have `assigned_membership_id`.

Verified authorization behavior that conflicts with the approved fail-closed model:

1. `AuthorizationService::withinServiceScope()` returns true when a membership has no service scopes.
2. `Membership::isRestrictedToHotel()` returns true when no hotel scopes exist.
3. `AuthorizationService::withinHotelScope()` applies hotel scope only when organization type is `hotel`; a multi-service `service_provider` organization bypasses this check.
4. `withinServiceScope()` only interprets `scope_requirement === 'service'`; catalog values such as `property`, `service_resource` and `service_assignment` are not enforced by that method.
5. Organization service enablement is not represented separately from employee service scopes.

These are source observations, not proposed behavior.

---

## 2. Required authorization dimensions

Every organization-side request must pass all applicable dimensions:

1. **Tenant membership:** active user, active membership and active organization.
2. **Permission:** effective role/direct permission in that organization.
3. **Organization eligibility:** the permission allows the organization's type.
4. **Organization service enablement:** the organization has the service enabled by Traway.
5. **Membership service scope:** a non-owner employee is explicitly scoped to the requested service.
6. **Resource ownership:** the target record belongs to the active organization.
7. **Resource/property scope:** where the service requires a narrower subset, the membership is scoped to that resource/property.
8. **Assignment/workflow:** assigned-only queues and protected state transitions are enforced.
9. **Delegation:** the grantor cannot grant more than they hold or bypass non-delegable/owner-only rules.

A pass at one dimension never substitutes for another. Owner status is not a platform bypass and never exposes another organization.

### 2.1 Empty scope semantics

For non-owner memberships:

- no service scope = no access to service-scoped operations;
- no hotel property scope = no hotel property access;
- no resource scope = no access where that service explicitly requires resource scopes;
- no assignment = no access to assigned-only work.

For the protected organization owner template, access may span all resources belonging to that organization for services enabled by Traway, subject to the owner's actual permissions and business workflow restrictions.

---

## 3. Proposed persistence model

### 3.1 Organization-enabled services

Add an organization-to-service relation, proposed table:

`organization_services`

| Column | Type / rule | Purpose |
|---|---|---|
| id | primary key | Row identity |
| organization_id | FK organizations, cascade delete | Tenant |
| service | string from centrally defined service keys | Enabled service |
| status | enabled / disabled (or boolean enabled) | Platform-controlled state |
| enabled_at | nullable timestamp | Activation time |
| disabled_at | nullable timestamp | Deactivation time |
| enabled_by | nullable FK users | Platform actor |
| timestamps | standard | Lifecycle |

Unique constraint: `organization_id + service`.

Rules:
- Only authorized Traway platform staff can enable/disable services for an organization.
- Organization owners cannot add a service to their own organization.
- Disabling a service blocks new provider-side operations immediately. Existing records remain owned and readable only through an explicitly defined transition/support policy; do not silently delete scopes or records.
- The service key is a domain identifier, not a permission.
- Flights remain platform-only and must not be enabled for partner organizations in this release.
- Do not enable eSIM provider operations until Q-53 defines the provider lifecycle.

### 3.2 Membership service scopes

Keep the existing `membership_service_scopes` table. Its meaning becomes explicit:

- it is an employee's service assignment, not an organization's subscription/capability;
- every row must reference a service currently enabled for the membership's organization;
- service scopes may contain multiple rows;
- owners are not restricted by employee scope rows; their effective reach is all currently enabled services for their organization;
- for non-owners, a missing row means deny.

Validation must happen when scopes are created/updated and again during authorization. This prevents stale or forged rows from granting a disabled service.

### 3.3 Permission-to-service applicability

Add a canonical `applicable_services` JSON column (nullable JSON array) to the permissions table, or an equivalent normalized relation if the project chooses relational metadata. The decision must be made once and used by the Seeder, permission catalog and runtime authorization.

Proposed semantics:

- `null`: organization-wide structural permission, not a service operation;
- `["hotels"]`: hotel service only;
- `["tours"]`: tours service only;
- `["visas"]`: visa service only;
- `["hotels","tours"]`: generic booking permission for hotel/tour booking records only;
- platform permissions remain platform-family and cannot be granted to an organization.

Do not infer applicability from permission prefixes alone. `bookings.*` is intentionally a Hotels/Tours operation, not an alias for every row in `bookings`.

### 3.4 Resource scopes

Keep `membership_hotel_scopes`, but correct its meaning and enforcement:

- a non-owner with no hotel scope rows has no property access;
- every scoped hotel must belong to the same organization;
- hotel scope must apply to both `hotel` and multi-service `service_provider` organizations whenever the request is for the Hotels service;
- a hotel booking must match the authorized hotel's `hotel_id`, and the hotel must belong to the active organization.

For Tours:
- membership service scope + `Tour.organization_id` ownership is the baseline;
- if the provider UI needs employee-specific tour subsets, add an explicit membership-to-tour scope relation before exposing that workflow. Do not simulate it with arbitrary IDs in request context.
- a tour slot/departure is authorized through its parent tour's organization and any membership tour scope.

For Visas:
- provider/company access is limited to applications linked to that organization's own `VisaOffering`/organization;
- non-owner staff access to applications is assignment-based where the workflow is assigned-only;
- `visa_requests.view/review` permits document intake/validation only, never final approve/reject;
- final decision remains platform-only under G0-DEC-04.

For eSIM:
- no partner-side resource scope or organization permissions until Q-53 defines package ownership, order fulfillment, activation, support, usage and refund responsibilities.

Avoid a generic polymorphic resource-scope table until the UI and resource lifecycle are confirmed. Explicit service-specific scope relations are easier to constrain with foreign keys and tenant ownership.

---

## 4. Runtime enforcement contract

The authorization entry point should accept a typed context containing the service and, when applicable, the target resource/property/assignment. Missing required context must deny.

Proposed evaluation order:

1. Resolve active user, membership and active organization.
2. Load permission definition and verify it belongs to the correct family.
3. Verify effective permission and delegation boundary.
4. Verify organization type eligibility.
5. Verify the organization has the requested service enabled.
6. Verify permission applicability includes the requested service.
7. For non-owners, verify explicit membership service scope.
8. Verify resource ownership against the active organization.
9. Verify property/resource scope and assigned-membership rules.
10. Apply workflow-specific restrictions (e.g. visa final decision platform-only).
11. Return allow only if every applicable check passes.

Important:
- Organization service enablement is checked for owners and employees alike.
- Owner exception applies only to membership/resource scope, not tenant ownership, permission eligibility, service enablement or platform-only boundaries.
- Platform permissions require platform organization context; partner memberships never inherit them.
- Customer contact data is a contextual projection from an already authorized record, not a customer directory.
- Query builders/endpoints must apply tenant and service filters before pagination, aggregation, exports and counts. A controller-level final boolean is not a substitute for scoped queries.

---

## 5. Service/resource mapping

| Service | Permission family | Tenant ownership | Non-owner boundary |
|---|---|---|---|
| Hotels | `bookings.*`, `properties.*`, `rooms.*`, `rates.*`, contextual `customers.view` | Hotel/property and booking organization ownership | explicit service scope + explicit hotel property scope |
| Tours | `tours.*`, hotel/tour-specific `bookings.*`, contextual `customers.view` | Tour and booking organization ownership | explicit service scope; assignment or tour scope only where confirmed by UI/workflow |
| Visas | `visa_offerings.*`, `visa_requests.view/review`, contextual `customers.view` | Visa offering/application organization ownership | explicit service scope + assignment for assigned queues; no final decision |
| eSIM | no organization permission family approved | not yet defined | deny partner-side access until Q-53 |
| Flights | platform permission family only | platform | no partner membership access |

A provider organization may have several enabled services simultaneously. Each employee can be assigned multiple enabled service scopes, but access remains limited to records owned by that organization and any required resource/assignment scope.

---

## 6. Delegation and role templates

- `service_provider_owner` is a protected owner template; its candidate permissions are limited to permissions eligible for `service_provider`.
- Owner effective service reach is all services enabled for the organization, not all platform services.
- Employee roles are bundles only. A role assignment never bypasses the employee's service/resource scopes.
- Direct grants and custom roles must pass the same permission eligibility and delegation validation.
- A grantor cannot assign a service scope the organization does not have enabled or a scope broader than the grantor's own effective scope.
- Platform-only permissions, Flights and final visa decisions cannot be delegated to an organization employee.

---

## 7. Required negative/positive test matrix

Before release, tests must prove at least:

1. Provider A cannot view/update Provider B's hotel, tour, visa offering, application or booking.
2. Provider with Hotels enabled but no Tours enabled cannot access tour endpoints, even if a stale membership scope row exists.
3. Employee scoped to Hotels cannot access Tours in the same multi-service organization.
4. Employee scoped to Hotels + Tours can access both only with the matching permissions.
5. Employee with no service scopes cannot access any service-scoped record.
6. Hotel employee with no property scope cannot access any property.
7. Service-provider employee with Hotels scope but no property scope cannot access any hotel property.
8. A property scope referencing a hotel owned by another organization is rejected on write and denied on read.
9. Owner can access all owned properties only while Hotels is enabled and the owner holds the relevant permission.
10. Disabling a service blocks new operations for both owner and employees.
11. A visa provider employee cannot decide/approve/reject a visa application.
12. An unassigned visa employee cannot access an assigned-only application.
13. Customer contact fields are not exposed by a directory endpoint through organization `customers.view`.
14. Generic `bookings.*` does not authorize visa or eSIM records.
15. A partner membership cannot use any `platform.*` permission.
16. Missing service/resource context for a scoped permission denies.
17. Reports, exports, dashboard KPIs and aggregate counts obey identical tenant/service/resource filters.

---

## 8. Decisions still required before implementation

- Confirm the storage form for permission applicability: JSON column or normalized permission-service pivot.
- Confirm the exact service key vocabulary and whether a company/hotel organization can also be multi-service, or only `service_provider` organizations.
- Confirm provider dashboard workflows and whether tour-specific employee scopes are required.
- Resolve Q-53 eSIM provider responsibilities.
- Confirm service-disable behavior for in-flight bookings/applications and existing settlements.
- Approve the exact revised permission catalog and all scope metadata at Gate 0A.

**Implementation status:** no code, migration, Seeder, config, policy or tests were changed by this design document.


---

## 9. Existing policy and test-suite review (verified on main)

The review of the existing policy layer and authorization tests exposed additional conflicts that must be resolved as part of the implementation plan.

### 9.1 Policy context is incomplete for service-aware checks

- `TenantPolicy::allows()` forwards the caller-provided context unchanged.
- `BookingPolicy` passes the booking as `resource`, but does not pass `service`.
- `TourPolicy` and `VisaApplicationPolicy` also pass resources without an explicit service context.
- The current authorization service only reads service scope when `scope_requirement === 'service'`; it does not infer the service from the resource.

**Required change:** service identity must be derived from a trusted server-side resource mapping or supplied by a typed policy method from the persisted resource. Never trust a client-provided service string if it can disagree with the persisted record. For bookings, normalize legacy singular values (`hotel`, `tour`, `visa`, `flight`, `esim`) to the canonical service keys (`hotels`, `tours`, `visas`, etc.) through one explicit mapping. A mismatch between supplied context and resource service must deny.

### 9.2 Existing tests currently encode the opposite of approved fail-closed behavior

In `tests/Feature/Authorization/AuthorizationServiceTest.php`:
- `test_unscoped_employees_are_not_limited_by_service()` explicitly asserts that an employee with no service scope can access a service-scoped permission. This contradicts G0-DEC-05.
- `test_a_resource_from_another_tenant_is_denied()` uses `Booking::SERVICE_FLIGHT` and a generic organization `bookings.view` permission as the positive control. Flights are platform-only in the current product definition, so this fixture must be replaced with a Hotels/Tours record and a correct tenant-owned resource before the test can validate the intended rule.
- `test_service_scoped_employees_cannot_act_outside_their_services()` covers only a hotel organization and does not test a multi-service provider organization or organization-level service enablement.

In `tests/Feature/Authorization/PolicyTest.php`:
- the helper `booking()` creates flight bookings for organization tenants, and several partner booking tests rely on that fixture;
- no negative test proves that a service-provider employee with Hotels scope but no hotel property scope is denied;
- existing property-scope coverage is only for an organization of type `hotel`, not `service_provider`.

These tests must be rewritten/extended; do not merely add new tests while leaving contradictory positive expectations in place.

### 9.3 Hotel policy and legacy permission key drift

- `HotelPolicy::publish()` still calls `org.profile.publish`, while the candidate catalog removes that key and introduces `properties.publish`.
- `HotelPolicy::view/update` use generic `org.profile.*` keys for Hotel property records, while the candidate catalog defines explicit `properties.*` keys.
- `BookingPolicy::viewAny()` and `export()` have no record context; list/export query services must enforce service and resource filters before returning rows.
- `BookingPolicy::view()` uses generic `bookings.view` without ensuring the persisted booking service is Hotels or Tours.
- `VisaApplicationPolicy::viewAny()` has no assignment/resource context; assigned-only employee queues must be filtered at query level, not only at single-record policy checks.

### 9.4 Required implementation sequence

1. Freeze Gate 0A decisions for service enablement, permission applicability, service key normalization, resource scopes and service-disable behavior.
2. Add the organization-enabled-service persistence model and validation; preserve membership service scopes as employee assignment.
3. Add permission applicability metadata and update the canonical Seeder/role-template derivation.
4. Refactor authorization evaluation into explicit tenant, permission, enabled-service, membership-service, resource/property, assignment and workflow checks.
5. Update policies to derive trusted service/resource context and replace legacy property permissions with canonical keys.
6. Update query services/controllers for list, dashboard, report, aggregate and export scoping.
7. Correct empty-scope behavior and ensure owner exception applies only to membership/resource scope, never to service enablement or tenant boundaries.
8. Rewrite contradictory tests and add the complete negative/positive matrix in §7.
9. Run the full Pest suite, migration tests and Seeder idempotency checks locally before merge.

**No implementation is performed by this review document.**
