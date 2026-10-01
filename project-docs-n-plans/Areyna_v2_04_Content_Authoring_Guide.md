# Areyna v2 — 04: Content Authoring Guide

> **Goal:** a repeatable recipe for adding a v2 project, a new stack variant of an existing project, or a new role — so the platform can keep growing without re-deciding the structure each time. Follow the steps in order; each ends with a check.

---

## 1. The Artefacts of a v2 Project

| Artefact | One per… | Lives in |
|---|---|---|
| Product spec (features, persona/stakeholders, milestone list, `data-testid` / API contract) | project | `project-docs-n-plans/Areyna_Project_<Name>.md` |
| Acceptance suite (black-box tests tagged by milestone) | project | `areyna-templates/<project>-acceptance` (tagged `vX.Y.Z`) |
| Reference build (tag per milestone / teammate event) | stack variant | `areyna-templates/<project>-<variant>-reference` (private) |
| Starter template (tag `m0` of the reference, trimmed) | stack variant | `areyna-templates/<project>-<variant>` (public, marked as template) |
| Areyna content (project, variants, scenarios, tasks, variant specs, rubrics, events, consequences, suggestions) | project | seeder in `database/seeders/`, using `AuthorsSimulationContent` |

---

## 2. Recipe: a New Project

### Step 1 — Product spec
- Decide the track (Build or Work Experience) and the difficulty.
- Write the **final** feature list first, then slice it into 5–9 milestones across 3 chapters/sprints. Each milestone must leave the app **working** and add something visible or testable.
- Define the **contract** the tests will rely on: `data-testid`s for UI, endpoints and status codes for APIs.
- Write the story: persona/stakeholders, one line per chapter, 1–2 scripted events per chapter.
- WE only: roles, which directories each owns, teammate PR schedule, procedures (start from PantryLink's).

✅ *Check:* every milestone has a one-sentence "done when…" that a test can verify.

### Step 2 — Reference build (first variant)
- Build the app **milestone by milestone**, tagging `m1`, `m2`, … (WE: also per teammate event, e.g. `api-s2-distributions`).
- Keep each tag's diff close to what a learner would write — this is also the calibration for the AI reviewer.

✅ *Check:* `git checkout m<n> && npm start` runs for every tag.

### Step 3 — Acceptance suite
- Black-box only (HTTP + Playwright), reading `areyna.json` for how to start the app.
- One test ID per behaviour, `T<milestone>.<n>` (WE: `S<sprint>-<ticket>.<n>`); keep titles readable — learners see them.
- Emit `areyna-report.json` (doc 01 §4.4).
- In the reference repo's CI, for every tag, assert that **exactly** the cumulative tests pass (tests for later milestones must fail — proves they test something new).

✅ *Check:* the matrix "tag × test → expected" is fully green in CI.

### Step 4 — Starter template
- Start from `m0`: the smallest runnable app plus `areyna.json`, the workflow, and a README (how to run, how to submit, the contract).
- Mark the repo as a GitHub template. Record the workflow file's content hash in the variant row (doc 01 §4.2).

✅ *Check:* create a fresh repo from the template → the workflow runs → only start-up tests pass.

### Step 5 — Areyna content (seeder)
- Project (`track`), stack variant(s), scenarios, milestone tasks (`task_type = milestone`), `task_variant_specs` (brief addendum, test IDs, `reference_tag`), rubric criteria (2–3 per milestone, stack-neutral), CAC variants (low = starting hint, high = stretch), scenario events, consequence tasks (one per milestone + one regression), suggestion tasks (one per chapter), backlog items (WE).
- Rubric criteria describe **qualities of the work** (validation on the server, stable IDs, clear README), never a specific stack's API — that's what keeps them shareable across variants.

✅ *Check:* `php artisan db:seed` runs twice without errors; the admin "Verify" on each variant is green.

### Step 6 — Walkthrough
- A tester completes the project in each variant on a real GitHub account, deliberately failing one milestone and causing one regression.
- Record time per milestone (feeds the explainer).

✅ *Check:* the project's pilot/exit criteria (see docs 02/03 for examples) are met. Then publish.

---

## 3. Recipe: a New Stack Variant of an Existing Project

1. Build a new reference repo against the **same** contract and milestone list; tag `m0`…`mN`.
2. Run the **existing** acceptance suite against every tag (fix the build, not the tests; if a test is genuinely stack-biased, fix the test and bump the suite version for all variants).
3. Make the starter template from `m0`.
4. Seed: one `project_stack_variants` row (difficulty relative to the other variants, rank gate) + one `task_variant_specs` row per milestone (addendum, hints, same test IDs, `reference_tag`).
5. WE: tag teammate events in the new reference build with the same event names.
6. Walkthrough as in §2 step 6.

**Choosing difficulty:** a variant is harder when it adds moving parts the learner must understand (a separate frontend, a type system, a compile step, async data, mobile build tooling), not when it's merely less familiar.

---

## 4. Recipe: a New Role

1. Add a `role_definitions` row: title, `specialization_tags` (the new tag), `min_years_experience`, dimension weights (what this role is judged on most), thresholds.
2. Add the tag to the project's `specialization_tags` and to the `role_tags` of the tickets that role does.
3. Decide what the team provides this role (teammate PRs) and what this role provides others; author the events.
4. If the role needs different evidence (e.g. QA: bug reports and test plans; UI/UX: design files; DevOps: pipeline config), add the deliverable types and rubric criteria; black-box tests can still verify DevOps work (e.g. the app deploys, health check passes).

Planned roles and the kind of evidence they produce:

| Role | Primary evidence |
|---|---|
| Frontend / Backend / Full-stack | Code + tests + PRs |
| Mobile | App code; tests run in an emulator/runtime in CI (needs a mobile-specific workflow) |
| QA | Test plans, automated tests added to the repo, bug reports on teammate PRs |
| DevOps | CI/CD config, Dockerfiles, deploy success, monitoring checks |
| UI/UX | Design files and specs (uploaded artefacts), reviewed by AI against a brief; implemented by "the team" |
| Data | Queries, reports, notebooks in the repo; tests check outputs against fixtures |

---

## 5. Converting a Classic Project

1. Keep the story, stakeholders, vault and the *intent* of each task; turn tasks into tickets that build **one** product.
2. Turn classic consequence tasks into incidents/consequences against the learner's own code.
3. Build the product spec → reference → suite → template → seeder exactly as §2.
4. Publish v2; unpublish classic for **new** enrolments only.

---

## 6. Authoring QA Checklist

- [ ] Every milestone leaves the app runnable and adds something testable.
- [ ] Tag × test matrix green in the reference repo's CI.
- [ ] Starter template runs and passes only start-up tests.
- [ ] Contract (`data-testid`s / API) is documented in the template README.
- [ ] Rubric criteria are stack-neutral; per-stack advice lives in `brief_addendum`.
- [ ] Each milestone has a consequence; each chapter a suggestion; one regression consequence exists.
- [ ] Scripted events read naturally in the persona's voice and never contradict the brief.
- [ ] WE: teammate PRs apply cleanly to a repo that followed the tickets; each reviewed PR contains exactly one seeded, fair issue.
- [ ] Seeder is re-runnable; admin "Verify" passes for every variant.
- [ ] Real walkthrough done per variant; time per milestone recorded.

---

## 7. Naming Conventions

| Thing | Pattern | Example |
|---|---|---|
| Project key | lowercase word | `taskly`, `pantrylink` |
| Variant key | `<backend>-<frontend>` or single stack | `express-ejs`, `express-react`, `laravel-blade` |
| Template repo | `<project>-<variant>` | `taskly-express-ejs` |
| Reference repo | `<project>-<variant>-reference` | `taskly-express-react-reference` |
| Acceptance repo | `<project>-acceptance` | `taskly-acceptance` |
| Milestone tags | `m<n>` | `m4` |
| Teammate event tags | `<area>-s<sprint>-<slug>` | `api-s2-distributions` |
| Test IDs | Build `T<m>.<n>`; WE `S<s>-<ticket>.<n>` | `T3.3`, `S2-PL8.2` |
| Ticket numbers (WE) | `<PROJECT>-<n>` | `PL-8` |
