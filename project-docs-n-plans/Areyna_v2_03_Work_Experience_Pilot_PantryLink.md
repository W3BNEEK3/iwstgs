# Areyna v2 — 03: Work Experience Pilot — PantryLink

> **Goal:** convert PantryLink from a set of standalone challenges into a **full product built by a team**, where the learner holds one role (Backend, Frontend or Full-stack) and builds their part of a real, running app, integrating with teammates' code that arrives as pull requests, following team procedures, and dealing with the same stakeholders, incidents and changing requirements the classic version already has.
>
> Depends on doc 01 (platform, especially §7 teammate PRs). Authoring follows doc 04. The classic PantryLink stays published until this version passes its exit criteria (§9).

---

## 1. What Carries Over from Classic PantryLink

| Classic content | v2 use |
|---|---|
| Harbourside Community Pantry story, business context, constraints | Unchanged |
| Stakeholders Grace Okafor (Coordinator), Tom Reyes (Volunteer Lead), Harbourside Trust (Funder) | Unchanged; they send the scripted messages |
| Vault: PRD, glossary, sprint goal template | Unchanged, plus new `docs/` in the repo (§3) |
| 3 scenarios: Logging Donations · Expiry & Stock · Volunteers & Funder Report | Become 3 sprints of the build |
| Core tasks (donation endpoint, list page, stock, expiry fix, report, shift sign-up) | Become **tickets** on the same codebase, split by role |
| Consequence tasks (impossible donations, expired yoghurt, negative stock race, midnight time-zone bug, name leaked in report, screen-reader sign-up) | Become **incidents and consequences** in the build, now against the learner's own code |
| Suggestion tasks | Unchanged in spirit |

---

## 2. Team, Roles and Stack

### 2.1 Stack variant (pilot has one)

| Key | `express-react` |
|---|---|
| `api/` | Node 20, Express, SQLite (`better-sqlite3`), zod for validation |
| `web/` | React + Vite, React Router |
| Contract | `docs/api.yaml` (OpenAPI 3.1) — the agreement between frontend and backend |
| Start | `npm start` builds `web/` and serves it from the API on one port (as Taskly B) |

### 2.2 Roles the learner can apply for

| Role | Owns | Receives from the team | Rank gate |
|---|---|---|---|
| **Backend Developer** | `api/` | Frontend PRs to `web/` from Mira | none |
| **Frontend Developer** | `web/` | Backend PRs to `api/` from Tunde | none |
| **Full-stack Developer** | `api/` and `web/` | QA reports and design hand-offs only (no code PRs) | Junior-2 (confirm) |

Role definitions: reuse *Associate Backend Engineer* (`backend`) and *Associate Frontend Engineer* (`frontend`); add *Associate Full-stack Engineer* (`fullstack`). Project `specialization_tags`: `backend`, `frontend`, `fullstack`.

### 2.3 The team (personas)

| Persona | Role | Voice |
|---|---|---|
| **Tunde Bakare** | Backend Lead | Direct, pragmatic; writes tidy PRs; asks for reviews before Friday |
| **Mira Chen** | Frontend Developer | Friendly, detail-oriented about accessibility |
| **Ife Balogun** | QA (part-time volunteer) | Files precise bug reports with steps to reproduce |
| **Grace Okafor** | Pantry Coordinator (stakeholder) | Non-technical, focused on waste and volunteers |
| **Tom Reyes** | Volunteer Lead (stakeholder) | Reports what happens on the floor |

---

## 3. The Repository (template `areyna-templates/pantrylink-express-react`)

```
api/                     # Express app skeleton: health route, db connection, empty routes/
web/                     # React skeleton: layout, router, empty pages, api client helper
docs/api.yaml            # OpenAPI contract — sprint 1 endpoints defined, later ones added by events
docs/PROCEDURES.md       # team working agreement (§4)
docs/DECISIONS.md        # lightweight decision log the learner adds to
.github/pull_request_template.md
.github/CODEOWNERS       # api/ → @team-backend, web/ → @team-frontend (explains ownership; not enforced)
.github/workflows/areyna.yml
areyna.json
README.md
```

The **reference build** (`…-reference`, private) is built role by role and tagged per event (e.g. `api-s1-donations`, `web-s1-donation-form`) so any learner, in any role, can be handed exactly the teammate work they need at the right moment.

---

## 4. Procedures (what makes it feel like a company)

`docs/PROCEDURES.md`, surfaced during induction and by the guide:

1. **One ticket, one branch**: `feature/PL-<number>-<short-slug>` (fixes: `fix/PL-<n>-…`).
2. **Pull request** using the template: *What* · *Why* (link the ticket) · *How I tested it* · *Screenshots* (UI) · *Notes for the reviewer*.
3. **Checks must be green** (the Areyna acceptance workflow).
4. **One approval** from review — the AI "Senior Reviewer" (doc 01 §5.4); address every comment or reply explaining why not.
5. **Squash-merge** by the author after approval; delete the branch.
6. **Contract first**: if you need an API change, update `docs/api.yaml` in your PR and say so in *Notes for the reviewer*.
7. **Definition of done**: merged, tests green, ticket moved to Done, stakeholder-facing change noted in the sprint summary.

The AI reviewer and the evaluation both check procedure (branch name, template filled, ticket linked); procedure feeds the **communication** dimension.

---

## 5. Sprint-by-Sprint Plan

Legend: **[B]** backend-tagged ticket · **[F]** frontend-tagged · **[B+F]** both halves (split by role; full-stack does both) · **TPR** = teammate PR event · **E** = other scripted event.

### Sprint 1 — Logging Donations

| | Backend learner | Frontend learner | Full-stack learner |
|---|---|---|---|
| Tickets | **PL-3 [B]** `POST /donations`, `GET /donations` (validation per PRD, 201/422) · **PL-5 [B]** sort by expiry, filter by category | **PL-4 [F]** donation form (tablet-friendly, server errors shown per field) · **PL-6 [F]** donations list with filter + expiring-soon highlight | PL-3, PL-4, PL-5, PL-6 |
| Team work received | **TPR** (after PL-3 passes) Mira: *donation form* → learner **reviews** it (does it use the API correctly? handles 422?) | **TPR** (sprint start) Tunde: *donations API* → learner **reviews** it, then builds PL-4 against it | — |
| Events | **E** Grace (sprint start): "Saturday is our busiest day — can volunteers use this by then?" | same | same |

Acceptance tests (IDs `S1-*`): API contract tests for PL-3/PL-5, UI tests for PL-4/PL-6. Everyone's suite runs the whole app; the tests a ticket requires are listed in its `task_variant_specs`.

### Sprint 2 — Expiry and Stock

| | Backend | Frontend | Full-stack |
|---|---|---|---|
| Tickets | **PL-8 [B]** distributions + stock that never goes negative (409 when insufficient) · **PL-9 [B]** `GET /donations/expiring-soon` with correct date boundaries | **PL-10 [F]** "give out items" screen; handle 409 by refreshing stock and explaining · **PL-11 [F]** expiring-soon widget | PL-8…PL-11 |
| Team work received | **TPR** Mira: give-out screen (after PL-8) → merge | **TPR** Tunde: distributions API + contract update (sprint start) → review | — |
| Events | **E** Tom (sprint start): "Dashboard says 40 tins, shelf has 12." · **Incident** (after PL-8, day 3): "Stock went to −3 on Saturday — two tablets gave out the last rice at once." → injected **consequence**: make it safe under concurrency | **Incident** (same moment): "The give-out screen showed stock that was already gone; volunteers were confused." → handle stale data and the 409 gracefully | both incidents |
| QA | **E** Ife files a bug against the learner's own endpoint if its boundary tests failed (adaptive) | Ife files a bug against the widget if it misses "expires today" (adaptive) | either |

### Sprint 3 — Volunteers and the Funder Report

| | Backend | Frontend | Full-stack |
|---|---|---|---|
| Tickets | **PL-14 [B]** quarterly report endpoint (totals, waste %, no personal data) · **PL-15 [B]** shifts API with capacity + 12-hour cancellation rule | **PL-16 [F]** report page with CSV download · **PL-17 [F]** accessible shift sign-up page | PL-14…PL-17 |
| Team work received | **TPR** Mira: report page + sign-up page → review one, merge one | **TPR** Tunde: report + shifts API → review | — |
| Events | **Requirement change** (day 2): Harbourside Trust wants a custom date range, not just quarters → contract update · **Incident** (after PL-14): "A family's name appeared in the report" → privacy-by-design fix | **Requirement change** (same) → date-range picker · **Incident** (after PL-17): "Amara couldn't sign up with her screen reader" | both sets |
| Close | **E** Grace: demo day — the learner writes a short sprint summary for stakeholders (communication) | same | same |

---

## 6. Adaptive Content

- **Consequences**: every learner-owned ticket has a consequence authored from the classic PantryLink consequence tasks, re-framed against their own code (e.g. PL-3 → "Impossible donations in the database": −5 tins, milk from 2019).
- **Regression consequence**: "Something the volunteers relied on broke after your merge" — generic, with the failing test titles inserted.
- **Suggestions** per sprint: *write the rules down as tests first* · *reproduce before you fix* · *explain your assumptions to a non-technical stakeholder* (reused).
- **Review tasks** are graded on: did the review catch the seeded issue (each reviewed TPR contains one small, realistic flaw — e.g. missing 422 handling), is it kind and specific, does it suggest a fix.

---

## 7. Deadlines and Time

Proposal (Overview §7.4): each sprint has a **soft deadline of 7 real days** from sprint confirmation. `sprint_day` events use real elapsed days, but any not-yet-fired day events fire when the learner submits the sprint early, so fast learners still see the whole story. Missing the deadline triggers a stakeholder message ("Grace: we had to use the spreadsheet again this Saturday") and is recorded for reporting — it never locks the learner out.

---

## 8. Mapping to the Engine

| Engine concept | PantryLink v2 |
|---|---|
| Project | PantryLink, `track = work_experience` |
| Stack variants | `express-react` (pilot); a Laravel or Django variant can be added later with the same story |
| Scenarios | 3 sprints |
| Tasks | ~12 tickets (milestones, role-tagged), ~6 review tasks, consequence + suggestion tasks |
| Scenario events | stakeholder messages, teammate PRs (per role), incidents, requirement changes |
| Sprint planning / backlog | **On** (unlike Build): tickets are backlog items; the learner plans sprints |
| Roles | backend / frontend / fullstack via `role_tags` and `role_definitions` |

---

## 9. Exit Criteria and Cut-over

- A tester completes PantryLink v2 as **Frontend** and as **Backend** (and one Full-stack sprint), and the final repo runs as a complete app.
- Every teammate PR applies cleanly to a repo that followed the tickets; the reference build passes all tests at every tag.
- AI PR reviews are specific enough that a tester can act on them without asking.
- Then: publish v2, unpublish classic PantryLink for **new** enrolments; existing classic sessions continue untouched.
