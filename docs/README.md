# Traway Documentation

One authoritative document per subject. This index says which document owns what, which documents are current, and where the entry points are.

**Last reviewed:** 2026-11-24 (Gate 0 documentation cleanup)

---

## Start here

| Question | Document |
|---|---|
| What is approved, what is pending, and what blocks what? | [`GATE-0-DECISION-REGISTER.md`](./GATE-0-DECISION-REGISTER.md) |
| What exists in the code today, and what is tested? | [`IMPLEMENTATION-STATUS.md`](./IMPLEMENTATION-STATUS.md) |
| What is missing between the documents and the code? | [`IMPLEMENTATION-GAP-ANALYSIS.md`](./IMPLEMENTATION-GAP-ANALYSIS.md) |
| What is the exact permission catalog? | [`PERMISSION-CATALOG-RECONCILIATION.md`](./PERMISSION-CATALOG-RECONCILIATION.md) (derivation) → [`phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md`](./phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md) (catalog and role assignment) |
| Which business questions are still open? | [`phase-02/OPEN-QUESTIONS.md`](./phase-02/OPEN-QUESTIONS.md) |

---

## Gate 0 — two approval boundaries

| Gate | Scope | Blocks | Status |
|---|---|---|---|
| **Gate 0A** | `G0-DEC-01`–`G0-DEC-05` (approved), the exact 85-key catalog, P-09, delegation and owner-template rules, empty-scope enforcement | Phase A, and every later phase | **Ready for owner review**; the catalog, P-09, delegation and enforcement detail await approval |
| **Gate 0B** | Notifications, provider architecture and selection, F-027/F-043/F-071, multi-city, settlements | Only the phase that depends on the specific item | **Pending**; no item approved. F-071 has no approved policy |

`G0-DEC-01`–`G0-DEC-05` are distinct from the business decisions `DEC-01`–`DEC-10` (recorded in `phase-02/OPEN-QUESTIONS.md` §2.9 and analysed in `phase-02/DB-DECISION-BRIEF.md`). The `G0-` prefix exists so the two sets never collide.

**No Phase A work starts until Gate 0A is approved in full.**

---

## Document ownership

### Status and decisions (root)

| Document | Owns | Notes |
|---|---|---|
| `GATE-0-DECISION-REGISTER.md` | The Gate 0A/0B split, the five approved decisions, the recommended and pending decisions, conflict resolution status, the approval checklist | **Authoritative for decision status.** If any other document disagrees about what is approved, this document wins |
| `IMPLEMENTATION-STATUS.md` | What is implemented and tested, phase by phase; the Gate 0 status summary | **Authoritative for implementation state** |
| `IMPLEMENTATION-GAP-ANALYSIS.md` | The audit of documents vs code: feature rules, business rules, workflows, authorization layers, conflicts C-01–C-18, missing schema, the phase checklist with dependency order | §9 conflicts are preserved as originally found; §9.1 records their resolution |
| `PERMISSION-CATALOG-RECONCILIATION.md` | The permission catalog derivation: count forensics, both deltas, every single-source key, delegation rules, catalog defects P-01–P-15 | The **derivation**; the matrix holds the presentation |

### Phase 1 — source requirements

| Document | Owns |
|---|---|
| `phase-01/UI-SCREENS-INVENTORY.md` | The screen inventory extracted from the 85 design captures (M-*, D-*, screen IDs used everywhere else) |
| `phase-01/SCREEN-COVERAGE-REPORT.md` | Proof of coverage: every capture read, every screen accounted for |

These are the origin of the `M-`/`D-` screen IDs; they are not implementation status and are not superseded by it.

### Phase 2 — business and authorization requirements

| Document | Owns | Notes |
|---|---|---|
| `phase-02/AUTHORIZATION-ARCHITECTURE.md` | Storage model, enforcement layers, privilege-escalation rules, delegation, lifecycle, tenant resolution | Multi-tenant authorization design; audit is excluded (§11) |
| `phase-02/ROLE-PERMISSION-SCOPE-MATRIX.md` | The permission catalog as a table, role → permission assignment, scope matrix, data visibility, delegation matrix, coverage checks | §3 is the **proposed** 85-key catalog, pending Gate 0A approval |
| `phase-02/USER-ROLES-AND-PERMISSIONS.md` | Role definitions R-01–R-19 in prose, per-portal permission matrices, role lifecycle | The role narrative; the matrix is authoritative for keys |
| `phase-02/BUSINESS-LOGIC.md` | Business rules BR-001–BR-125 with source screens | BR-107/111/124 carry superseded-audit notes |
| `phase-02/FEATURES-CATALOG.md` | Features F-001–F-112 with priority, source screens and status (`C`/`I`/`U`/`D`) | The feature ID namespace |
| `phase-02/WORKFLOWS.md` | Workflows WF-01–WF-15 end to end | Process, not authorization |
| `phase-02/OPEN-QUESTIONS.md` | Question register Q-01–Q-52, recorded business decisions `DEC-01`–`DEC-10`, Gate 0 decisions `G0-DEC-01`–`G0-DEC-05`, blocking summary, assumptions | **Authoritative for open questions** |
| `phase-02/DB-DECISION-BRIEF.md` | The ten database-blocking decisions with options, technical recommendation and consequences; §9 records the answers | The reasoning behind `DEC-01`–`DEC-10`; not a second decision register |

---

## Rules for changing documentation

1. **One subject, one document.** A decision is recorded in the register; a requirement in `BUSINESS-LOGIC.md`/`FEATURES-CATALOG.md`; a question in `OPEN-QUESTIONS.md`; an implementation fact in `IMPLEMENTATION-STATUS.md`. Do not restate a decision in three places — link to it.
2. **Never describe a recommendation as approved.** Use the vocabulary in the register §1.2: Approved, Recommended, Requires clarification, Deferred.
3. **Never reopen an approved decision** without a concrete technical contradiction, and record the contradiction where it was found.
4. **Superseded text is marked, not deleted.** Keep the original wording and add a "Superseded by …" note, so the history of a requirement stays readable.
5. **Counts are derived, not chosen.** Any permission count must be reproducible from `PERMISSION-CATALOG-RECONCILIATION.md`, and any statement of a delta must name its baseline (seeder or union).
6. **No audit, activity or user-tracking capability.** `status_histories` is the only history table and `status_history.view` is the only key that reads it (`G0-DEC-01`).


## Phase 2 authorization references

- [Service-aware permission catalog](./phase-02/SERVICE-AWARE-PERMISSION-CATALOG.md) — candidate permission keys, service applicability and scope requirements.
- [Dashboard permission mapping](./phase-02/DASHBOARD-PERMISSION-MAPPING.md) — supplied dashboard screens mapped to candidate permissions and data boundaries; UI facts are distinguished from proposed enforcement.
- [Authorization data model design](./phase-02/AUTHORIZATION-DATA-MODEL-DESIGN.md) — proposed persistence model, runtime checks and test matrix.
- [Multi-service provider authorization](./phase-02/MULTI-SERVICE-PROVIDER-AUTHORIZATION.md) — approved product direction and tenant/service/resource isolation rules.
