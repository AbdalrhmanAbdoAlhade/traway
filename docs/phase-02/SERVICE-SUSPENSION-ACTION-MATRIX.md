# Suspended Service — Existing-Record Action Matrix

**Project:** Traway  
**Status:** Proposed backend authorization contract — owner approval required for the marked policy choices  
**Date:** 2026-11-24  
**Decision basis:** Q-54 owner decision A+B, Q-56 hierarchical scopes, Q-57 existing-record continuity

## 1. Purpose

This matrix defines the narrow provider-side actions that may remain available after Traway suspends or disables one service for an organization. It does not grant a general write bypass and does not change the ordinary active-service workflow.

The matrix applies only to records that:
1. were created before the service suspension/disable effective timestamp;
2. belong to the active organization;
3. resolve to the original service from persisted server-side data;
4. remain inside the member's permission, service, property/resource and assignment scopes; and
5. are in a state that permits the specific transition under the service's business rules.

A missing effective timestamp, missing service identity, missing scope, or ambiguous record state fails closed.

## 2. Global rules while a service is suspended or disabled

| Operation | Result | Reason |
|---|---|---|
| List/view pre-existing records | Allow, subject to all normal scopes | Customer continuity |
| Download/view documents already attached to an authorized record | Allow only if the corresponding record/document permission and scope pass | No new intake |
| Create booking, application, order or other new commitment | Deny | New intake is blocked |
| Create service resources, room types, tours, departures or offers | Deny | No new inventory/commitment |
| Publish, republish, enable availability or reopen sales | Deny | Service remains unavailable |
| Change price, commission, capacity, dates or terms for future sale | Deny | Would create or alter new commitments |
| Generic update of an existing record | Deny | No blanket `bookings.update` bypass |
| Execute an action not listed below | Deny | Explicit allowlist only |
| Platform intervention | Evaluate platform permission and platform workflow independently | Platform authority is not inherited by provider |

The existing-record exception must not treat the service as enabled for new intake. It is a separate authorization intent such as `existing_record_servicing`, derived from the persisted record and service lifecycle.

## 3. Hotels — proposed allowlist

The canonical booking states currently documented are `pending_payment`, `confirmed`, `completed`, `cancelled`, `refunded`, and `expired` (BR-076). Hotel actions below are additionally subject to the stay dates, rate cancellation terms, property scope and existing permissions.

| Existing booking state | View | Check-in | Check-out | Provider cancellation | Refund action | Other edits |
|---|---:|---:|---:|---|---|---|
| `pending_payment` | Allow | Deny | Deny | Deny | Deny | Deny |
| `confirmed` | Allow | Allow only during valid check-in window and with `bookings.checkin` | Deny until check-in/eligible stay completion | **Allow** with the dedicated `bookings.cancel` permission, only when the persisted hotel rate policy permits cancellation and the booking is in an eligible state | **Not yet approved**; no direct refund execution | Deny |
| `completed` | Allow | Deny | Allow only if the recorded stay workflow permits a delayed checkout correction and `bookings.checkout` is held; otherwise deny | Deny | **Not yet approved**; platform refund workflow only unless separately authorized | Deny |
| `cancelled` | Allow | Deny | Deny | Deny | View existing refund status only | Deny |
| `refunded` | Allow | Deny | Deny | Deny | View only | Deny |
| `expired` | Allow | Deny | Deny | Deny | View only | Deny |

Check-in/check-out are operational fulfilment actions, not permission to edit arbitrary booking fields. They must not change price, guest identity, property, dates or payment details. Any exceptional correction goes through a separately authorized platform process.

## 4. Tours — proposed allowlist

Tour catalogue lifecycle (`draft`, `in_review`, `published`, `documents_required`, `rejected`, `expired`) is distinct from the customer booking lifecycle. Only published tours are saleable (BR-069/BR-090). Existing confirmed bookings must remain accessible after delisting (BR-094).

| Existing booking state | View | Fulfilment/status mutation | Provider cancellation | Refund action | Other edits |
|---|---:|---|---|---|---|
| `pending_payment` | Allow | Deny | Deny | Deny | Deny |
| `confirmed` | Allow | **Not yet approved**: no partner booking-completion/attendance permission is currently specified in the catalog | **Not yet approved**; must follow the exact tour policy disclosed at purchase (BR-073/BR-077) | **Not yet approved**; no direct refund execution | Deny |
| `completed` | Allow | Deny | Deny | **Not yet approved**; platform refund workflow only unless separately authorized | Deny |
| `cancelled` | Allow | Deny | Deny | View existing refund status only | Deny |
| `refunded` | Allow | Deny | Deny | View only | Deny |
| `expired` | Allow | Deny | Deny | View only | Deny |

A confirmed tour booking remains readable and may be fulfilled under the original tour/departure/resource scope, but the exact partner-side fulfilment transition must not be invented. Until a dedicated permission and state transition are approved, no partner-side booking status mutation is allowed while suspended.

## 5. Visas — proposed allowlist

Visa application stages are service-specific; the documentation does not yet provide a complete canonical list of persisted application states. Therefore this matrix authorizes only the review actions already established by G0-DEC-04 and BR-095.

| Existing application condition | View application/documents | Review documents / request missing or replacement documents | Change customer application stage | Final approve/reject |
|---|---:|---:|---:|---:|
| Submitted / awaiting partner review | Allow within assigned request or authorized team queue | Allow with `visa_requests.review` | Deny unless an explicitly defined partner-review transition exists | Deny — platform only |
| Partner review in progress | Allow within assigned request or authorized team queue | Allow with `visa_requests.review` | Deny unless an explicitly defined partner-review transition exists | Deny — platform only |
| Awaiting customer documents | Allow within assigned request or authorized team queue | Allow to review newly submitted documents and request missing/replacement documents | Deny unless an explicitly defined partner-review transition exists | Deny — platform only |
| Submitted to / under platform decision | Allow only if the partner's existing access policy still permits the record | No partner decision action; additional review only if explicitly requested by platform workflow | Deny | Deny — platform only |
| Approved / rejected / closed | Allow within existing scope | Deny unless a post-decision document correction workflow is separately approved | Deny | Deny — platform only |

No provider-side visa action may approve or reject an application, override a platform decision, or move an application into a platform-only decision state. The final decision remains restricted to authorized platform staff with `platform.visa_requests.decide`.

## 6. Cancellation and refund boundary

The current catalog's `bookings.cancel` and `bookings.refund` are too broad to serve as the suspension allowlist by themselves.

Proposed safe default:
- A provider may view cancellation/refund status on an existing record.
- **Owner decision (2026-11-24):** a hotel provider may cancel an existing confirmed booking directly when it holds `bookings.cancel` and the persisted rate policy says cancellation is eligible. The action must be recorded as a cancellation transition; it is not a generic booking update.
- For Tours, provider cancellation remains unapproved until the tour-specific policy and transition rules are confirmed.
- A provider may submit a refund request only after the owner explicitly approves that action for the service, record state and applicable policy.
- A provider must not execute a payment refund or mark a booking refunded.
- Payment capture/refund confirmation and final financial state remain controlled by the platform/payment workflow.
- A cancellation is allowed only when the persisted service policy says it is eligible; the client cannot supply or override eligibility.

A provider-side hotel cancellation does not grant permission to execute a payment refund, alter the refund amount, or mark the booking as `refunded`. Refund calculation and execution remain in the platform/payment workflow under BR-077–BR-079 and DEC-10.

## 7. Required implementation contract

The authorization layer should expose distinct intents rather than infer intent from a generic update endpoint:

- `new_intake`: requires service status enabled; always denied while suspended/disabled.
- `existing_record_read`: may pass for a pre-cutoff record if all ordinary tenant/service/resource/assignment checks pass.
- `existing_record_action`: requires a specific action key, allowed record state, pre-cutoff record and all ordinary scopes.
- `platform_intervention`: evaluated only through platform permissions and platform workflow.

The server derives service, organization, record state and creation timestamp from persisted models. It never trusts a client-supplied service, status, cutoff or intent flag. Collection queries, counts, reports and exports use the same existing-record filter. No stale membership scope can override a suspended service's allowlist.

## 8. Tests required before release

1. Suspension blocks every new booking/application/resource/publish/availability action.
2. Pre-cutoff existing records remain visible only to authorized tenant members.
3. Post-cutoff records cannot be serviced through the exception.
4. Hotel check-in/out require the matching permission, property scope and valid state/time.
5. Hotel arbitrary booking edits remain denied.
6. Tour booking mutations remain denied until a dedicated fulfilment action is approved and implemented.
7. Visa document review remains scoped to assigned/team-queue requests.
8. Provider cannot approve/reject visas or issue refunds.
9. Cross-tenant, wrong-service, wrong-property, unassigned and missing-scope records are denied.
10. Re-enabling a service restores normal new-intake authorization prospectively; it does not retroactively broaden employee scopes.

## 9. Owner decisions still required

The product direction Q-57 is approved. The owner has additionally approved direct hotel-booking cancellation under the conditions in §3. This action matrix remains partially pending for other service actions.

The owner must still decide:
- whether hotel providers may initiate a refund request, and whether that is limited to cancellations/platform-approved cases;
- whether tour providers may mark attendance/completion, and which booking states permit that transition;
- whether tour providers may initiate cancellation/refund requests, subject to the policy disclosed at purchase;
- the exact visa partner-review states and any explicit partner-stage transitions before a request enters platform decision.

Until those decisions are approved, the conservative defaults in the tables apply: read existing records; permit only already-defined operational actions; deny ambiguous mutations.
