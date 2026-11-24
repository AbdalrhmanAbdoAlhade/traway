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
| `confirmed` | Allow | **Allow** with `tour_bookings.attendance` only for the actual scheduled tour session and within the provider's departure/resource scope | **Allow** with `tour_bookings.complete` only after the scheduled tour date/time is reached and the required fulfilment/attendance conditions pass | **Allow** with the dedicated `tour_bookings.cancel` permission, only when the persisted tour cancellation policy disclosed at purchase permits cancellation and the booking is in an eligible state (BR-073/BR-077) | **Not approved**; no direct refund execution | Deny |
| `completed` | Allow | Deny | Deny | **Not approved**; platform refund workflow only unless separately authorized | Deny |
| `cancelled` | Allow | Deny | Deny | View existing refund status only | Deny |
| `refunded` | Allow | Deny | Deny | View only | Deny |
| `expired` | Allow | Deny | Deny | View only | Deny |

A confirmed tour booking remains readable under the original tour/departure/resource scope. During suspension, an authorized provider may record attendance with `tour_bookings.attendance` and mark the booking `completed` with `tour_bookings.complete`, but only for the actual scheduled session, once its scheduled date/time has been reached, and after the configured fulfilment/attendance requirements pass. The provider may cancel it only through `tour_bookings.cancel` when the persisted policy version/terms applicable to that booking permit cancellation. These action-specific permissions do not grant arbitrary status updates, booking-field edits, price/payment changes or refund execution. The server must enforce valid state transitions and prevent attendance/completion before the scheduled session.

## 5. Visas — proposed allowlist

Visa application stages are service-specific; the documentation does not yet provide a complete canonical list of persisted application states. Therefore this matrix authorizes only the review actions already established by G0-DEC-04 and BR-095.

| Existing application condition | View application/documents | Review submitted documents | Request missing/replacement documents and enter `Documents Required` | Change other customer application stages | Final approve/reject |
|---|---:|---:|---:|---:|---:|
| Submitted / awaiting partner review | Allow within assigned request or authorized team queue | Allow with `visa_requests.review` | Allow with `visa_requests.request_documents` only when a mandatory document is missing/rejected | Deny | Deny — platform only |
| Partner review in progress | Allow within assigned request or authorized team queue | Allow with `visa_requests.review` | Allow with `visa_requests.request_documents` only when a mandatory document is missing/rejected | Deny | Deny — platform only |
| Awaiting customer documents | Allow within assigned request or authorized team queue | Allow with `visa_requests.review` after customer resubmission | Allow only for a new, valid missing/replacement-document request; do not duplicate an outstanding request | Deny | Deny — platform only |
| Submitted to / under platform decision | Allow only if the partner's existing access policy still permits the record | Additional review only if explicitly requested by platform workflow | Deny; partner cannot return a request from platform decision to customer-document collection | Deny | Deny — platform only |
| Approved / rejected / closed | Allow within existing scope | Deny unless a post-decision document correction workflow is separately approved | Deny | Deny | Deny — platform only |

`visa_requests.request_documents` is a distinct partner permission: it may issue a missing/replacement-document request and transition an eligible pre-existing request to `Documents Required`, but only when at least one mandatory document is missing or rejected under BR-095. It must not be used for arbitrary stage changes or duplicate an outstanding request. The action is limited to an assigned request or explicitly authorized team queue and remains subject to tenant, service, assignment and pre-suspension cutoff checks. Newly uploaded documents are reviewed with `visa_requests.review`. No other partner stage transition is implied.

No provider-side visa action may approve or reject an application, override a platform decision, or move an application into a platform-only decision state. The final decision remains restricted to authorized platform staff with `platform.visa_requests.decide`.

## 6. Cancellation and refund boundary

The current catalog's `bookings.cancel` and `bookings.refund` are too broad to serve as the suspension allowlist by themselves.

Proposed safe default:
- A provider may view cancellation/refund status on an existing record.
- **Owner decision (2026-11-24):** a hotel provider may cancel an existing confirmed booking directly when it holds `bookings.cancel` and the persisted rate policy says cancellation is eligible. The action must be recorded as a cancellation transition; it is not a generic booking update.
- **Owner decision (2026-11-24):** a tour provider may directly cancel an existing confirmed booking during suspension with the dedicated `tour_bookings.cancel` permission, only when the persisted tour policy disclosed at purchase permits cancellation and the booking state is eligible. This is a cancellation transition, not a generic update.
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

The product direction Q-57 is approved. The owner has approved direct hotel-booking cancellation under §3, direct tour-booking cancellation under §4, and action-specific tour attendance/completion under §4. This action matrix remains partially pending for other service actions.

The owner must still decide:
- whether hotel providers may initiate a refund request, and whether that is limited to cancellations/platform-approved cases;
- whether tour providers may initiate a separate refund request, and under which policy/state conditions;
- any partner-stage transition other than the specifically approved `Documents Required` transition.

Until those decisions are approved, the conservative defaults in the tables apply: read existing records; permit only already-defined operational actions; deny ambiguous mutations.
