# Phase 2 — Database-Blocking Decision Brief

**Project:** Traway
**Purpose:** Resolve the 10 questions that block Phase 3 (data model) before any schema work starts.
**Source:** [`OPEN-QUESTIONS.md`](./OPEN-QUESTIONS.md) §2 and §3
**Status:** Awaiting business answers — **no decision has been taken**
**Date:** 2026-11-23

---

## 1. How to use this brief

* The 10 questions are grouped into **5 answerable groups**. Answer one group at a time if you prefer.
* Each question is given a decision ID `DEC-01 … DEC-10`, mapped to its original `Q-nn`.
* Every option is described with how it works, advantages, disadvantages and its effect on the data model.
* The **technical recommendation** is a production-engineering opinion, not a business decision. It is explicitly labelled and can be overridden.
* **"Business approval required"** states exactly what only you can decide. Nothing in those rows will be implemented until you answer.
* You may answer **"defer"** for any question. Deferral is only accepted with a documented technical strategy that keeps the schema open — each question includes one.

### Answer format

Reply with one line per decision, for example:

```
DEC-01 = A
DEC-02 = B, currencies: USD + EGP + SAR
DEC-03 = A, first booking = first paid booking per account
DEC-04 = A
DEC-05 = A, 15 minutes
...
```

Anything left unanswered stays `Open` and Phase 3 does not start.

---

## 2. Summary

| Decision | Q | Topic | Group | Business approval? | Current working assumption |
|---|---|---|---|---|---|
| DEC-01 | Q-01 | Refund arithmetic / currency of the 5,120 figure | 1 — Money | **Yes** | Mock defect, USD authoritative |
| DEC-02 | Q-19 | Multi-currency scope | 1 — Money | **Yes** | USD only at launch |
| DEC-03 | Q-14 | Promo rules | 1 — Money | **Yes** | One code, first booking, percentage |
| DEC-04 | Q-25 | Reference / ID scheme | 2 — Identity | Partial (format) | Prefix + year + sequence |
| DEC-05 | Q-11 | Fare-hold duration and ownership | 2 — Identity | Partial (customer promise) | 15 min, provider-overridable |
| DEC-06 | Q-12 | Visa fee, payment and eligibility | 3 — Visa | **Yes** | Country+type price, paid in-app |
| DEC-07 | Q-13 | Visa decision lifecycle | 3 — Visa | **Yes** | Manual internal recording |
| DEC-08 | Q-23 | Hotel search inputs | 4 — Hotels & account | **Yes** (product) | Destination, dates, adults, children, rooms |
| DEC-09 | Q-21 | Account deletion and retention | 4 — Hotels & account | **Yes** (+ legal) | 14-day grace, financial data retained |
| DEC-10 | Q-29 | Refund approval and settlement execution | 5 — Finance ops | **Yes** | Super-admin approval, manual transfer |

---

# Group 1 — Money, pricing & promotions

These three decisions share one foundation: how money is stored. Answering them together avoids designing a money model three times.

## DEC-01 — Q-01: Why do the cancellation figures not reconcile with the payment total?

### Context

| Evidence | Value shown |
|---|---|
| M-024 — flight payment | Promo −$24, **total $511**, 3 passengers |
| M-042 — cancellation of the same trip | **Refund 3,605**, **fee 1,515** (implied paid 5,120) |
| M-051 — payment history | Paid $657, refunded $65 |

The same trip cannot be both 511 and 5,120 in the same currency. Three explanations are possible:

1. **Mock defect** — the cancellation screen was designed with unrelated placeholder numbers.
2. **Currency mismatch** — 5,120 is a local-currency amount (e.g. EGP) while the payment screen is USD, and the currency label is missing.
3. **Different booking** — the cancellation screen illustrates a different, more expensive itinerary.

### Why this blocks the database

* It decides whether a monetary amount is a **single scalar** or a **pair (amount + currency + FX snapshot)** attached to every quote, booking, payment and refund.
* It decides whether a **refund ledger** must reconcile to the original capture per currency, or can be computed on the fly from one total.
* If 5,120 is real, cancellation-fee rules and refund policy need a fee model (flat, percentage, carrier-specific), which is a table of its own.
* If it is a mock defect, seeding fee tables from these numbers would corrupt every future refund calculation.

### Options

**Option A — Single currency (USD); the figures are a mock defect**
* How it works: every amount is stored in one currency and in integer minor units. Refunds are computed at cancellation time from the amount actually paid minus the applicable policy fee.
* Advantages: simplest correct model; one rounding rule; reporting and reconciliation are trivial; refund arithmetic always reconciles by construction because it derives from the captured payment.
* Disadvantages: cannot represent a genuine local-currency booking; if the EGP hypothesis is true, this option hides a real requirement and a later migration is mandatory.
* Schema impact: one money representation; no FX columns; refund rows store `amount`, `fee`, `net`, all in USD.

**Option B — Booking-level currency with FX snapshot**
* How it works: each booking stores its transaction currency, the amounts in that currency, the base-currency amounts and the rate used at capture. Refunds mirror the original currency.
* Advantages: explains the 5,120 case as a non-USD booking; correct audit trail; supports market pricing without a full multi-currency ledger.
* Disadvantages: every report must convert; historical rates must be immutable; refunds must be issued in the original currency, which some gateways handle poorly; more columns and validation everywhere.
* Schema impact: currency code + FX rate + base amount on every monetary row; a rate-history source; reporting always joins to base amounts.

**Option C — Full multi-currency ledger**
* How it works: per-currency balances, wallets and settlement accounts; each transaction posts double-entry amounts in both currencies.
* Advantages: supports local charging, local payouts and partner settlements in multiple currencies.
* Disadvantages: significantly more complex; requires FX gain/loss accounting, currency-specific reconciliation and finance operations that do not exist in the design.
* Schema impact: ledger, currency balances, FX postings, settlement per currency.

**Option D — Treat the two screens as different bookings**
* How it works: no schema consequence; documentation records that M-042 is illustrative only.
* Advantages: unblocks work immediately; no speculative complexity.
* Disadvantages: leaves the fee policy undefined; the refund screen remains untrustworthy as a requirement source.
* Schema impact: same as A, plus a note that cancellation figures are non-authoritative.

### Technical recommendation

**Option A, implemented as "A+"**: single USD ledger in integer minor units, but include a `currency` code (fixed to `USD`) and a nullable `fx_rate` on monetary rows from day one. This costs nothing now, resolves all arithmetic, and avoids a destructive migration if Option B is later confirmed. Do **not** seed fee rules from 3,605 / 1,515.

### Business approval required

* Confirm which explanation is true (mock defect / currency / different booking).
* If real, state the **cancellation fee rule** (flat per booking, percentage of fare, carrier policy, or none).
* Confirm that all customer-facing prices are USD.

### If deferred

Adopt A+ and record that the cancellation figures are illustrative. Cost of change to B later: add `currency`/`fx_rate` values and backfill at a fixed rate — low, because the columns exist.

---

## DEC-02 — Q-19: Is multi-currency in scope, and in what form?

### Context

M-043 shows «العربية · دولار $» and M-053 is a settings screen with a language/currency section. Every capture displays `$`. The design therefore **implies** a currency choice but only ever proves USD.

### Why this blocks the database

Currency scope determines:

* whether prices are stored once or per currency;
* whether a **quote** can be re-used after an FX change;
* whether refunds and settlements occur in the charge currency;
* how partner dues and platform commission are reported;
* how many decimal places and rounding steps exist.

Getting this wrong is one of the most expensive schema mistakes to reverse, because money columns propagate into every domain.

### Options

**Option A — USD only**
* How it works: base currency = display currency = settlement currency = USD. The selector is hidden or shows a single option.
* Advantages: simplest; no FX source, no rate history, no conversion rounding; finance and partner settlement are trivial; fastest to launch.
* Disadvantages: cannot show local prices; conversion pressure will appear as soon as marketing targets Egypt/Gulf; hiding an existing settings row may look like a missing feature.
* Schema impact: one currency constant; still store `currency` for forward compatibility.

**Option B — USD ledger, display conversion only**
* How it works: all prices, payments and refunds are computed and charged in USD. The app converts for display using a rate captured at quote time; the charged amount and rate are stored for audit.
* Advantages: customer sees a familiar currency; ledger stays single-currency; much simpler than charging locally; the FX rate is documented per transaction.
* Disadvantages: the customer may be charged a different amount than displayed if the rate moves before capture — must be handled explicitly; card issuers may add their own FX fee; two amounts must be shown on receipts.
* Schema impact: `display_currency`, `fx_rate`, `display_amount` snapshot on quotes/bookings/payments; a rates table with history.

**Option C — Charge in the customer's currency**
* How it works: the gateway charges EGP/SAR/etc.; the ledger stores the transaction amount and currency plus the USD equivalent at capture; refunds are issued in the original currency.
* Advantages: best customer experience locally; avoids "why was I charged in dollars?"; aligns with local acquiring.
* Disadvantages: reconciliation across currencies; gateway and refund support varies; FX gain/loss appears; settlement and commission need a base-currency policy; testing multiplies.
* Schema impact: transaction currency + base currency + rate on every payment/refund; per-currency settlement; FX gain/loss reporting.

**Option D — Full multi-currency operations, including partner payouts**
* How it works: partners are settled in their local currency; platform holds currency balances; FX exposure is managed.
* Advantages: strongest partner proposition at scale.
* Disadvantages: requires treasury operations, hedging policy and finance staff that the design does not include.
* Schema impact: currency accounts, payout records per currency, FX postings.

### Technical recommendation

**Option B** if any localisation is intended, **Option A** if the launch market is USD-only. Option B keeps the accounting ledger single-currency — which is what makes reconciliation and commission simple — while still satisfying a currency selector. Avoid Option C unless the payment provider contract or local regulation requires local charging; it is the point where complexity rises sharply.

### Business approval required

* Which currencies must be **displayed** at launch?
* Which currency is the customer actually **charged** in?
* Who bears FX differences — Traway or the customer?
* In which currency are partners paid?
* Is the currency selector shipped or hidden?

### If deferred

Choose A, hide the selector, and store `currency` on money rows. Moving to B later is additive (new nullable snapshot columns + rates table). Moving to C later is a re-architecture of the ledger — so if local charging is likely within 12 months, decide now.

---

## DEC-03 — Q-14: What are the promotion rules?

### Context

M-024 applies `TRAWAY10` (−$24) inside a flight checkout. M-057 lists the same code with "10 % on the first booking", category chips (flights, hotels, tours, eSIM), conditions and an expiry countdown. BR-015 currently states one code per booking, server-validated.

### Why this blocks the database

Promotions are not a single table if they can stack, have per-user limits, per-service scope, or interact with refunds. The decision determines:

* whether the booking-to-promotion relationship is **one-to-one or many-to-many**;
* whether redemption counters must be transactionally decremented (concurrency);
* whether the discount is a **derived value** or a **stored snapshot** that survives price changes;
* how a refund reverses a discount without creating money.

### Options

**Option A — Single promo per booking, scoped, capped**
* How it works: a promotion has a type (percentage/fixed), a service scope, a validity window, a first-booking-only flag, optional global and per-user usage caps. One redemption row per booking; the discount is snapshotted on the booking.
* Advantages: matches the drawn design and BR-015; simple validation and reversal; easy to explain to support; counters are the only concurrency point.
* Disadvantages: no campaign stacking; marketing cannot combine "10 % + free baggage"; limits expressed as columns rather than a rules engine.
* Schema impact: `promotions`, `promotion_redemptions` with unique booking; discount fields on the booking; atomic usage counters.

**Option B — Stackable promotions with exclusivity groups**
* How it works: multiple promotions per booking, each in an exclusivity group, applied in a defined order with priority; the booking keeps a breakdown of each discount.
* Advantages: maximum marketing flexibility; supports campaigns, loyalty and service-specific offers simultaneously.
* Disadvantages: discount interaction is hard to reason about; refunds must reverse each component; support cannot explain a final price easily; testing matrix grows combinatorially.
* Schema impact: many-to-many booking↔promotion with per-row amount and order; exclusivity and priority tables.

**Option C — Campaign vouchers with limited inventory**
* How it works: codes belong to a campaign with a total quantity; each redemption decrements a counter atomically; no stacking, but many campaigns can exist.
* Advantages: good for flash sales and partner-funded campaigns; simple for customers; counters are a clean scaling problem with well-known solutions.
* Disadvantages: quantity contention under load; requires idempotent redemption to avoid overselling; still no stacking.
* Schema impact: campaign + code + redemption rows, atomic counter, idempotency key.

**Option D — No engine, one hard-coded code**
* How it works: the code is configuration, not data.
* Advantages: zero schema.
* Disadvantages: cannot run a second campaign without a release; cannot expire or cap; not viable for a marketing-led travel product.
* Schema impact: none.

### Technical recommendation

**Option A, modelled so Option B is additive**: keep the booking↔promotion link as its own table even though v1 enforces at most one active row (a unique constraint can be relaxed later), snapshot the discount and the code on the booking, and use an atomic counter for usage caps. This is the smallest correct model and the cheapest to extend.

Regardless of option, decide the **refund interaction**: the standard rule is that a refund reverses the paid amount only, and the discount is not paid out in cash. If a booking is cancelled, the promo redemption is released or consumed according to the campaign rule.

### Business approval required

* Can promotions stack? If yes, in which combinations?
* What exactly is "first booking" — first paid booking per account, per e-mail, per device, or per payment instrument?
* Which services can a code apply to, and does it apply to taxes and fees?
* Is a discount returned in cash on refund, or only reversed?
* Global and per-user usage limits; expiry behaviour.
* Who can create promotions — platform only, or partners too?

### If deferred

Adopt A with a separate redemption table. Cost of change to B: add rows and an ordering column — moderate but contained; no money migration.

---

# Group 2 — Identity, references & inventory holds

## DEC-04 — Q-25: What is the canonical reference scheme per entity?

### Context

Nine formats appear in the captures:

| Entity | Captures | Example |
|---|---|---|
| Flight booking | M-025 | `TRW7K2` (6-char airline-style PNR) |
| eSIM order | M-036 | `TW-58210` |
| Hotel reservation | M-064 | `HTL-90418` |
| Tour booking | M-068 | `TR-33107` |
| Visa application | M-031 | `TRW-V-2291` |
| Payment | D-03.14 | `PAY-90121` |
| Partner application | D-02.3 | `P-2291` |
| Hotel cancellations (notification) | D-05.2 | `TW-48184` |
| Hotel/other bookings | D-05.4, D-03.3 | `TW-48197`, `TW-48201` |

Two different concepts are mixed here: **internal booking references** (what Traway owns) and **external provider references** (airline PNR, hotel confirmation, eSIM provider order). An airline PNR is assigned by the carrier and **cannot** be replaced by a Traway scheme.

### Why this blocks the database

* Decides whether the primary key is a human-readable string or a surrogate, which affects every foreign key, index and API route.
* Decides how uniqueness is guaranteed under concurrency (two bookings created in the same second).
* Decides whether customer support can look up a record by the number printed on a voucher.
* Decides how external provider identifiers coexist with internal ones without collision.
* Sequential human IDs leak business volume and invite enumeration; that is a security decision, not only aesthetic.

### Options

**Option A — Surrogate internal key + separate immutable human reference per entity**
* How it works: every aggregate has a non-guessable internal key (UUIDv7 or bigint). A separate `reference` column holds the customer-facing value (`TRW-2026-000123`), unique per entity, generated atomically and never reused. Provider references (airline PNR, hotel confirmation, eSIM order) live in their own nullable columns.
* Advantages: stable keys independent of format changes; no enumeration; references can differ per entity without touching relations; provider data modelled honestly; safe for merges and imports; API can expose references without exposing primary keys.
* Disadvantages: two identifiers to reason about; every lookup by reference needs a unique index; sequence generation is a contention point; support tooling must accept both.
* Schema impact: primary key + unique `reference` per aggregate; a counter/sequence per prefix; provider-reference columns; no reference used as a foreign key.

**Option B — Human reference as primary key**
* How it works: `TRW7K2`-style strings are the primary key and foreign key everywhere.
* Advantages: one identifier; trivial lookups; readable database rows.
* Disadvantages: format changes cascade through the schema; guessable keys leak volume; provider references may collide with internal ones; renaming or merging entities is painful; bulk operations and replication dislike non-monotonic keys.
* Schema impact: string PKs/FKs across all tables.

**Option C — One global reference with a type prefix**
* How it works: a single sequence issues `X-000001` and the prefix identifies the entity type.
* Advantages: one generator; easy global search.
* Disadvantages: type is encoded in a string (easy to get wrong); per-entity sequences become impossible without parsing; support confusion when prefixes change; analytics must parse strings.
* Schema impact: one global counter; type inferred from prefix.

**Option D — Keep the nine drawn formats as-is**
* How it works: no canonical scheme; each screen keeps its mock format.
* Advantages: none beyond visual fidelity to the mock.
* Disadvantages: cannot guarantee uniqueness; no cross-portal consistency; reporting and support break; violates BR-113.
* Schema impact: undefined.

### Technical recommendation

**Option A.** Use a non-guessable internal key (UUIDv7 if records may be created across services or exposed publicly; bigint if single-database and internal only), a per-entity human reference built from `PREFIX-YEAR-SEQUENCE`, and separate columns for provider references. Reference format is a string, so the exact prefix/length can be finalised later without a schema change — only the generator changes.

Concretely, treat the airline PNR as an **attribute of the flight ticket**, not as the booking identifier. The customer may see the PNR on the boarding pass while the booking reference remains Traway's.

### Business approval required

* The customer-facing format only: prefixes per product, whether the year is included, total length, and whether sequential numbers are acceptable (they reveal volume).
* Whether one reference is shown across all channels or each product keeps its own prefix (recommended).
* Whether references must be speakable/typable over the phone (affects alphabet: exclude O/0, I/1).

Technical structure (surrogate key + separate reference) needs no business approval but will be adopted unless you object.

### If deferred

Adopt A now. The format decision can be deferred indefinitely because it is a value generator, not a schema shape.

---

## DEC-05 — Q-11: How long is the fare hold, and who controls it?

### Context

M-024 shows a **14:32** countdown on the payment screen. No other screen states a hold, and no hold is shown for hotels, tours or eSIM. The 14:32 suggests a 15-minute window that had already run for 28 seconds.

### Why this blocks the database

* A hold is a **stateful reservation of inventory** that must be persisted, expired reliably, and released idempotently; otherwise inventory leaks.
* It decides whether the quoted price is bound to a **quote record** that can expire, or recomputed from live prices.
* It decides how bookings in `pending_payment` are distinguished from abandoned carts, and how expiry interacts with payment callbacks that arrive late.
* It affects concurrency design: two customers competing for the last seat need an atomic decrement, not a read-then-write.

### Options

**Option A — Platform-wide fixed TTL (e.g. 15 minutes)**
* How it works: entering checkout creates a hold row with `expires_at = now + config`. A scheduled job releases expired holds. The customer sees the countdown.
* Advantages: predictable customer experience; one config; simple operations; easy to test; consistent across suppliers.
* Disadvantages: ignores supplier rules (some fares hold for 24 h, some not at all); may hold inventory longer than the supplier allows, causing a late failure; the 15-minute promise can be false if the supplier expires first.
* Schema impact: holds table with expiry and status; config value; expiry worker.

**Option B — Supplier-driven TTL**
* How it works: the hold is created at the supplier; Traway stores the supplier's `expires_at` and hold reference. The countdown reflects the supplier's deadline.
* Advantages: truthful; never promises more than the supplier guarantees; avoids overselling.
* Disadvantages: TTL varies per fare and may be very short (2–5 min), harming conversion; supplier APIs may not support holds; more failure paths.
* Schema impact: `provider_expires_at`, `provider_hold_ref`, `hold_type`; per-supplier integration.

**Option C — No hard hold, revalidate at payment**
* How it works: no reservation; the price is re-checked when the customer pays; if sold out, the payment is refused.
* Advantages: no expiry worker; no inventory locking; simplest.
* Disadvantages: worst customer experience (pay, then fail); refunds/voids on failure; conversion loss; cannot show a meaningful countdown.
* Schema impact: quote snapshot only; no hold table.

**Option D — Hybrid: hard hold where the supplier supports it, soft quote otherwise**
* How it works: `hold_type = supplier | soft`; always persist `expires_at`; supplier holds are released through the provider, soft holds simply invalidate the quote. Payment validates the hold state before capture.
* Advantages: truthful where possible, flexible elsewhere; one interface for the rest of the system; degradation is explicit.
* Disadvantages: two behaviours to test; product must decide what the countdown means in soft mode; slightly more state.
* Schema impact: one holds table with a type discriminator and an optional provider reference — superset of A and B.

### Technical recommendation

**Option D** (a superset that contains A and B). Always persist an `expires_at`, always validate at capture, always release idempotently. If supplier integration is not ready at launch, the same table runs in soft mode with a 15-minute TTL — no redesign later.

Two rules matter more than the duration: (1) the **client timer is display only** — the server decides expiry; (2) a payment callback that arrives after expiry must either be accepted against the held price or refunded automatically, and that policy must be written down.

### Business approval required

* The customer-facing hold duration (is 15 minutes the promise?).
* What the customer is told when the price changes after expiry.
* Whether a late payment after expiry is honoured or automatically refunded.
* Whether hotels and tours also get holds (the design only shows one for flights).

### If deferred

Adopt D with `hold_type = soft` and 15 minutes. Supplier-specific TTLs can be added as data later. Cost of change: low.

---

# Group 3 — Visa domain

## DEC-06 — Q-12: How are visa fees priced, collected and constrained?

### Context

M-029 shows the fee as a literal placeholder «[حسب لوحة التحكم]» — proving the fee is **administered**, not hard-coded. D-03.9 contains the administration: 48 countries, visa types per country with prices (tourist 30 d $599, business 3–5 y $156, residence 10–15 y $290), and required documents with mandatory/optional toggles. M-030/M-031 show document upload and submission but **no payment step**. M-032 shows multiple applications per user (UAE complete, another in progress).

### Why this blocks the database

* Determines the **dimensions of the price table**: country, visa type, and possibly applicant nationality or residence. Each extra dimension changes uniqueness and lookup.
* Determines whether a visa application has a **payment** at all, and therefore whether refunds, revenue recognition and settlement apply to visas.
* Determines whether **eligibility** is a separate rule set (nationality → allowed types) or merely a catalogue filter.
* Determines whether the fee is **snapshotted** on the application (prices change; an application submitted in March must keep its March price).
* If the fee is paid to a government/embassy rather than to Traway, the money model is fundamentally different (pass-through vs revenue).

### Options

**Option A — Country × visa type price, paid in-app at submission**
* How it works: one active price per country+type, effective-dated; the application snapshots the fee and currency; payment is captured before submission; refunds follow the application outcome policy.
* Advantages: consistent with the catalogue screen; simple lookup; snapshot protects history; enables in-app revenue and refunds.
* Disadvantages: cannot express nationality-based pricing (common for visas); government fee changes require admin action; if the embassy actually collects the fee, Traway is collecting money it must remit.
* Schema impact: price table keyed by country+type with validity dates; application stores fee snapshot; payment linked to application.

**Option B — Country × type × nationality/residence matrix with eligibility flags**
* How it works: price and eligibility rows per nationality (or residence), with a fallback default; lookup resolves the most specific row.
* Advantages: models real visa pricing; supports "Egyptians pay X, Saudis pay Y"; eligibility becomes data.
* Disadvantages: the matrix grows quickly (48 countries × types × 200 nationalities); admin UI becomes complex; fallback rules must be explicit or pricing becomes unpredictable.
* Schema impact: additional dimension + fallback precedence; eligibility table; audit on price changes.

**Option C — No in-app payment; fee paid externally**
* How it works: the app shows the fee and instructions; the customer pays the embassy/partner directly; the application records only the amount for reference.
* Advantages: avoids money transmission and refunds; matches the current screens (no payment step drawn); simplest compliance position.
* Disadvantages: no revenue capture; no payment state; reconciliation is manual; the "fee" display may confuse customers about what they are paying.
* Schema impact: fee fields on the application/type only; no payment link; no refund logic.

**Option D — Split service fee + government fee (pass-through)**
* How it works: two line items — Traway's service fee (revenue) and the government fee (liability to remit) — potentially paid at different times.
* Advantages: accurate accounting; transparent to the customer; accommodates embassies that require direct payment.
* Disadvantages: two payment flows; liability tracking; more complex refunds (service fee may be non-refundable while government fee is).
* Schema impact: fee components with type (service/government), separate liability tracking, refund rules per component.

### Technical recommendation

**Option A for launch, modelled with Option B's dimension present but nullable**: a price row carries country, type, optional nationality/residence and effective dates, with a defined fallback to the nationality-agnostic row. This means enabling nationality pricing later is a data change, not a migration. If the fee is actually collected by the embassy, choose **C** instead — do not build in-app payment for money Traway does not receive.

The fee must be **snapshotted** on the application regardless of option, because catalogue prices change and an application is a historical contract.

### Business approval required

* Does Traway collect the visa fee in-app, or is it paid to the embassy/partner? (This is the biggest fork.)
* Is the fee composed of a service fee plus a government fee, and is either refundable on rejection?
* Does price or eligibility depend on the applicant's nationality or residence?
* Who sets prices — platform only, or partner companies too?
* What happens to the fee if the application is rejected?

### If deferred

If undecided, build **C** (no in-app payment) because it is the least committal, with a nullable payment link on the application. Moving to A later adds a payment relation but does not reshape the application table. Building A first and discovering the fee is external would require removing money flows — more expensive.

---

## DEC-07 — Q-13: What happens after documents are accepted?

### Context

D-04.6 shows a company reviewer accepting/rejecting documents with a progress indicator ("1 of 5 completed") and a queue. M-031 tells the customer "review within 24–48 h". M-032 shows a document tracker but **no decision status**. D-03.2 lists notification types including incomplete documents, but no "visa approved" or "visa rejected" event is legible. The design stops precisely at the hand-off to the authority.

### Why this blocks the database

* A visa application is a **long-running, externally dependent process**. Without defined states there is no status machine, no history table, no SLA timer and no notification trigger.
* It decides whether Traway stores an **external application reference** (embassy/e-visa portal id) and whether status is pushed, polled or entered manually.
* It decides what the customer sees: silence, intermediate states, or a final decision only.
* It decides whether rejection reasons, appeals, re-submission and partial approvals exist as first-class data.
* If the process is external, the schema must separate **Traway's workflow state** from **the authority's decision** — conflating them is a common and costly modelling error.

### Options

**Option A — Manual internal workflow with status history**
* How it works: canonical states (`draft → submitted → documents_verified → sent_to_authority → approved | rejected | withdrawn`), each transition stored in an append-only history with actor and timestamp; the company records the authority's outcome manually; an optional external reference field is stored.
* Advantages: matches the drawn screens; auditable; supports SLAs and notifications; no external integration dependency; works for every country immediately.
* Disadvantages: relies on staff discipline; statuses can lag reality; no automation; external reference is free text unless validated.
* Schema impact: status + status-history table; external reference; decision fields (date, reason, attachment).

**Option B — Direct integration with e-visa/authority APIs**
* How it works: per-country adapters submit applications and receive status updates; the external id and raw payload are stored; a mapping layer translates external states to internal ones.
* Advantages: fastest status updates; fewer manual errors; scalable across countries.
* Disadvantages: most countries have no usable API; per-country certification and maintenance; credentials and personal data handling; partial coverage still needs the manual path.
* Schema impact: integration tables, external ids, payload storage, state mapping per provider.

**Option C — Binary recorded outcome only**
* How it works: the application is approved or rejected with a free-text note; no intermediate states, no history.
* Advantages: minimal.
* Disadvantages: no audit, no SLA, no customer visibility, no support answers, no analytics; cannot distinguish "documents accepted" from "sent" from "decision pending".
* Schema impact: two columns on the application.

**Option D — Hybrid: internal workflow now, adapter interface for later**
* How it works: Option A as the canonical model, with a nullable `external_provider` + `external_reference` and a documented adapter contract so Option B can be added per country without changing the core.
* Advantages: ships immediately; keeps the door open; one customer-facing state machine regardless of how the outcome arrives.
* Disadvantages: some duplication when an integration is added; requires discipline to keep internal and external states separate.
* Schema impact: A's tables plus provider/reference columns on the application and a per-provider mapping table when needed.

### Technical recommendation

**Option D.** Model Traway's workflow as canonical and treat the authority's status as an external fact with its own reference and timestamps. Never let an embassy status enum become the application's primary status column. Store rejection reasons as structured data (reason code + note) rather than free text alone, because customers will ask and support will need to report.

Also define **who may transition** each state: document acceptance is a company action; the decision is recorded by the company (or the platform, if Traway submits centrally). This is a permissions decision as much as a schema one.

### Business approval required

* Who submits to the authority — the partner company, Traway staff, or the customer?
* Who records the final decision, and is supporting evidence (approval letter/PDF) stored?
* Which intermediate states may the customer see? (Recommendation: show "under review" and the final outcome, not internal document mechanics.)
* Are rejection reasons shown to the customer? Are re-applications allowed?
* Is there an SLA commitment, and what happens when it is breached?
* Do visas expire, and must the system track expiry and renewal?

### If deferred

Adopt D with manual recording and only these customer-visible states: `submitted`, `under_review`, `approved`, `rejected`, `cancelled`. Internal document states stay internal. This is the minimum that supports notifications and support without pretending to integrate.

---

# Group 4 — Hotels & account lifecycle

## DEC-08 — Q-23: What inputs does hotel search take?

### Context

The corpus contains hotel **results** (M-059: "Dubai, 11–4 Dec, 2 adults", 186 properties), filters (M-060), map (M-061), detail (M-062), booking (M-063) and confirmation (M-064) — but **no hotel search form**. Home (M-014) has a hotel tab whose panel is not visible in the capture, and the results screen shows only a summary line.

### Why this blocks the database

* Hotel inventory is date-based and occupancy-based. Without the input dimensions there is no way to design the availability model or its indexes.
* Children's ages change pricing and room capacity; if ages are not captured, the occupancy model cannot enforce limits.
* If nationality/residence affects price or eligibility, the search request and the rate model need that dimension.
* The search input shape determines the query pattern — and therefore whether availability is computed from a per-date inventory table or a rate/availability cache.

### Options

**Option A — Destination + dates + adults + children + rooms (no ages)**
* How it works: the standard OTA search; children are a count only.
* Advantages: familiar; simple query; matches most budget booking flows.
* Disadvantages: cannot apply child pricing or age-based occupancy; properties with strict child policies cannot be filtered accurately; support disputes at check-in.
* Schema impact: search criteria on the quote; occupancy counts on booking; no child-age storage.

**Option B — Option A + children's ages**
* How it works: each child carries an age at check-in; rates and capacity resolve by age band.
* Advantages: correct pricing and capacity; supports family products; fewer check-in disputes; required by many hotel APIs.
* Disadvantages: more input friction; age bands must be configured per property/rate; validation complexity.
* Schema impact: child rows with age on the search/quote/booking; age-band rate rules.

**Option C — Option B + nationality/residence**
* How it works: the guest's country is part of the search and may change rates or eligibility (common in the Gulf).
* Advantages: supports resident-only rates and local pricing.
* Disadvantages: extra dimension on rates; caching fragments; user friction; privacy considerations.
* Schema impact: rate eligibility by country; nationality captured on the quote/booking.

**Option D — No dedicated search form; reuse the Home hotel panel only**
* How it works: destination, dates and guests are entered in the Home tab's hotel panel; there is no separate search screen.
* Advantages: matches the evidence (no form supplied); fewer screens; consistent with the flight panel pattern.
* Disadvantages: less room for hotel-specific inputs (rooms, children ages, nationality); harder to deep-link a search.
* Schema impact: none beyond A/B; the query contract still needs the same fields.

### Technical recommendation

**Option B with Option D as the entry point**: reuse the Home panel for destination/dates/guests, add rooms and children's ages, and store `lat/lng` on hotels so the map (M-061) can query by viewport. Add nationality later only if resident rates are confirmed — keep the rate table able to hold a country condition, but leave it null at launch.

The critical schema consequence: availability must be modelled **per room type per night**, not per hotel, and the quote must record the exact occupancy and ages it was priced for. Without that, the booking screen cannot guarantee the total shown in M-063.

### Business approval required

* The exact search inputs the product will ship (destination, dates, guests, rooms, children ages, nationality).
* Child policy: at what age does a child become an adult, and do children need ages at search time?
* Are there resident-only or nationality-based rates?
* Is a separate hotel search screen required, or is the Home panel sufficient?

### If deferred

Ship the Home panel with destination, dates, adults, children (count) and rooms. Store children's ages as optional columns from day one so adding them is a data change.

---

## DEC-09 — Q-21: What does account deletion mean, and what must be retained?

### Context

M-056 lists consequences: documents deleted, saved travellers and favourites lost, **existing bookings are not cancelled**; it warns about an upcoming trip; it requires typing «حذف». D-03.11 shows an admin queue of **14 open deletion requests**, proving deletion is **processed**, not instant. M-051/M-042 show payments and refunds that must survive for accounting.

### Why this blocks the database

* Deletion determines whether rows are destroyed, soft-deleted or anonymised — which changes every foreign key that points at a user.
* Financial records must remain auditable after the customer is gone; if payments reference a deleted user, the ledger breaks unless a snapshot or pseudonym is kept.
* A grace period requires a **request** entity separate from the user, with cancel/reinstate semantics.
* Legal retention periods differ per data class (identity documents, passport scans, invoices), so deletion cannot be a single `DELETE`.

### Options

**Option A — Request → grace period → anonymisation, financial data retained**
* How it works: a deletion request is created (visible in the admin queue), login is blocked immediately or at grace start, the customer can cancel during the grace window, then a job anonymises PII, deletes documents/travellers/favourites, and keeps financial records linked to a pseudonymous id or a customer snapshot.
* Advantages: reversible mistakes; satisfies "right to erasure" for PII while preserving accounting; matches the admin queue; supports legal retention by data class; audit stays intact.
* Disadvantages: two-step implementation; "deleted" customers still exist as pseudonyms; support must understand the distinction; jobs must be monitored.
* Schema impact: `deletion_requests` with state and timestamps; `deleted_at`/`anonymised_at`; nullable user FK + snapshot on financial rows; per-table retention rules.

**Option B — Immediate hard delete after grace, financial rows keep snapshots only**
* How it works: after the grace period, the user row and all PII are physically deleted; bookings/payments retain denormalised customer snapshots and no FK.
* Advantages: strongest privacy posture; smallest residual data; clear to explain.
* Disadvantages: loses referential integrity for support lookups and re-registration; duplicates customer data inside each booking; analytics on customers becomes impossible; irreversible.
* Schema impact: nullable/removed FKs; snapshot columns on every historical row; cascade rules carefully ordered.

**Option C — Anonymise immediately, keep the account shell**
* How it works: PII is replaced at request time; the account remains as an empty shell; bookings remain attached.
* Advantages: fastest to implement; no jobs; preserves links.
* Disadvantages: "deleted" account still exists and can be matched; no grace period; poor customer expectation; may not satisfy erasure.
* Schema impact: anonymisation fields; no deletion state machine.

**Option D — Manual admin-only deletion**
* How it works: requests are queued and an admin performs deletion after review; nothing is automated.
* Advantages: maximum control; matches the queue screen; handles edge cases (active visas, upcoming trips) case by case.
* Disadvantages: does not scale; SLA depends on staff; inconsistent outcomes; error-prone.
* Schema impact: request table only; manual operations.

### Technical recommendation

**Option A**, with the admin queue as the operational control. Model deletion as a workflow, not a database event: `requested → scheduled → anonymised` (or `cancelled`). Block new transactions during the grace period, keep existing bookings visible to operations, and retain financial records under a pseudonymous identifier. The "bookings are not cancelled" rule must be explicit to the customer — it is a business decision with refund implications.

Also decide whether an **active visa application** blocks deletion. It probably should, because the company is still working on a live case and documents are held.

### Business approval required

* Grace period length (14 days suggested), and whether the request can be cancelled.
* Does deletion block while an upcoming trip, open visa application or pending refund exists?
* Which data classes are deleted, anonymised or retained — and for how long? (Legal input required.)
* What does the customer receive as confirmation?
* Are bookings preserved and visible to the partner after deletion? Under what identity?

### If deferred

Do not implement deletion. Store requests in a queue and process manually. The schema needs only the request table plus nullable `deleted_at`; the anonymisation job can be added later without reshaping the user table.

---

# Group 5 — Finance operations

## DEC-10 — Q-29: Who approves refunds, and how are settlements executed?

### Context

D-03.2 lists a **refund request** notification. M-042 lets a customer cancel and promises money back in 7–14 days. D-03.14 shows platform finance KPIs, a partner-dues table with a **transfer status**, a transaction log (`PAY-90121`) and transfer actions. D-04.9 shows the partner's **upcoming settlements**. No approval screen exists for either refunds or payouts.

### Why this blocks the database

* A refund is not a status change; it is a **money movement** with an approval, a gateway reference, a failure path and a reconciliation state. That requires its own entity and an immutable ledger.
* Settlements require **batch** and **line** records so a partner's period can be recalculated, partially paid or reversed.
* If approval thresholds exist, the schema must store who approved, when, and against which policy — otherwise the audit trail cannot answer "who authorised this payout?".
* Commission, refunds and partner dues must reconcile across three portals (BR-103, BR-114); without a ledger, each screen computes its own version of the truth.

### Options

**Option A — Policy-based automation with manual escalation, batch settlements**
* How it works: refunds within policy are auto-approved (gateway refund); above a threshold or outside policy they require an authorised approver. Settlements are generated as a batch of partner-dues lines, executed as one or more payouts, and tracked through `pending → processing → transferred | failed`.
* Advantages: fast for routine cases; controlled for exceptions; auditable; supports partial and failed payouts; scales.
* Disadvantages: policy logic must be explicit; threshold configuration; two code paths to test.
* Schema impact: `refunds` (state, approver, reason, gateway ref), `settlements` + `settlement_items`, `payouts`, append-only ledger entries.

**Option B — Maker-checker for every refund and payout**
* How it works: one user initiates, a different authorised user approves, always.
* Advantages: strongest financial control; simple to audit; prevents single-actor fraud.
* Disadvantages: slow; requires at least two finance users; blocks small routine refunds; operational overhead.
* Schema impact: approval requests with initiator/approver pairs; state machine with rejection.

**Option C — Record-only: money moves outside the system**
* How it works: finance executes the bank transfer or gateway refund externally and marks the record as transferred/refunded.
* Advantages: no gateway integration; minimal engineering; works from day one.
* Disadvantages: no automated reconciliation; human error; customer status depends on manual updates; no idempotency; disputes are hard to prove.
* Schema impact: transaction rows with status and external reference; no ledger automation.

**Option D — Fully automated gateway payouts (marketplace-style)**
* How it works: a payment provider (e.g. Stripe Connect) holds funds, splits commission and pays partners automatically; refunds are API calls.
* Advantages: least manual work at scale; built-in reconciliation; regulatory burden shifts to the provider.
* Disadvantages: provider fees; onboarding/KYC for every partner; less control over payout timing; jurisdiction and currency constraints; migration cost if adopted later.
* Schema impact: provider account ids, transfer ids, payout webhooks, reconciliation tables.

### Technical recommendation

**Option A**, with an approvals table that can enforce Option B later (initiator ≠ approver is a constraint, not a redesign). Add an append-only **double-entry ledger** for money movements rather than relying on mutable status columns: a booking payment, a refund, a commission accrual and a partner payout are all postings. This is what makes D-03.14, D-04.9 and M-051 agree by construction instead of by coincidence.

Keep gateway integration behind an interface: start with Option C for payouts (manual transfer recorded) and Option A for card refunds (gateway API), then adopt Option D for payouts when volume justifies it. The data model does not change between C and D for payouts — only the executor does.

### Business approval required

* Who may approve a refund, and is there an amount threshold above which a second approver is required?
* Are refunds automatic within policy, or always reviewed?
* How are partner payouts executed at launch — bank transfer, gateway, or manual? On what schedule (weekly, monthly, on request)?
* What happens when a partner's dues are negative (refunds exceed sales)? Carry forward, invoice, or deduct from a deposit?
* Who bears gateway fees and chargebacks?
* Are partial refunds allowed?

### If deferred

Implement refunds as **record-only** (Option C) with a required approver field and an immutable ledger entry; implement settlements as batches with manual transfer recording. This unblocks the schema and can later switch the executor without changing entities.

---

# 6. Answer sheet

Copy this block and fill in your choices. Any line may be marked `DEFER` with a reason.

```
DEC-01 (Q-01) refund arithmetic:        [ A | B | C | D ]   notes:
DEC-02 (Q-19) multi-currency:           [ A | B | C | D ]   currencies:
DEC-03 (Q-14) promotions:               [ A | B | C | D ]   stacking: yes/no
DEC-04 (Q-25) reference scheme:         [ A | B | C | D ]   format notes:
DEC-05 (Q-11) fare hold:                [ A | B | C | D ]   duration:
DEC-06 (Q-12) visa fee model:           [ A | B | C | D ]   who collects:
DEC-07 (Q-13) visa lifecycle:           [ A | B | C | D ]   who records decision:
DEC-08 (Q-23) hotel search inputs:      [ A | B | C | D ]   children ages: yes/no
DEC-09 (Q-21) account deletion:         [ A | B | C | D ]   grace period:
DEC-10 (Q-29) refunds & settlements:    [ A | B | C | D ]   approval threshold:
```

### Decisions that cannot be made technically

These need your business answer, not an engineering default:

| Decision | Why it is yours |
|---|---|
| DEC-01 | Whether 5,120 is a real amount; refund fee policy |
| DEC-02 | Which currencies customers see and are charged in; partner payout currency |
| DEC-03 | Stacking, "first booking" definition, refund treatment of discounts |
| DEC-06 | Who collects visa money; refundability on rejection; nationality-based pricing |
| DEC-07 | Who submits to authorities and records decisions; what the customer sees |
| DEC-08 | Search inputs and child policy |
| DEC-09 | Grace period, retention periods, blocking conditions (legal review) |
| DEC-10 | Approval authority, thresholds, payout method and schedule, negative balances |

DEC-04 is primarily technical; only the customer-facing format needs your sign-off.
DEC-05 is primarily technical; the customer-facing duration and post-expiry policy need your sign-off.

---

# 7. What happens after you answer

1. **`OPEN-QUESTIONS.md`** — each `Q-nn` moves from `Open` to `Answered` or `Deferred`, with the chosen option, date, decision owner and a short rationale. Original text is preserved; the answer is added beneath it. No question is deleted.
2. **`BUSINESS-LOGIC.md`** — affected rules change from `U` to `C` or `I`. Where a decision introduces a new rule, it is appended with the next free `BR-nnn`; existing IDs are never renumbered. Deprecated rules keep their ID and gain a `superseded by` note.
3. **`FEATURES-CATALOG.md`** — only features whose scope changes are edited (for example, multi-currency or visa payment). Feature IDs remain stable; deferred features stay in the catalogue marked `U`/deferred.
4. **`WORKFLOWS.md`** — steps that depended on the answer are rewritten; new exception paths are added. `WF-nn` IDs remain stable.
5. **Decision log** — a new section records the 10 decisions with rationale, so Phase 3 can cite them.
6. **Readiness gate** — Phase 3 starts only when all 10 are `Answered` or `Deferred` with a documented strategy. A deferral records the schema accommodation (nullable dimension, extension table or abstracted executor) that makes the later change non-destructive.

No code, migration, model or API will be produced until you approve Phase 3 separately.

---

# 8. Quick reference — current blockers

| Q | One-line blocker | If you answer nothing else, decide |
|---|---|---|
| Q-01 | Two irreconcilable totals for one trip | Is 5,120 real, and what is the cancellation fee rule? |
| Q-19 | Currency selector without a currency model | USD only, or display/charge in local currencies? |
| Q-14 | Promo behaviour undefined beyond one example | Can codes stack, and what counts as "first booking"? |
| Q-25 | Nine identifier formats | Approve surrogate key + one human reference per entity |
| Q-11 | 15-minute timer with no stated policy | Hold duration, and what happens if payment arrives late |
| Q-12 | Fee placeholder, no payment screen | Who collects the visa fee and is it refundable? |
| Q-13 | Workflow stops after document review | Who records the authority's decision, and what does the customer see? |
| Q-23 | Hotel search form missing | Which inputs, and are children's ages required? |
| Q-21 | Deletion queue with no policy | Grace period, blocking conditions, retention |
| Q-29 | Refund/payout actions with no approval flow | Who approves money out, and how are partners paid? |


