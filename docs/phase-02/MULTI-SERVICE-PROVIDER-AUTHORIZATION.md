# Multi-Service Provider Authorization Model

**Project:** Traway  
**Phase:** 2 — Functional & Business Analysis  
**Status:** Owner-approved direction; documentation updated, implementation and exact permission catalog still pending Gate 0A  
**Decision basis:** User confirmation on 2026-11-24 that one service-provider organization may operate more than one service.

---

## 1. Authoritative product rule

A single service-provider organization may be enabled for **one or more service lines at the same time**. The organization is the tenant boundary; service lines and resources are narrower authorization boundaries inside that tenant.

The provider is not required to create a separate account or organization for each service. The same organization may operate hotels, tours, visa services and eSIM, subject to platform enablement and the relevant onboarding/verification requirements.

Flights remain **platform-operated only** in the current release. A provider must not receive flight inventory, flight booking or flight-management access merely because `flights` exists in a legacy/general service-scope enum. No provider flight service is enabled until a separate product decision authorizes it.

## 2. Authorization decision

Every organization-side request must pass all applicable checks:

1. **Permission:** the membership has the exact operation permission.
2. **Organization:** the target record belongs to the active organization.
3. **Enabled service:** the target service is enabled for that organization.
4. **Membership service scope:** the member is explicitly scoped to that service; an empty scope grants no service access.
5. **Resource scope:** the target resource belongs to the organization and is within the member's assigned resources/properties.
6. **Assignment / workflow constraints:** where the workflow is assignment-based, the record is assigned to the member; business-state and platform-only workflow rules still apply.

A permission never grants a service or resource scope. A service scope never grants a permission. No identifier supplied by the client can override these checks.

## 3. Organization service enablement versus membership service scope

These are two distinct relationships and must not be conflated:

- **Organization-enabled services:** the set of service lines the platform has enabled for the organization after onboarding/approval. This is the organization's service catalogue/capability boundary.
- **Membership service scope:** the subset of those enabled services that a particular employee may access.

The organization owner may administer employees within the organization's enabled services, but cannot enable a new service for the organization unless the platform workflow grants that capability. An employee can never be assigned a service the organization does not have enabled.

An owner has organization-wide access to the organization's enabled services through the protected owner template. For non-owner memberships, no service-scope rows means no service access (G0-DEC-05). Do not interpret an empty array, null, or missing scope as all services.

## 4. Resource boundaries by service

| Service | Service-specific records | Required resource boundary | Important restriction |
|---|---|---|---|
| Hotels | Properties, rooms, rates, hotel bookings | Organization ownership plus property scope; bookings must reference a property belonging to the same organization | Apply the existing multi-property rule from G0-DEC-03. Property scope is required for non-owner staff. |
| Tours | Tours/programmes, departure slots, tour bookings | Organization ownership plus tour/resource ownership; assigned-resource restrictions where configured | A tour employee must not see hotel, visa or eSIM records without the corresponding service scope and permissions. |
| Visas | Visa offerings, visa requests and document reviews | Organization ownership, enabled visas service, offering/request relationship, and assignment scope where applicable | Provider/company staff may view and validate documents only. Final approve/reject decisions remain with authorized Traway platform staff under G0-DEC-04. |
| eSIM | Provider packages/catalogue records, customer eSIM orders and related operational records | Organization ownership, enabled eSIM service and the relevant package/order relationship | eSIM order/usage capabilities and provider API responsibilities require explicit catalog/workflow definition; do not reuse hotel/tour booking permissions by assumption. |
| Flights | Flight search, inventory and bookings | Platform scope only in the current release | No provider-side flight service or permission is granted in this release. |

The `hotels` property entity and `membership_hotel_scopes` remain the canonical hotel-property model. Their applicability must be based on the organization having an enabled Hotels service, not solely on `organizations.type = hotel`. This preserves the existing hotel-group model while allowing a multi-service provider organization to own hotel properties where the platform has enabled that capability.

## 5. Permission-family rules

The current generic `bookings.*` family is not a universal permission family for every commercial workflow.

- Keep `bookings.*` for actual bookable inventory workflows such as hotel and tour bookings, with service and resource filters applied to every query and mutation.
- Keep `visa_requests.view` and `visa_requests.review` for visa application visibility and document validation. They do not confer final decision authority.
- eSIM orders, activations and usage must use a separately defined eSIM operational permission family if partner-side operations are in scope. Do not invent or seed those keys until the lifecycle and provider responsibility are confirmed.
- `customers.view` is contextual, not a customer-directory grant. It permits only the minimum customer contact fields needed for records the member is already authorized to access. It must never expose the platform-wide customer directory, unrelated bookings, saved travellers, passport documents or payment instruments.
- Platform-wide customer directory access remains under `platform.customers.*` only.

The exact organization permission catalog must be recalculated after the service/resource matrix is approved. Therefore the previous 85-key proposal is **not an approved final count** and must not be treated as implementation-ready.

## 6. Delegation and owner ceilings

- A provider owner can assign employees only to services enabled for that organization.
- The owner may grant only permissions and resource scopes that the owner currently holds and is allowed to delegate.
- A service-scoped manager may manage employees only within the service/resource scope delegated to that manager, and only if the relevant employee/role administration permissions are held.
- No organization owner or employee may grant platform permissions, enable platform-only Flights, make final visa decisions, transfer ownership, or alter protected owner templates.
- Multiple service scopes are allowed for one membership. Resource scopes are independently constrained within each selected service.
- If a membership has several roles/direct grants, the union of permissions is still intersected with the membership's service/resource scopes; role union must never bypass those scopes.

## 7. Isolation invariants

The following are mandatory and testable:

1. Provider A cannot list, view, mutate, export or infer Provider B's records.
2. A hotel-scoped employee cannot access a sibling property outside their assigned property scope.
3. A Tours-only employee cannot access Hotels, Visas or eSIM records even if the same organization operates them.
4. A member cannot access a service that is not enabled for the organization.
5. A member cannot access a resource that belongs to another organization, even if the resource ID is known.
6. A member cannot gain access by omitting a scope parameter, sending null, an empty array, or another tenant's resource ID.
7. Customer data is returned only as a minimal projection attached to an authorized service record.
8. Platform-only flight and visa-decision actions cannot be reached through organization-side endpoints.

## 8. Dashboard and documentation implications

The Phase 1 dashboard inventory currently documents dedicated company, hotel and restricted-employee portals, but no complete multi-service provider portal. The existing role editor concepts can be reused, but the provider dashboard must expose:

- enabled service lines for the organization;
- employee service-scope assignment;
- resource/property scope assignment per service;
- service-aware navigation and queues;
- service-aware reporting and exports.

This is a **documented UI coverage gap**, not permission to invent screen IDs or claim those screens already exist. Add provider dashboard screens to the UI inventory and feature catalog when the product design is supplied/approved.

## 9. Gate 0 consequences

The previous P-09 recommendation to deny all `bookings.*` and `customers.view` permissions to service providers is superseded. It is replaced by service-aware permission eligibility and strict tenant/service/resource scoping.

Before Phase A implementation:
- revise the exact permission catalog and allowed organization/service combinations;
- decide which provider service workflows are operational at launch, especially eSIM;
- align the Seeder, role templates, role matrix and authorization configuration;
- add negative isolation tests for cross-organization, cross-service and cross-resource access.

No application code, migration, Seeder or runtime authorization behavior is changed by this documentation update.
