# Areyna v2 — 02: Build Pilot — "Taskly" (To-do List)

> **Goal:** the first Build-track project. A learner starts from an almost-empty repo and, over 3 chapters and 7 milestones, builds a complete, deployed to-do app — in either of two JavaScript stack variants. Along the way they meet light, friendly versions of real work: a user who lost her data, a change of requirements, feedback from a phone user, and consequences when their own change breaks something.
>
> Depends on doc 01 (platform). Authoring follows doc 04.

---

## 1. The Product

**Taskly** — a personal to-do list.

Final feature set (after Milestone 7):
- see all tasks; add a task (validated title, optional due date);
- mark complete / not complete; edit a title or due date; delete;
- overdue tasks are clearly marked;
- filter All / Active / Completed, with an "N items left" counter;
- data survives restarts (SQLite);
- works on a phone, has accessible form labels and button names;
- has a README a stranger can follow, and is deployed at a public URL.

**Persona (the "client")**: **Nora Adeyemi**, a nursing student who wants a simple list she can open on her phone between shifts. She appears in chapter messages; she is friendly, non-technical and specific.

---

## 2. Stack Variants

| | **A — Express + EJS** | **B — Express API + React** |
|---|---|---|
| Key | `express-ejs` | `express-react` |
| Difficulty | 1 | 2 |
| Rank gate | none | Junior-2 (confirm) |
| How it works | One Express app renders HTML with EJS templates; forms post and redirect (Post/Redirect/Get) | `api/`: Express JSON API. `web/`: React (Vite). In production `npm start` builds `web/` and serves it from Express, so there is one URL |
| New concepts vs A | — | JSON APIs, `fetch`, component state, loading/error states, keeping UI and server in sync |
| Estimated effort | 8–12 h | 14–20 h |

### 2.1 Starter templates (Milestone 0)

**A — `areyna-templates/taskly-express-ejs`**
```
package.json          # express, ejs; scripts: start, dev
src/server.js         # express app, GET / renders views/index.ejs
views/index.ejs       # <h1 data-testid="app-title">Taskly</h1>, empty <ul data-testid="task-list">
public/styles.css
areyna.json           # §4.3 of doc 01
.github/workflows/areyna.yml
README.md             # how to run locally, how to submit a milestone
```

**B — `areyna-templates/taskly-express-react`**
```
package.json          # workspaces: api, web; scripts: start (build web + start api), dev (both)
api/src/server.js     # express, GET /api/health, serves ../web/dist in production
web/                  # Vite + React; App.jsx renders the title + empty list
areyna.json
.github/workflows/areyna.yml
README.md
```

Both starters **pass no milestone tests** except the ones that only check the app starts. They are intentionally small: the learner should feel they built the app, not filled in blanks.

### 2.2 Reference builds (private)

`areyna-templates/taskly-express-ejs-reference` and `…-express-react-reference`, each with tags `m0` (identical to the starter) through `m7`. Authoring QA (doc 04) runs the acceptance suite against every tag and expects exactly the cumulative tests to pass.

---

## 3. The `data-testid` Contract

The acceptance suite (`areyna-templates/taskly-acceptance`) only relies on these. Both variants must use them; the README of each template lists them, and milestone briefs mention the ones they introduce.

| Test ID | Element | Introduced |
|---|---|---|
| `app-title` | Page heading containing "Taskly" | M0 |
| `task-list` | The list container | M0 |
| `task-item` | One task (attribute `data-completed="true|false"`, `data-overdue="true|false"`) | M1 |
| `task-title` | Title text inside an item | M1 |
| `new-task-form`, `new-task-title`, `new-task-submit` | Add form, title input, submit button | M2 |
| `form-error` | Visible validation message | M2 |
| `task-toggle`, `task-delete` | Complete toggle, delete button inside an item | M3 |
| `task-edit`, `edit-task-title`, `edit-task-due`, `edit-task-save` | Edit controls | M5 |
| `new-task-due`, `task-due` | Due date input / displayed due date | M5 |
| `filter-all`, `filter-active`, `filter-completed`, `items-left` | Filters and counter | M6 |

---

## 4. Chapters, Milestones and Tests

Chapter = scenario. Milestones unlock one at a time (doc 01 §6). Default autonomy climbs across chapters: **low → mid → high**, and CAC adapts per learner as usual (low = a starting hint, high = a stretch goal).

### Chapter 1 — "Get it on the screen" (autonomy: low)

> *Narrative:* Nora messaged a friend of a friend (you) asking if you could build her a simple to-do list. You said yes. Time to get something on the screen.
>
> *Event E1 (scenario_start, stakeholder_message)* — Nora: "Honestly anything that lets me see my list on my phone would be amazing. No rush!"

**M1 — Show a list of tasks**
- Brief: run the starter locally; show three example tasks in the list.
- A: render an array from the route into the template. B: return tasks from `GET /api/tasks`, fetch them in React.
- Tests: `T1.1` home page loads and shows `app-title` · `T1.2` exactly 3 `task-item`s with non-empty `task-title`.
- Rubric: implementation (it renders from data, not hard-coded HTML) · communication (explanation says what was changed and how to run it).

**M2 — Add a task**
- Brief: a form to add a task. Titles are required, trimmed, max 120 characters. Show a clear error when invalid.
- A: `POST /tasks` then redirect to `/` (explain why redirect). B: `POST /api/tasks` returns `201` + the task, `422` + message when invalid; the list updates without a reload.
- Tests: `T2.1` adding "Buy milk" shows it in the list · `T2.2` empty/whitespace title shows `form-error` and adds nothing · `T2.3` 121-character title rejected.
- Rubric: implementation (validation on the **server**, not only in the browser) · problem analysis (edge cases named in the explanation).
- Low CAC hint: "Validate on the server first; browser validation is a convenience." High CAC stretch: "Keep what the user typed in the input after a validation error."

**M3 — Complete and delete**
- Brief: toggle a task complete/incomplete; delete a task.
- Tests: `T3.1` toggling sets `data-completed="true"` and back · `T3.2` delete removes exactly that task · `T3.3` with 3 tasks, toggling/deleting the middle one leaves the other two unchanged (catches index-based IDs).
- Rubric: implementation (stable IDs, correct HTTP methods) · debugging (if they hit the index bug, the explanation of the fix).

### Chapter 2 — "Make it last" (autonomy: mid)

> *Event E2 (scenario_start, stakeholder_message)* — Nora: "I closed the tab last night and all my tasks were gone 😭 Is that normal?"

**M4 — Keep tasks after a restart**
- Brief: store tasks in SQLite so they survive restarts. The database file must not be committed.
- A/B: same requirement; B keeps it in `api/`.
- Tests: `T4.1` tasks added before a restart are still there after (the suite restarts the process) · `T4.2` IDs are unchanged after restart · all T1–T3 still pass.
- Rubric: design (data access kept out of route handlers, e.g. a small `tasks` module) · implementation (parameterised queries, not string-built SQL) · communication (explains why the DB file is git-ignored).

> *Event E3 (after_task M4, requirement_change)* — Nora: "It works!! Two things: could tasks have a due date? And I keep making typos — can I fix a task instead of deleting it?"

**M5 — Edit tasks and add due dates**
- Brief: edit a task's title; optional due date when adding/editing; show the date in a readable format; mark tasks past their due date as overdue.
- Tests: `T5.1` editing a title updates it everywhere · `T5.2` a due date is saved and displayed in `task-due` · `T5.3` a task due yesterday has `data-overdue="true"`, one due tomorrow `false` · `T5.4` an invalid date is rejected with `form-error`.
- Rubric: problem analysis (what "overdue" means: completed tasks are not overdue; "today" is not overdue) · implementation (date handling, time zones acknowledged).

### Chapter 3 — "Make it yours and ship it" (autonomy: high)

> *Event E4 (scenario_start, stakeholder_message)* — Nora: "I've got 30 tasks now and it's getting messy. Can I just see what's left?"

**M6 — Filters and counter**
- Brief: All / Active / Completed filters and an "N items left" counter.
- A: filter via query string (`/?filter=active`) so it survives refresh. B: filter in state *and* reflect it in the URL.
- Tests: `T6.1` each filter shows the right items · `T6.2` `items-left` counts active tasks and updates after toggling · `T6.3` the chosen filter survives a page reload.
- Rubric: design (one source of truth for the filtered list) · implementation.

> *Event E5 (after_task M6, stakeholder_message)* — Nora: "Showed my classmates! One of them uses a screen reader and couldn't tell what the buttons did. Also the buttons are tiny on my phone."

**M7 — Ship it**
- Brief: accessible labels and button names; comfortable on a phone; a README a stranger can follow; deploy to a free host and share the URL.
- Tests: `T7.1` every input has a label and every button an accessible name (automated a11y check on the main page) · `T7.2` no horizontal scrolling at 375 px wide · `T7.3` *(if a URL is submitted)* the deployed URL responds with `app-title`.
- Rubric: communication (README: what it is, how to run, how to test, known limitations) · implementation (a11y fixes) · problem analysis (what they chose not to do and why).
- Deploy target: chosen at authoring time among current free hosts; if none is suitable, M7's deploy part becomes an optional stretch (Overview §7.3).

---

## 5. Adaptive Content

### 5.1 Consequence tasks (injected on failure, same repo)

| Trigger | Consequence task |
|---|---|
| M2 fails server-side validation tests | **"Blank tasks keep appearing"** — Nora sends a screenshot of empty tasks. Fix validation on the server; explain why the browser check wasn't enough |
| M3 fails `T3.3` | **"I deleted one task and a different one vanished"** — fix IDs; explain the root cause |
| M4 fails `T4.1`/`T4.2` or the DB file is committed | **"My tasks disappeared after your update"** — make storage survive restarts; keep data out of git |
| Any **regression** (earlier milestone's test fails) | **"Something that used to work broke"** — Nora reports the broken feature in her words; find what changed (hint: `git diff`), fix it, and say how you'll catch it next time |

### 5.2 Suggestion tasks (injected at chapter transitions on a habit)

| Chapter | Suggestion |
|---|---|
| 1 | **Commit messages that explain why** — rewrite the last three commit messages (in the explanation) so someone else would understand them |
| 2 | **Keep data access separate** — move all SQL into one module and explain the benefit |
| 3 | **A README a stranger can follow** — ask a friend (or rubber duck) to follow it; record what they got stuck on |

---

## 6. Content Mapping to the Engine

| Engine concept | Taskly |
|---|---|
| Project | Taskly, `track = build`, difficulty beginner |
| Stack variants | `express-ejs` (1), `express-react` (2) |
| Scenarios | 3 chapters |
| Tasks | 7 milestones (`task_type = milestone`), 4 consequence tasks, 3 suggestion tasks |
| `task_variant_specs` | 14 rows (7 milestones × 2 variants): addendum, test IDs, reference tag |
| Scenario events | E1–E5 |
| Rubric criteria | 2–3 per milestone, stack-neutral |
| Dimensions exercised | Implementation, problem analysis, design, debugging, communication; testing through the "how I checked it" part of explanations (a later Build project introduces writing tests) |

---

## 7. Pilot Exit Criteria

- A tester completes Taskly end to end in **both** variants with a real GitHub account, ending with a deployed app (or the optional-deploy fallback).
- Every reference tag passes exactly its cumulative tests in CI.
- At least one regression and one validation failure were triggered deliberately and produced the right consequence task.
- Average time per milestone recorded for both variants (feeds the explainer's time estimate).
