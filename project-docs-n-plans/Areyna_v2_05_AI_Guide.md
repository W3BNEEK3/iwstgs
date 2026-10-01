# Areyna v2 — 05: The AI Guide (Tiroco)

> **Goal:** turn Tiroco from a set of fixed page tips into a **guide that watches how each learner is doing and speaks up at the right moment**: a hint when they're stuck, an explanation when something new happens, a tip on using Areyna better, a good outside resource for a skill they keep struggling with, and a short note when a new learner feature ships. The learner can always turn it off.
>
> Decision D19 (Overview §7). Works for **classic, Build and Work Experience** projects, so it can ship before, during or after v2-1.

---

## 1. What Tiroco Says: Five Kinds of Message

| Kind | When | Who writes it | Example |
|---|---|---|---|
| **Walkthrough** (exists today) | First visit to a page, or a milestone flag on it | Authored (`GuideCatalog`) | "This is your sprint board. Cards move left to right as you work." |
| **Nudge** (new) | Something Tiroco *observed* about this learner | AI, from a trigger (§3) | "Your last two tries at this task lost marks on edge cases. Before you submit again, list what could go wrong with the input." |
| **Tip** (new) | A quiet moment (after a pass, on the dashboard) | Library of authored tips, **picked and phrased** by AI for this learner | "Write your explanation before you polish the code. It often shows you a gap." |
| **Resource** (new) | A skill stays weak across several tasks | AI picks from an **admin-curated list** (§5); never invents links | "Your tests keep missing boundary dates. The *Testing* chapter on javascript.info has short exercises on exactly that." |
| **Announcement** (new) | A new learner feature is live | Admin notes → AI drafts a short summary → admin approves (§6) | "New: you can now see your full submission history on your profile. Useful for spotting your own patterns." |

The **project explainer** (AI-written, cached per project) stays as it is.

**The rule behind all of this: rules decide *when* Tiroco speaks; the AI decides *what to say*.** Deterministic triggers keep Tiroco predictable, cheap and never spammy. The AI makes each message specific to the learner, grounded in facts we pass it.

---

## 2. What Tiroco Can See (the Learner Snapshot)

When a trigger fires, a `LearnerSnapshotBuilder` assembles a compact, factual summary. Only this snapshot is sent to the AI, never raw submissions from other learners and never personal data beyond the learner's first name.

| Signal | Source (exists today unless marked *new*) |
|---|---|
| Rank, experience level, track, project, role, stack variant | `learner_profiles`, `learner_sessions`, v2 variant |
| Current page, task / milestone, CAC levels | route + session |
| Last 5 evaluations: pass/fail, tier per dimension, `criteria_missed`, `gap_type` | `evaluation_results`, `dimension_evaluations` |
| Dimension trends (improving / flat / falling) | `dimension_scores` |
| Habit flags, gap flags | `habit_flags`, `gap_flags` |
| Attempts on the current task, the last explanation's length | `submission_packages` |
| Time on the current task | reported by the task page after 25 minutes (`POST /guide/stuck`, verified server-side) |
| Days since last visit | `user_guide_preferences.last_active_at` (*new*) |
| CI results and regressions (v2) | `submission_packages.ci_*` |
| Consequence / suggestion cards injected, rank events | `sprint_board_events`, `rank_events` |
| What Tiroco already said recently, and what the learner dismissed or rated | `guide_messages` (*new*) |

---

## 3. Triggers (When Tiroco Speaks)

Each trigger has a condition, a **cooldown** and a place in the priority order (`TriggerCatalog`). Triggers run at three moments: after an evaluation (a Guidance listener on `EvaluationComplete`, registered after `PostEvaluationRouter`), when the guide card asks for its message after a page has loaded (cheap checks only), and when the task page reports a learner has been on it for 25 minutes.

> **Built in the first release:** `repeat-fail`, `stuck-idle`, `thin-explanations`, `rank-change` (up or down), `welcome-back`, `weak-dimension`, `quiet-moment`, `announcement`.
> **Deferred:** `skipped-materials` and `hint-reliance` need the task page to record when materials and hints are opened (today both are always visible, so there is nothing to observe); `first-consequence` is already covered by the authored "An amber card appeared" walkthrough; `ci-failing` and `regression` arrive with v2-1.

### 3.1 Pilot trigger catalogue

| Key | Condition | Kind | Cooldown | What the message should do |
|---|---|---|---|---|
| `repeat-fail` | 2nd failed attempt on the same task | nudge | per task | Name the missed criterion in plain words; suggest one concrete way to approach it; point to hints/materials. **Never** the answer |
| `stuck-idle` | On a task page 25+ min, no draft saved, no submission | nudge | 1/day | Ask if they're stuck; suggest the hint button or reference materials; remind them explanations can describe partial work |
| `skipped-materials` | Failed with `gap_type = context` and never opened reference materials | nudge | 1/week | Explain that the vault holds the facts the task relies on |
| `thin-explanations` | Communication below proficient 2× and explanations under ~60 words | nudge | 1/week | Show a 3-part structure: what I did, why, how I checked it |
| `hint-reliance` | Revealed all hints on 3 tasks in a row but passing well | nudge | 1/week | Encourage trying first without hints; explain that CAC will give more room |
| `first-consequence` | First consequence card injected | nudge | once | Explain what consequence tasks are and that they're normal, not a penalty |
| `rank-up` / `rank-support` | Rank event | nudge | per event | Celebrate or reassure; explain what changes (autonomy, roles unlocked) |
| `welcome-back` | Returns after 5+ days away | nudge | per return | One-line recap: project, where they stopped, what's next |
| `ci-failing` (v2) | Two submissions in a row with `ci_status = failed` | nudge | per milestone | How to open the Actions log and read the first failing test |
| `regression` (v2) | A regression consequence was injected | nudge | per event | Suggest `git diff` between the last good commit and now |
| `weak-dimension` | Same dimension below proficient in 3 of the last 5 evaluations (reuses `HabitPatternDetector`'s data) | resource | 1 per dimension / 2 weeks | Recommend **one** curated resource that fits the skill, level and stack |
| `quiet-moment` | After a pass, or on the dashboard, and nothing else is queued | tip | 1/day | One tip from the library (§4) that fits this learner and hasn't been shown |
| `announcement` | A published announcement the learner hasn't seen | announcement | once each | Show the approved text (no AI call at display time) |

### 3.2 Frequency caps (never spammy)

- At most **one** unsolicited message per page load, and **3 per day** (announcements and walkthroughs don't count).
- **Priority order** when several are due: walkthrough → announcement → nudge (by trigger priority) → resource → tip. Lower ones wait for a later page.
- **Quiet zones:** no tips or resources during the diagnostic, during an evaluation that's running, or in the first 2 minutes of a task. Nudges during the diagnostic are limited to `stuck-idle`.
- **Back-off:** if a learner dismisses 3 messages of one kind without reading (closed in under 2 s) or rates 2 "not helpful" in a row, that kind is paused for 7 days. Tiroco doesn't tell them; it just gets quieter.

---

## 4. The Tips Library

General advice on using Areyna well is authored by us, not invented by the AI, so it's always accurate. The AI **chooses** the best-fitting unseen tip for this learner and may rephrase it in one or two sentences to connect it to what they're doing.

Starting set (admin-editable, `guide_tips` table):

| Area | Tip |
|---|---|
| Submitting | Write your explanation before polishing the code. Explaining often shows you the gap. |
| Submitting | Say how you checked your work. Reviewers weigh that as much as the code. |
| Hints | Hints cost nothing. Try for 10 minutes first, then use one. |
| Reference materials | Every task's facts live in the vault. Skim it before you start, not after you fail. |
| Feedback | Read "criteria missed" first. It's the fastest route to a pass on the next attempt. |
| Consequences | A consequence task means the story reacted to your work. It's practice, not punishment. |
| Pacing | Short daily sessions beat one long weekend session. Your rank tracks consistency. |
| Profile | Check your competency graph weekly. Pick the lowest dimension and focus there. |
| Git (v2) | Commit small and often, with messages that say *why*. Your history becomes your portfolio. |
| Git (v2) | Run the tests locally before you push. `npm test` is faster than waiting for CI. |
| Procedures (WE) | Fill every PR template section. Reviewers judge communication too. |

Each tip has: `area`, `text`, `applies_to` (tracks/pages/ranks), `active`.

---

## 5. Resource Recommendations (supporting learning outside Areyna)

Tiroco recommends outside resources that support what Areyna trains: reference docs, practice sites, spaced-repetition tools, communities. To keep this trustworthy:

- **Only from an admin-curated list** (`guide_resources`). The resource is **chosen by rules** (matches the weak skill, closest to the learner's level, never recommended before, not opted out of, link check not failing); the AI only writes the one-sentence reason around it. The link on the card comes from the list, never from the AI, so it cannot invent or mis-type one.
- One resource at a time, with a one-sentence reason tied to what the learner is struggling with.
- The learner can mark "Already use it" or "Not for me"; either hides that resource from them for good.
- Nothing is sponsored, and the list is reviewed every quarter for dead links (a scheduled link check flags broken URLs to admins).

Fields: `name`, `url`, `kind` (reference / practice / course / tool / community), `dimensions` (competence dimension IDs), `level` (beginner / intermediate / advanced), `is_free`, `blurb`, `is_active`, `last_checked_at`, `last_check_ok`. (`concept_tags` and `stacks` come with v2, when stack variants exist.) Links are checked weekly by `php artisan guide:check-resources` (scheduled) or from the admin page; a 401/403/429 counts as working because bot protection often answers automated checks that way.

Suggested seed list (verify at authoring time):

| Resource | Kind | Good for |
|---|---|---|
| MDN Web Docs | reference | HTML/CSS/JS, HTTP, accessibility |
| javascript.info | reference + practice | JavaScript fundamentals, async, testing basics |
| The Odin Project | course | Full-stack JS path; good companion for Build track |
| freeCodeCamp | course + practice | Beginners; responsive design, APIs |
| Exercism (JavaScript track) | practice + mentoring | Implementation, problem analysis |
| Learn Git Branching | practice | Git branching and merging (v2) |
| Frontend Mentor | practice | Frontend implementation from designs |
| roadmap.sh | reference | Seeing the bigger picture of a role |
| Anki | tool | Spaced repetition for concepts they keep forgetting |
| web.dev (Learn Accessibility) | course | Accessibility (Taskly M7, PantryLink sign-up page) |
| OWASP Cheat Sheet Series | reference | Security (validation, SQL injection, privacy) |

---

## 6. Feature Announcements (learners only)

When we ship something **learner-facing**, Tiroco tells learners once, briefly.

1. An admin opens **Admin → Guide → Announcements → New** and fills in: internal title, notes on what changed (can be rough or technical), audience (**learners** only; admin-only changes are never announced), the page it relates to (optional link), and an optional **feature flag**.
2. **Generate learner summary**: the AI turns the notes into a short announcement (title ≤ 8 words, body ≤ 40 words, one "Try it" link), written for a junior learner with no internal jargon.
3. The admin edits if needed and **publishes**. Nothing reaches learners without that approval.
4. Learners see it once, as a Tiroco card on their next page load (priority just below walkthroughs), and it stays listed under a **What's new** entry in the user menu.
5. If a feature flag is set, the announcement only shows while that flag is on, so a rolled-back feature is never announced.

This works even when the learner has turned off tips: announcements have their own switch (§7).

---

## 7. Learner Controls

In the Tiroco card (a small ⋯ menu) and on **Profile → Guide settings**:

- **Turn off Tiroco**: exists today; stops everything except the What's new list, which stays readable.
- Per-kind switches: **Hints when I'm stuck** (nudges), **Tips**, **Resource suggestions**, **New feature announcements**. All on by default.
- **Helpful / Not helpful** on every AI message (feeds §9 and the back-off).
- **Why am I seeing this?** shows the trigger in plain words, e.g. "You've tried this task twice."
- **Reset guide** (exists): replays walkthroughs.

---

## 8. How It Works

### 8.1 Data model (as built)

Migration `2026_09_29_100000_create_ai_guide_tables`. No separate activity log was needed: the one fact nothing recorded (when the learner was last here) is a column on the preferences row, and time on a task is reported by the task page itself.

**`guide_messages`**: everything Tiroco decided to say, whether AI-written or not. Keyed by **user** (the guide is per account, and announcements reach users who haven't enrolled yet).

| Column | Type | Notes |
|---|---|---|
| `id` | uuid | |
| `user_id` | uuid FK | |
| `kind` | string | `nudge`, `tip`, `resource`, `announcement` |
| `trigger_key` | string | e.g. `repeat-fail` |
| `context_ref` | string nullable | e.g. task ID, dimension ID, rank event ID; scopes cooldowns |
| `facts` | json | What the trigger saw; the writer's input |
| `title`, `body` | string, text | The authored fallback until written, then the final text |
| `cta_label`, `cta_url` | nullable | Set by the trigger (an internal path, or a curated resource's link), never by the AI |
| `tip_id` / `resource_id` / `announcement_id` | uuid nullable | |
| `status` | string | `pending` (fallback only) → `ready` (written) → `shown` → `dismissed`; `expired` after 48 h unshown |
| `generated_by` | string | `authored`, `ai`, `fallback` |
| `rating` | string nullable | `helpful`, `not_helpful` |
| `shown_at`, `dismissed_at` | timestamps | "Closed unread" = dismissed within 2 s of showing |

**`guide_tips`** and **`guide_resources`** (fields in §4 and §5; both keyed by a stable `key` so re-seeding never overwrites admin edits), **`guide_resource_opt_outs`** (`user_id`, `resource_id`, `reason`), **`feature_announcements`** (`internal_title`, `notes`, `title`, `body`, `link_url`, `feature_flag`, `status` draft/published/archived, `published_at`, `created_by`), **`platform_settings`** (key → JSON; holds `guide.daily_cap` and `guide.triggers`, and later the WE sprint deadline).

**`user_guide_preferences`** (exists) gains `muted_kinds` json (the learner's switches), `paused_until` json (back-off per kind) and `last_active_at`.

The migration also inserts the two feature flags and the starter tips and resources, so an existing install gets a working guide from `php artisan migrate` alone.

### 8.2 Flow (as built)

```
Evaluation complete ─┐   Guide card asks after page load ─┐   Task page: 25 min, no submit ─┐
                     ▼                                     ▼                                 ▼
          EvaluationGuideTriggers              VisitGuideTriggers                 StuckGuideTrigger
                     └──────────────► GuideMessageQueue ◄──────────────────────────────────┘
                        flag · admin trigger switch · learner switches/back-off · cooldown
                        → guide_messages row, status pending, with authored fallback text
                                              │
      GET /guide/next (the card's own background request, after the page has rendered)
                                              ▼
                                        GuideDelivery
            expire stale · quiet zones · daily cap · priority → pick one message
                                              ▼
                                      GuideMessageWriter
       LearnerSnapshotBuilder + GuidePromptBuilder → AI provider → GuideMessageValidator
       valid → generated_by ai · anything else → keep fallback · provider failing → 10 min back-off
                                              ▼
                                  marked shown → Tiroco card
```

**No AI call ever happens while a page renders, and no queue worker is needed.** The AI is called from the guide card's own request after the page is on screen, once per message (a cache lock stops two tabs paying twice). Every trigger writes an authored fallback, so the learner always gets a sensible message even with no AI key configured.

### 8.3 The prompt (outline)

*System:* You are Tiroco, the guide inside Areyna, a platform where junior developers learn by doing realistic project work. Speak like a kind senior colleague: warm, plain English, specific, never patronising. You are given one trigger and facts about the learner. Write **one** short message (title ≤ 8 words, body ≤ 60 words) that helps with exactly that trigger. Rules: never give the solution or code for the task they're working on; refer to hints and materials instead; never grade or predict grades; never mention internal names (CAC, gap_type, dimension IDs); never invent facts or links; use only resource/tip IDs from the lists provided; if the facts aren't enough to say something useful, return `{"skip": true}`.

*User content:* what this situation's message should achieve, the trigger's facts, the learner snapshot (§2), the authored draft (which it may keep), and the last 5 messages Tiroco sent this learner (so it doesn't repeat itself).

*Output (JSON):* `{"title": "...", "body": "..."}` or `{"skip": true}`. The tip, resource and link are fixed by the trigger before the AI is asked, so only the text needs checking: the validator rejects code, URLs, internal jargon and over-long text.

### 8.4 Cost

A learner triggers at most ~3 AI generations a day (caps), each a short prompt using the cheaper model tier already configured for the chosen provider. Tips can be pre-phrased per rank band and cached. Announcements are generated once per announcement, not per learner.

### 8.5 Module layout

All inside the existing `src/Guidance` module:

```
Domain/         GuidePreference(+Repository), Message/ (GuideKind, TriggerCatalog, NewGuideMessage,
                GuideMessage, GuideMessageRepository), Content/ (GuideTip, GuideResource,
                FeatureAnnouncement, GuideContentRepository, AnnouncementNotReady)
Application/    Service/  GuideMessageQueue, GuideDelivery, GuideMessageWriter, GuidePromptBuilder,
                          GuideMessageValidator, LearnerSnapshotBuilder, ContentPicker, GuideBackoff,
                          GuideSettings, EvaluationGuideTriggers, VisitGuideTriggers, StuckGuideTrigger
                Listener/ QueueGuideMessagesOnEvaluation
                Command/  DismissGuideMessage, RateGuideMessage, OptOutOfResource, SetGuideKindsMuted,
                          ReportStuckOnTask, SaveGuideContent, DeleteGuideContent, SetAnnouncementStatus,
                          GenerateAnnouncementSummary, CheckGuideResourceLinks, UpdateGuideSettings
                Query/    GetNextGuideMessage, ListWhatsNew, GetGuideSettings, GetGuideHealth,
                          ListGuideContent, GetGuideContentItem
Infrastructure/ Eloquent models + repositories
Presentation/   GuideMessageController (next, dismiss, rate, opt-out, stuck, kinds, What's new),
                Admin/GuideAdminController, GuideComponent
```

Guidance reads other modules only through the QueryBus (one new query was added for it: SimExecution's `GetUserIdForLearner`), and listens to `EvaluationComplete` rather than being called by `PostEvaluationRouter`.

---

## 9. Admin: Guide Section

- **Triggers**: enable/disable each trigger and edit its cooldown; the daily cap. (Fallback text is written in code next to each trigger, because it is built from the trigger's facts.)
- **Tips** and **Resources**: CRUD; resource link-check status.
- **Announcements**: draft → generate summary → edit → publish / archive.
- **Message log**: recent messages (learner, trigger, text, rating), filterable by trigger.
- **Health**: per trigger over the last 30 days: shown, helpful (with %), not helpful, closed unread. *Later:* the next-attempt pass rate of nudged learners compared with learners who weren't nudged (the real measure of whether Tiroco helps).

---

## 10. Feature Flags and Rollout

| Flag | Gates |
|---|---|
| `guide.ai_nudges` | Nudges, tips and resources (walkthroughs keep working without it) |
| `guide.announcements` | Announcement cards and the What's new list |

Rollout: ship with `guide.ai_nudges` on for a small share of learners first (flag percentage, or admins and testers only), watch helpful % and dismiss % for two weeks, then open to all.

---

## 11. Build Status

Built on branch `claude/cloud-credits-detection-2mhvbg`: everything in §1–§10 except the deferred triggers listed in §3 and the pass-rate comparison in §9. Feature tests in `tests/Feature/AiGuideTest.php` cover each built trigger, cooldowns, the daily cap, priority, quiet zones, learner switches, back-off, AI fallback and validation, resources and opt-out, announcements end to end, flag-gated announcements and admin access.

Next, with v2-1: the `ci-failing` and `regression` triggers, git and procedure tips, and `stacks` on resources.

---

## 12. Exit Criteria

- A tester working through a classic project and the Taskly pilot receives relevant nudges at the right moments (repeat fail, idle, first consequence, returning) and never more than the caps allow.
- No message contains a task solution (checked by the validator plus a manual review of 50 generated messages).
- An admin can publish an announcement end to end in under 5 minutes, and it shows once to each learner.
- Helpful rate ≥ 60% and "turned Tiroco off" under 15% of active learners during the rollout period.
