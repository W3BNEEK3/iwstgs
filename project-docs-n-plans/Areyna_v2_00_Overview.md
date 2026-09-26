# Areyna v2 — Overview: Full Projects, Two Tracks

> **Goal:** turn Areyna from a platform of *self-contained work-experience tasks* into one where every project is a **real piece of software the learner builds from an empty repository to a finished product**, while keeping everything that makes it feel like a real job: sprints, stakeholders, changing requirements, incidents, consequences and adaptive difficulty.
>
> This is the entry document. Read it first; the other v2 docs go deeper:
>
> | Doc | What it covers |
> |---|---|
> | **00 — Overview** (this doc) | Why v2, the two tracks, the learner journey, what changes vs. what stays, decisions, phases |
> | **01 — Platform Foundation** | Data model, GitHub App, milestone submissions, acceptance tests, evaluation, teammate PRs, security |
> | **02 — Build Pilot: Taskly** | The first Build-track project: a to-do app in two JavaScript variants, milestone by milestone |
> | **03 — Work Experience Pilot: PantryLink** | Converting PantryLink into a role-based, team-based full build |
> | **04 — Content Authoring Guide** | The repeatable recipe for adding any new project, stack variant or role |

---

## 1. Why v2

### 1.1 How the platform works today (v1)

| Level | What it is today |
|---|---|
| **Project** | A fictional company (MedQueue, ShiftBoard, PantryLink, CampusBook, PayFlow) |
| **Scenario** | A situation at that company ("Sprint 2 — stock counts are wrong"). 3 per project |
| **Task** | 1–3 per scenario. Each is **self-contained**: "build this endpoint", "fix this bug", "explain this trade-off". The learner pastes code/text, the AI grades it against a rubric |
| **Consequence / suggestion** | Injected when a learner fails a task or shows a repeated weakness |

### 1.2 The problem

**Nothing carries over between tasks.** There is no single codebase that grows. Each task is judged on its own, so after the last scenario the learner has no working software — only a set of graded fragments from different moments of a project's life. It also means:

- there are no genuinely first-app projects (to-do, notes) for true beginners;
- roles are few (backend, frontend, QA) because a role only changes which fragments you see;
- the AI can judge what the learner *pasted*, but never whether the app actually runs.

### 1.3 The v2 idea in one sentence

> **A project is a product built step by step in the learner's own GitHub repository; every task is a milestone on that same codebase, and real-world disruptions happen *during* the build, to the learner's own code.**

When the learner finishes the last scenario, they have a working app they own and can keep building — whether or not they noticed they were "building a project" at all.

---

## 2. Two Tracks, One Engine

| | **Build track** (learning) | **Work Experience track** (job simulation) |
|---|---|---|
| **Who you are** | The whole team, solo | One role on a company team |
| **What you make** | A complete app from an empty repo | Your role's slice of a complete product; teammates' code arrives as real pull requests |
| **Typical projects** | To-do list, notes, expense tracker → small products | Company products: PantryLink, CampusBook, MedQueue, … |
| **Structure** | Chapters and milestones | Sprints, backlog, stakeholders, procedures, deadlines |
| **Disruptions** | Light and friendly ("the client wants due dates now") | Scripted (requirement changes, incidents, code reviews, teammate PRs) **plus** adaptive consequences on *your* code |
| **How work is submitted** | Push to your repo, submit a milestone (a commit) | Open a pull request in your repo following team procedure |
| **Roles** | None | Frontend, Backend, Full-stack; later Mobile, QA, DevOps, UI/UX, Data |
| **Unlocks** | Open to everyone | Open to everyone; harder projects/stack variants gated by rank |

**One engine** means both tracks reuse the same machinery that already exists: projects → scenarios → tasks, sprints and backlog, rubrics and the six competence dimensions, AI evaluation, consequence and suggestion injection, CAC (complexity / autonomy / context), ranks, the in-app guide, reporting.

### 2.1 Stack variants: the same project, harder

Every project has one or more **stack variants**. A harder stack is a harder version of the *same* project, with the same story, features and milestones. Example for the Build pilot:

| Variant | Difficulty | What makes it harder |
|---|---|---|
| **Express + EJS** | 1 (easier) | One Node app rendering HTML pages. No separate frontend |
| **Express API + React** | 2 (harder) | Two apps: a JSON API and a React frontend — state, fetching, CORS, two things to run |

As we add projects, we add variants (Laravel, Django, Next.js, mobile, …). Content that is written once per *project* (story, milestones, rubrics, acceptance tests where possible) is shared by all its variants; only starter code, reference code and a few hints are per variant. See doc 04.

### 2.2 The ladder

```
Build track                                   Work Experience track
─────────────────────────────                 ─────────────────────────────────
Tiny apps (to-do, notes)   ── rank grows ──▶  Beginner company projects (PantryLink)
Small products (tracker)                      Intermediate (CampusBook, MedQueue)
                                              Advanced (PayFlow)
```

Harder **stack variants** of any project are gated by rank, independently of which project it is.

---

## 3. The Learner Journey

### 3.1 Build track — "Taskly" (to-do app), Express + EJS variant

1. **Check-in (diagnostic)** — unchanged; sets the starting rank.
2. **Pick a project** — Taskly. The explainer shows what they'll build, the chapters, time needed and the two stack variants.
3. **Connect GitHub** (once per account) — then click **Create my repo**. GitHub creates `taskly` in their account from our starter template; they install the Areyna app on that one repo.
4. **Chapter 1 — "Get it on the screen"** — Milestone 1: run the starter, show a list of tasks. They code locally, push, click **Submit milestone**, pick the commit, write a short "what I did and why".
5. **Checking** — our acceptance tests run automatically in their repo (GitHub Actions). Areyna reads the test result *and* the AI reviews the code changes and the explanation. Pass → next milestone. Fail → feedback, and they push a fix.
6. **A light disruption** — at the start of Chapter 2, a message from "the client": "Could tasks have due dates?" That becomes the next milestone — on the same code.
7. **Regression = consequence** — if Milestone 5 breaks adding tasks (a Milestone 2 test now fails), a consequence task appears: "Users report they can't add tasks since yesterday's update."
8. **Finish** — Milestone 7: polish, README, deploy. They now own a deployed to-do app with a clean commit history, and the profile shows the competency graph.

### 3.2 Work Experience track — PantryLink as the Frontend developer

1. **Apply for a role** — Frontend Developer at Harbourside Community Pantry.
2. **Repo** — created from the PantryLink template: `api/` (backend, owned by "the team") and `web/` (frontend, the learner's).
3. **Induction** — meet the stakeholders (Grace, Tom) and the team (Tunde, backend lead; Mira, frontend; Ife, QA). Read the procedures: branch naming, PR template, definition of done.
4. **Sprint 1 planning** — pull frontend tickets from the backlog, write the sprint goal.
5. **Teammate PR** — Tunde (backend) opens a PR adding `POST /donations`. The learner is asked to review it, then build the donation form against it.
6. **Procedure** — the learner opens their own PR; the AI "senior reviewer" leaves review comments on GitHub; they address them; tests + review pass → ticket done.
7. **Incident** — mid-Sprint 2, "Stock went negative on Saturday" — for the frontend learner this is "the page showed stale stock; make it refresh and handle the 409 conflict the API now returns".
8. **Finish** — Sprint 3 demo: a complete, running PantryLink where the learner built the frontend and integrated with a real backend.

---

## 4. What Changes, What Stays

| Area | v1 today | v2 |
|---|---|---|
| Project | Fictional company, fragments | Fictional company **or** plain product; a whole app |
| Project track | — | `build` or `work_experience` (existing projects become `classic` until converted) |
| Stack | Implied, single | Explicit **stack variants** per project |
| Task | Self-contained problem | **Milestone** on the same codebase (plus review, planning and explanation tasks) |
| Where code lives | Pasted into a form | Learner's own **GitHub repo** |
| Checking | AI rubric only | **Acceptance tests (CI) + AI review** of the actual diff + explanation |
| Other roles' work | Not represented | **Teammate PRs** from our reference build, delivered into the learner's repo |
| Disruptions | Adaptive only (consequence/suggestion) | **Scripted events** (planned for everyone) + adaptive (consequence/suggestion) |
| Sprints, backlog, CAC, ranks, dimensions, guide, reporting | ✔ | ✔ unchanged in concept, extended where noted in doc 01 |
| Diagnostic | Paste-in assessment | **Unchanged** (short assessment, not a build) |

---

## 5. Decisions Log (agreed with the product owner, Sept 2026)

| # | Decision | Chosen |
|---|---|---|
| D1 | Where learner code lives | **Their own GitHub repo** |
| D2 | Stacks | **Learner picks from a few variants per project**; a harder stack is a harder version of the same project; projects keep gaining variants |
| D3 | Roles | Frontend / Backend / Full-stack, Mobile, QA, DevOps / UI-UX / Data — **added as projects need them** |
| D4 | Entry level | **A ladder**: tiny first apps → small products → company projects; rank unlocks |
| D5 | How work is checked | **AI review + our acceptance tests** |
| D6 | Other roles' work | **Provided by the "team"** — real code from our reference build, delivered as PRs |
| D7 | Disruptions | **Scripted + adaptive** |
| D8 | Existing 5 projects | Keep their stories; **convert into Work Experience full builds** over time; run as `classic` until converted |
| D9 | Structure | **Two tracks**: Build + Work Experience |
| D10 | Pilot | **1 Build + 1 Work Experience**: Taskly (to-do) in 2 variants + PantryLink conversion in 1 stack |
| D11 | Pilot stacks | **JavaScript**: Express + EJS (easier), Express API + React (harder) |
| D12 | GitHub connection | **Areyna GitHub App** |
| D13 | Build pilot app | **To-do list ("Taskly")** |

---

## 6. Phases

| Phase | Scope | Exit criteria |
|---|---|---|
| **v2-1 Foundation** (doc 01) | Tracks and stack variants in the schema; GitHub App connect + repo linking; milestone submissions (commit / PR); CI result ingestion; evaluation of diff + tests + explanation; scripted events; admin authoring for the new fields | A learner can connect GitHub, link a repo made from a template, submit a milestone, and see it graded from real CI results + AI review |
| **v2-2 Build pilot** (doc 02) | Taskly: product spec, 2 variants × (starter template, reference build tagged per milestone), shared black-box acceptance suite, milestones, rubrics, disruptions, consequences | A tester completes Taskly end to end in **both** variants and ends with a working, deployed app |
| **v2-3 Work Experience pilot** (doc 03) | PantryLink conversion: monorepo template, reference build, API contract, roles (Backend / Frontend / Full-stack), teammate PR schedule, procedures, AI PR review | A tester completes PantryLink as Frontend and as Backend; the final repo runs as a complete app |
| **v2-4 Scale** (doc 04) | More projects, variants and roles using the authoring recipe; convert remaining classic projects | Each new project passes the authoring QA checklist |

---

## 7. Open Questions (to confirm before or during v2-1)

1. **Public or private learner repos?** GitHub Actions minutes are free for public repos on standard runners; private repos draw from the learner's monthly free minutes. *Proposal:* default public ("it's your portfolio"), allow private with a warning.
2. **Learners without GitHub** — v2 projects require a (free) GitHub account. Classic projects remain for anyone who can't or won't. *Confirm this is acceptable.*
3. **Deploy milestone** — the Build pilot's last milestone deploys the app to a free host. Free hosting offers change often; the doc names candidates but the final choice is made when authoring. *Or make deploy an optional stretch?*
4. **Deadlines in Work Experience** — real calendar deadlines (e.g. sprint ends in 7 days) or simulated time only? *Proposal:* soft, simulated deadlines shown on the board; missing one produces a stakeholder message, not a lock-out.
5. **AI review comments on GitHub** — should the "senior reviewer" post directly on the learner's PR (most realistic), or only inside Areyna? *Proposal:* on the PR, with an Areyna-side copy.
