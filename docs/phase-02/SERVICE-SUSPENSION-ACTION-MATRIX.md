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

## 5. Visas — approved partner/platform workflow

Visa issuance itself happens outside Traway through the embassy/competent authority. Traway records workflow progress and the evidence/details of an externally issued visa; the application must never claim that Traway issued the visa.

| Workflow stage / action | Organization employee | Traway platform staff |
|---|---|---|
| View an existing request | Allow within assigned request or explicitly authorized team queue using `visa_requests.view` | Allow using `platform.visa_requests.view` |
| Review submitted/re-uploaded documents | Allow using `visa_requests.review` | Allow using `platform.visa_requests.review` |
| Request missing/replacement mandatory documents; enter `Documents Required` | Allow using `visa_requests.request_documents` only when a mandatory document is missing/rejected under BR-095 | Allow using platform visa review workflow |
| Submit a complete request to Traway for final platform review | Allow using `visa_requests.submit_for_platform_review` only when required documents pass validation and no outstanding customer-document request exists | Receive/review the request |
| Platform final review / platform workflow decision | No permission to perform this platform action | Platform-only; existing `platform.visa_requests.decide` boundary remains |
| Record actual external visa issuance | Allow using `visa_requests.record_issuance` only after the embassy/competent authority has actually issued the visa; record issuance details and attach the visa file/evidence | May view the recorded issuance within platform access |
| Send issued visa/details to the customer | Allow using `visa_requests.deliver` after issuance is recorded and required delivery data/file is present | May view delivery status; no extra platform approval required for the partner's authorized delivery |
| Arbitrary status changes, fabricated issuance, or platform final decision | Deny | Only through explicit platform permissions and valid transitions |

Partner actions remain restricted by organization ownership, enabled Visas service, membership service scope, request assignment/authorized team queue, valid state transitions and the existing-record cutoff during service suspension. During suspension, only requests created before the effective suspension time may be serviced; the same explicit permissions and checks continue to apply.

`visa_requests.request_documents`, `visa_requests.submit_for_platform_review`, `visa_requests.record_issuance` and `visa_requests.deliver` are separate permissions. Document review does not itself grant the ability to request documents, submit for platform review, record issuance or deliver a visa. The partner may not record issuance until actual external issuance has occurred. Recording issuance and delivery do not mean that Traway issued or approved the visa.

The final platform review remains distinct from actual issuance by the embassy/competent authority. Do not infer any additional partner-side status transition beyond the explicitly listed actions.

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
