# IWSTGS — Phase Generation Guidance Prompt

> **What this file is for:** Paste this entire document into a fresh conversation with Claude
> (claude.ai or any Claude session) whenever you ask it to generate a new phase document
> (Phase 3, Phase 4, ... Phase 11) for IWSTGS. It tells Claude how to stay inside the system's
> actual architecture and not invent scope. Always attach the four source documents alongside
> this prompt (see Section 1).
>
> This is not a one-time read. Re-read Section 4 (Non-Negotiable Constraints) and Section 7
> (Scope Discipline) before approving any phase doc Claude produces.

---

## 1. Required Context — Attach All of These Every Time

Before asking Claude to write a phase document, attach or paste the full content of:

1. **`IWSTGS_Business_Logic_Document.docx`** — the WHY. Defines rank architecture, CAC model,
   AIMediation boundaries, evaluation framework, progression logic. This is the authoritative
   source for *system behavior and rules*.
2. **`IWSTGS_Data_Model_v2.docx`** — the WHAT. Defines every table, every field, every enum.
   This is the authoritative source for *schema*. If a phase doc's migration disagrees with
   this document, the migration is wrong, not the other way around.
3. **`IWSTGS_Integration_Spec_v2.docx`** — the HOW. Defines the bounded context map, the
   Project→Scenario→Task hierarchy, the execution flow, and which context owns which table.
   This is the authoritative source for *module boundaries and data flow*.
4. **`IWSTGS_Implementation_Plan.md`** — the WHEN. Defines the 11-phase breakdown and what
   belongs in each phase. Treat this as a sequencing guide, not a schema source — if it
   conflicts with the Data Model on a field name or table structure, the Data Model wins
   (the Implementation Plan was written earlier and may be stale on details).
5. **All completed phase documents** (`IWSTGS_Phase_0_Foundation.md` through the most recently
   completed phase). These establish the actual file structure, naming conventions, and
   pedagogical style already in use. A new phase document must extend this pattern, not
   invent a new one.
6. **This file.**

If any of these is missing from the conversation, tell Claude to stop and ask for it rather
than generating from memory or assumption.

---

## 2. Source-of-Truth Hierarchy

When two documents disagree, resolve the conflict in this order:

```
Data Model v2          — wins on: table names, field names, types, enums, nullability
Integration Spec v2    — wins on: which bounded context owns what, data flow, execution order
Business Logic Doc     — wins on: business rules, thresholds, adaptive behavior
Implementation Plan    — wins on: phase sequencing only. Defers to the three above on schema/logic.
Prior phase docs       — wins on: established naming conventions, file structure, code style
```

Never resolve a conflict by inventing a third option. If the four documents are genuinely
silent on something needed for the phase, that is an **open design decision** — flag it
explicitly in the generated phase doc rather than quietly deciding it (see Section 8).

---

## 3. Project Identity — What This System Actually Is

IWSTGS is a Laravel 13 / DDD modular monolith that simulates real software engineering work
for learners. It is **not** a generic LMS, **not** a coding-exercise platform, and **not** a
chatbot tutor. Every phase document must serve the actual mechanics: rank progression, the
CAC model (Complexity/Autonomy/Context-fidelity), the three-tier Project→Scenario→Task content
hierarchy, four-layer submissions, and AIMediation operating strictly within rule-defined
boundaries (it evaluates against criteria — it does not improvise rubrics).

If a proposed phase feature doesn't trace back to a specific section of the Business Logic
Document or Integration Spec, it does not belong in the phase document. Flag it, don't build it.

---

## 4. Non-Negotiable Architectural Constraints

### 4.1 The 11 Bounded Contexts (fixed — do not add, rename, or merge)

| Context | Owns |
|---|---|
| Identity | Authentication, user accounts, RBAC |
| Organizations | Organisation registration, org-specific config |
| Content | Default platform content management, versioning |
| Simulation | Project/scenario/task blueprints, rubric definitions |
| SimExecution | Live learner sessions, sprint board, enrolments, injected cards |
| Submission | Submission packages, artifact storage, code execution |
| EvalEngine | Evaluation results, dimension evaluations, follow-up prompts |
| AIMediation | Claude API integration — service only, owns no downstream state |
| LearnerProfile | Profile, rank events, dimension scores, gap/habit/mismatch flags |
| Competency | Competence dimension definitions, role qualification thresholds |
| Reporting | Read-only dashboards and analytics — writes to nothing |

### 4.2 The Three-Tier Content Hierarchy (fixed)

```
ProjectTemplate (the umbrella — business context, stakeholders, tech context)
    └── ScenarioTemplate (the chapter — narrative, situation trigger, reference materials)
        └── Task (the work item — brief, CAC variants, deliverables, rubric criteria)
```

Every new content table belongs under exactly one of these three tiers, or under
Competency/EvalEngine if it is dimension/rubric infrastructure. If you can't say which tier
a proposed table belongs to, it is not ready to be designed.

### 4.3 Standing Architectural Decision — `users` vs `learner`

The Data Model v2 specifies a single `learner` table under Identity, with auth fields and
learner-specific fields combined. **The actual implementation deliberately diverges from this:**

- `users` table (Identity module) — generic platform account: auth, RBAC roles
  (`super_admin`, `org_admin`, `content_author`, `learner`)
- `learners` table (SimExecution module) — learner-specific enrollment data: `entry_category`,
  `years_experience`, `organisation_id`, FK to `users`

**Reason:** Not every platform account is a learner. Content authors and org admins need
`users` rows without learner-specific fields. This split is correct DDD and should be
preserved in every future phase. When the Data Model v2 document says "learner", read it as
"the `users` + `learners` pair" unless the field is clearly auth-only (in which case it's on
`users`) or clearly enrollment-only (in which case it's on `learners`).

Do not attempt to "fix" this by merging the tables. Do not regenerate a single `learner` table
because the Data Model says so. This is a documented, intentional decision.

### 4.4 DDD Layering (fixed, applies to every module)

```
Domain/         — entities, value objects, domain events, repository interfaces. Zero framework code.
Application/    — commands, queries, handlers, DTOs. Orchestrates domain + infrastructure.
Infrastructure/ — Eloquent models, mappers, repository implementations, service providers.
Presentation/   — controllers, form requests, middleware, views.
```

No business logic in controllers. No Eloquent queries in domain classes. Every module's
service provider loads its own routes in `boot()` (see Section 4.6).

### 4.5 Migration Numbering

Use `php artisan make:migration <descriptive_name>` with no manual sequence prefix unless
the existing phase docs use one — check the most recent phase document's convention before
choosing. Laravel prepends a timestamp automatically; sequencing is handled by the timestamp,
not by the name. Always state the exact migration order (which table must exist before which)
the way Phase 2 did, because FK dependencies are real and migrations run in filename order.

### 4.6 Route Loading

Every module that owns routes loads them in its own service provider's `boot()` method:

```php
public function boot(): void
{
    Route::middleware('web')->group(base_path('routes/<module>.php'));
}
```

Do not introduce a second centralized route loader. (`ModulesServiceProvider` exists as a
legacy registry for not-yet-migrated modules only — do not add new modules to it.)

---

## 5. Pedagogical Style — Required for Every Phase Document

The user is using this project to learn PHP, Laravel, DDD, and software architecture. Every
phase document must teach, not just instruct. Mirror the style already established in Phase 0–2:

- **Explain WHY before WHAT.** Every table, every non-obvious column gets a short paragraph
  explaining the reasoning (see how Phase 2 explains `rubric_set_id` nullability, or why
  `competence_dimensions` has no `timestamps()`).
- **Call out new PHP/Laravel syntax** the first time it appears, with a short "what this does
  and why it matters" note (Phase 2's "PHP 8+ Syntax Used in This Phase" section is the
  template for this).
- **Migration-first, classes-second.** If a phase introduces new tables, do migrations only
  in that phase; defer Eloquent models, repositories, and handlers to the next phase, exactly
  as Phase 2 deferred Phase 3's models.
- **Concrete bash commands** for every artisan command the user needs to run, in the order
  they need to run them.
- **Checklists** (`- [ ]`) after every migration/file, and a full verification checklist at
  the end of the phase covering: migration integrity, table existence, data checks,
  structural checks (FKs, composite uniques), enum checks, and a rollback check.
- **A "Known Issues to Fix" section** if review of the current codebase surfaces bugs —
  Phase 2 did this for Phase 1's bugs. Do the same for the phase being closed out.
- **A "What the Next Phase Will Build On Top of This" closing section**, so the user
  understands why the current phase stops where it does.

---

## 6. Process for Generating a New Phase Document

1. Identify which phase is being requested and confirm it's the **next sequential phase** —
   never skip ahead. If the user asks for Phase 5 while Phase 3 isn't done, say so and stop.
2. Re-read the Implementation Plan's section for this phase to get the scope boundary.
3. Cross-reference every table/field/enum mentioned against Data Model v2. If the
   Implementation Plan's phase description references a table not yet in the Data Model, or
   describes fields that don't match, flag the conflict before writing anything.
4. Cross-reference the bounded context assignment against the Integration Spec's context map.
5. Check the most recently completed phase document for the actual current file/folder/naming
   conventions in use — do not assume the Implementation Plan's naming is current if a later
   phase doc changed it.
6. Draft the phase document following the structure in Section 5.
7. Before presenting it, run the self-check in Section 7.

---

## 7. Scope Discipline — Self-Check Before Presenting a Phase Document

Run through this list before showing the phase document to the user:

- [ ] Every table introduced exists in Data Model v2 with the same name and the same fields.
      If it doesn't exist there, it does not belong in this phase.
- [ ] Every table is assigned to the correct bounded context per the Integration Spec.
- [ ] No table from a *later* phase has been pulled forward (e.g., do not create
      `learner_session` while still in the Phase 2 content-schema stage).
- [ ] No PHP classes were generated in a migrations-only phase (mirror Phase 2's rule).
- [ ] The `users`/`learners` split (Section 4.3) was respected, not "corrected."
- [ ] Every enum column's allowed values match Section 14 (Enum Reference) of Data Model v2
      exactly — no invented or renamed enum values.
- [ ] Every new domain enum (PHP backed enum) mirrors its corresponding DB enum exactly.
- [ ] FK `cascadeOnDelete` / `restrictOnDelete` / `nullOnDelete` choices match the reasoning
      style already used (state the reasoning, don't just declare the FK).
- [ ] The phase document closes with a verification checklist the user can mechanically run.
- [ ] Anything not resolvable from the four source documents is listed as an open question
      instead of being silently decided.

If any box can't be checked, the phase document is not ready.

---

## 8. Handling Genuinely Open Design Decisions

Integration Spec v2 Section 20 lists unresolved questions (e.g., human review queue SLA,
situation_trigger UI rendering, proactive guidance prompt timing). If a phase you're
generating touches one of these areas, do not invent an answer. Reproduce the open question
in the phase document under a clearly marked "Open Design Decision — Needs User Input" callout
and ask the user to resolve it before the phase proceeds.

---

## 9. The Literal Prompt To Paste to Claude

Copy everything below this line into your message to Claude, after attaching the four source
documents and the most recent phase document(s):

```
I'm building IWSTGS — read the attached Business Logic Document, Data Model v2, Integration
Spec v2, Implementation Plan, and the most recent phase document(s). I'm using this project to
learn PHP, Laravel, and DDD architecture, so the phase document needs to teach, not just
instruct (see the style of the existing Phase 0–2 documents).

I also have a "Phase Generation Guidance Prompt" file (attached) — follow it exactly. In
particular:
- Resolve conflicts using the source-of-truth hierarchy in Section 2.
- Respect the 11 fixed bounded contexts and the Project→Scenario→Task hierarchy in Section 4.
- Respect the users/learners architectural split in Section 4.3 — do not "fix" it.
- Follow the pedagogical style in Section 5.
- Run the self-check in Section 7 before presenting the document to me, and tell me which
  boxes you checked.
- If you hit an open design decision per Section 8, stop and ask me rather than deciding it.

Generate Phase [N]: [phase name from the Implementation Plan].
```

---

## 10. Maintenance Note

If you (the user) make a deliberate architectural decision that diverges from the source
documents — the way Section 4.3 documents the `users`/`learners` split — add it to Section 4
of this file immediately. This file is only useful if it stays current with real decisions,
not just the original spec.
