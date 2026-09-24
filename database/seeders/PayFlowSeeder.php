<?php
namespace Database\Seeders;

use Database\Seeders\Concerns\AuthorsSimulationContent;
use Illuminate\Database\Seeder;

/**
 * PayFlow — intermediate backend/API project about money: exact arithmetic,
 * state machines, idempotent webhooks, scheduled jobs and audit trails. The
 * step up from the beginner projects once CAC and rank have escalated.
 */
class PayFlowSeeder extends Seeder
{
    use AuthorsSimulationContent;

    public function run(): void
    {
        [$project, $rubric] = $this->project('PayFlow — Invoicing for Freelancers', [
            'tagline'          => 'Invoices, payments and reminders where every cent has to add up.',
            'project_type'     => 'saas',
            'business_domain'  => 'fintech',
            'business_context' => "PayFlow is a small startup selling invoicing to freelancers: designers, translators, tutors. A freelancer creates an invoice, sends it to a client, and the client pays by card through a payment provider. PayFlow has 1,800 paying users and a reputation to protect: a single wrong total gets posted on social media.\n\nYou are joining the backend team as they rebuild the invoicing core.",
            'stakeholders'     => [
                ['name' => 'Lena Fischer', 'role' => 'CTO', 'priority' => 'high', 'concern' => 'correctness of money and a clean audit trail'],
                ['name' => 'Marco Silva', 'role' => 'Head of Support', 'priority' => 'medium', 'concern' => 'fewer "my invoice is wrong" tickets'],
                ['name' => 'Aisha Bello', 'role' => 'Freelancer (customer advisory board)', 'priority' => 'medium', 'concern' => 'getting paid on time without chasing'],
            ],
            'overarching_constraints' => [
                ['type' => 'technical', 'description' => 'Money is stored as integer minor units (cents). Never use floats for money.'],
                ['type' => 'technical', 'description' => 'The payment provider retries webhooks for up to 3 days and may deliver the same event more than once.'],
                ['type' => 'regulatory', 'description' => 'Every change to an invoice must be traceable to a person or a system process.'],
            ],
            'tech_context'        => ['existing_stack' => ['PHP', 'Laravel', 'PostgreSQL', 'Redis', 'Queue workers'], 'team_size' => 5, 'deployment_environment' => 'cloud, 3 app servers', 'special_requirements' => 'Scheduled jobs run on every app server unless told otherwise.'],
            'specialization_tags' => ['backend', 'api'],
            'coding_guidelines'   => 'Money is an integer of cents plus a currency code. State changes go through one method per transition. Every webhook handler must be idempotent. Tests may not depend on the real clock.',
            'velocity_estimate'   => ['sprint_days' => 10],
            'difficulty_level'    => 'intermediate',
        ]);

        $this->vaultItem($project, 'srs', 'Invoicing SRS (excerpt)', "Invoice: number, client, currency, line items (description, quantity, unit price in cents, tax rate %), status.\n\nTotals: tax is calculated per line and rounded half-up to the cent; invoice total = sum of line totals.\n\nStatuses: draft → sent → paid; sent → overdue (automatically after due date); sent/overdue → void (manual). Paid and void are final.\n\nPartial payments are allowed; an invoice is paid when payments ≥ total.", 1);
        $this->vaultItem($project, 'sad', 'Architecture notes', "Payment provider sends webhooks: payment.succeeded, payment.refunded. Each event has a unique event_id and an HMAC signature header.\n\nScheduled jobs are defined in the console kernel and run on all 3 app servers by default. Redis is available for locks.", 2);
        $this->vaultItem($project, 'sprint_goal_template', 'Sprint goal template', 'This sprint we will make ___ reliable, measured by ___.', 3);

        $back = ['backend', 'api'];

        // ── Scenario 1 ───────────────────────────────────────────────────────
        $s1 = $this->scenario($project, 1, [
            'title'        => 'Sprint 1 — Invoices That Add Up',
            'narrative'    => 'The old invoicing code used floats and a status string anyone could overwrite. Support gets a "my total is wrong" ticket every week. The rebuild starts with the invoice itself.',
            'trigger'      => "Slack from Lena Fischer (CTO): \"Screenshot doing the rounds on Twitter: an invoice for 3 × €19.99 at 19% VAT showing €71.36. Correct is €71.37. We are rebuilding totals and statuses this sprint. Money as integers, no exceptions.\"",
            'trigger_type' => 'slack_message',
            'role_label'   => 'Backend Engineer',
        ], [
            ['type' => 'ticket', 'title' => 'PF-11: Rounding example from support', 'content' => "Line: 3 × 1999 cents, VAT 19%\nNet: 5997\nTax: 5997 × 0.19 = 1139.43 → 1139\nLine total: 7136 (shown as €71.36)\n\nCustomer's accountant expected €71.37.\n\nNote: our SRS says tax is rounded half-up per line. Check the calculation above against the rule.", 'signals' => ['1139.43 rounds to 1139 — so is 71.36 actually right?', 'float vs integer arithmetic']],
        ]);

        $s1a = $this->coreTask($s1, $rubric, 1, [
            'title' => 'Build invoice totals with exact money',
            'brief' => "Implement creating an invoice with line items and calculating its totals according to the SRS. Money must be integer cents. Work through ticket PF-11 and state whether our total or the accountant's is correct, and why.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel'], 'concepts' => ['money representation', 'rounding'], 'time' => 90,
            'answer' => 'Stores prices as integer cents, computes tax per line with explicit half-up rounding on integers or a decimal library, sums line totals. Works PF-11: 1139.43 rounds to 1139 per line, so €71.36 is correct under our rule; the accountant rounded differently (e.g. on the invoice total or used a different rule). Explains how to communicate this.',
            'starter' => 'Represent every amount as an integer. Do the multiplication for tax in integers and decide exactly where rounding happens.',
            'stretch' => 'Some clients are in countries that require tax to be rounded on the invoice total, not per line. Design how the rounding rule could vary by client without duplicating the calculation.',
            'hint_low' => 'Calculate PF-11 by hand following the SRS rule before writing code.',
            'hint_mid' => 'Where exactly does rounding happen, and how many times?',
            'criteria' => [
                ['dim_implementation', 'Exact arithmetic', 'Totals are computed with integers and the SRS rounding rule.', 'Any float in the money path is at most developing. Rounding must be explicit.', ['Integer arithmetic, explicit half-up per line, correct totals, tested with PF-11.', 'Correct integer totals following the rule.', 'Mostly correct but with floats or implicit rounding.', 'Incorrect totals.']],
                ['dim_problem_analysis', 'Challenging the ticket', 'Works through PF-11 and correctly concludes which total follows our rule.', 'The ticket is a trap: under the SRS, €71.36 is correct.', ['Correct conclusion with the arithmetic shown and a sensible reply to support.', 'Correct conclusion.', 'Accepts the ticket without checking.', 'No analysis.']],
            ],
        ]);

        $s1b = $this->coreTask($s1, $rubric, 2, [
            'title' => 'Model the invoice status lifecycle',
            'brief' => "Implement invoice status changes according to the SRS. Only allowed transitions may happen; anything else is rejected with a clear error. Explain how your design makes an illegal transition hard to write by accident.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel'], 'concepts' => ['state machines', 'domain modelling'], 'architectural' => true,
            'answer' => 'An explicit transition map or one method per transition (send, markPaid, markOverdue, void) that checks the current status; final states reject changes; tests cover every allowed and a sample of forbidden transitions.',
            'starter' => 'Draw the statuses as boxes and the allowed transitions as arrows first.',
            'stretch' => 'Support wants to "un-void" invoices created by mistake. Argue whether that should be a transition or a new invoice, from an audit point of view.',
            'hint_low' => 'Can a paid invoice ever change status?',
            'hint_mid' => 'Avoid a generic setStatus() that accepts any value.',
            'criteria' => [
                ['dim_design', 'Explicit state model', 'Transitions are explicit and illegal ones are impossible or rejected.', 'A generic status setter with ad-hoc checks is developing.', ['Explicit transitions, final states enforced, design explained.', 'Explicit transitions enforced.', 'Checks scattered or incomplete.', 'Any status can be set.']],
                ['dim_testing', 'Transition tests', 'Tests cover allowed and forbidden transitions.', 'Look for tests of final states.', ['Every allowed transition and all final-state protections tested.', 'Main transitions and one forbidden case tested.', 'Only happy-path tests.', 'No tests.']],
            ],
        ]);

        $s1aFix = $this->adaptiveTask('consequence', $s1, $rubric, 3, [
            'title' => 'Follow-up: invoice totals are off by one cent',
            'brief' => "Support has three new tickets where the invoice total differs from the sum of the lines by one cent. All three have 10+ lines. Explain how a one-cent error can appear, find it in your implementation, and fix it with a test using one of the reported invoices.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Identifies rounding at the wrong stage or float accumulation across many lines; ensures per-line rounding to integer cents before summing; adds a regression test with a multi-line invoice.',
            'hint' => 'Check where rounding happens when there are many lines.',
            'criteria' => [
                ['dim_debugging', 'Rounding diagnosis', 'Explains and fixes the source of the one-cent difference.', 'Must name where the drift comes from.', ['Precise cause, fix, and regression test.', 'Cause and fix.', 'Fix by trial and error.', 'Not found.']],
            ],
        ]);

        $s1bFix = $this->adaptiveTask('consequence', $s1, $rubric, 4, [
            'title' => 'Follow-up: a paid invoice was voided',
            'brief' => "A freelancer voided invoice INV-1188 after the client had paid it. The client now has a receipt for a void invoice and the freelancer's books don't balance. Find how this was possible and close the gap.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Finds the void path skipping the status check (e.g. direct update or missing guard), enforces "paid is final" in the single transition method, and adds a test.',
            'hint' => 'Is there any code path that changes status without going through your transition method?',
            'criteria' => [
                ['dim_implementation', 'Closing the gap', 'Every status change goes through a guarded path.', 'Look for removal of bypasses, not just an extra if.', ['All bypasses removed and a test added.', 'Guard added with a test.', 'Guard added only for this case.', 'Not fixed.']],
            ],
        ]);

        $s1Focus = $this->adaptiveTask('suggestion', $s1, $rubric, 5, [
            'title' => 'Development focus: make illegal states unrepresentable',
            'brief' => "Explain, with an example from this project, the difference between checking a rule everywhere it matters and designing the code so the rule can't be broken. When is each approach appropriate?",
            'domain' => 'design', 'roles' => $back, 'deliver' => 'written',
            'answer' => 'Contrasts scattered checks with encapsulated transitions or value objects (e.g. Money), with a concrete example and trade-offs.',
            'hint' => 'Think about a Money value object versus passing integers around.',
            'criteria' => [
                ['dim_design', 'Design reasoning', 'Clear contrast with a concrete example and trade-offs.', 'Example must come from the project.', ['Clear contrast, concrete example, balanced trade-offs.', 'Clear contrast with an example.', 'Generic answer.', 'No understanding.']],
            ],
        ]);

        $this->linkAdaptive($s1a, [$s1aFix], [$s1Focus]);
        $this->linkAdaptive($s1b, [$s1bFix], [$s1Focus]);

        // ── Scenario 2 ───────────────────────────────────────────────────────
        $s2 = $this->scenario($project, 2, [
            'title'        => 'Sprint 2 — Getting Paid Reliably',
            'narrative'    => 'Invoices are solid. Now the money has to arrive in the right place. The provider sends webhooks, retries them when we are slow, and sometimes sends the same one twice.',
            'trigger'      => "Incident report #INC-042: \"Between 14:02 and 14:40 our webhook endpoint timed out under load. The provider retried. 37 invoices now show double payments; 5 clients received two receipts. No money was actually taken twice.\"",
            'trigger_type' => 'incident_report',
            'role_label'   => 'Backend Engineer',
        ], [
            ['type' => 'report', 'title' => 'Sample webhook payload', 'content' => "POST /webhooks/payments\nHeader X-Signature: sha256=…\n\n{\n  \"event_id\": \"evt_9f2c\",\n  \"type\": \"payment.succeeded\",\n  \"invoice_reference\": \"INV-1203\",\n  \"amount\": 4500,\n  \"currency\": \"EUR\",\n  \"paid_at\": \"2026-05-02T14:03:11Z\"\n}", 'signals' => ['event_id for idempotency', 'verify signature', 'partial payments']],
        ]);

        $s2a = $this->coreTask($s2, $rubric, 1, [
            'title' => 'Handle payment webhooks safely',
            'brief' => "Implement the payment.succeeded webhook handler. It must verify the signature, record the payment against the invoice, support partial payments, and be safe when the same event arrives more than once, including twice at the same time. Explain how you guarantee each event is applied exactly once.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel', 'postgresql'], 'concepts' => ['idempotency', 'webhooks', 'unique constraints'], 'time' => 90, 'architectural' => true,
            'answer' => 'Verifies the HMAC before parsing; stores payments with a unique constraint on event_id and inserts inside a transaction, treating a duplicate-key error as success; marks the invoice paid when the sum of payments ≥ total; returns 2xx quickly.',
            'starter' => 'Use the event_id. Where can the database itself guarantee something happens only once?',
            'stretch' => 'payment.refunded events can arrive before the payment.succeeded they refer to. Explain how your design copes.',
            'hint_low' => 'A unique index on event_id is a good start. What should the handler return for a duplicate?',
            'hint_mid' => 'Checking "does this event exist?" and then inserting is not safe on its own.',
            'criteria' => [
                ['dim_implementation', 'Correct handler', 'Signature verified, payment recorded, partial payments supported.', 'Signature check must happen before trusting the payload.', ['All behaviours correct and tested.', 'All behaviours correct.', 'Missing signature check or partial payments.', 'Handler does not work.']],
                ['dim_design', 'Exactly-once effect', 'Duplicates, including concurrent ones, cannot create two payments.', 'Check-then-insert without a constraint is developing; a unique constraint or equivalent is proficient.', ['Database-enforced idempotency with a clear explanation of the concurrent case.', 'Idempotent by event_id with a constraint.', 'Check-then-insert only.', 'Not idempotent.']],
            ],
        ]);

        $s2b = $this->coreTask($s2, $rubric, 2, [
            'title' => 'Reconcile payments with invoices',
            'brief' => "Some payments arrive with a reference that doesn't match an invoice exactly (typos, missing prefix, wrong case). Design and implement a reconciliation step: match what can be matched safely, flag over- and under-payments, and list anything unmatched for a human. Explain what you refuse to auto-match and why.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel'], 'concepts' => ['data matching', 'risk'],
            'answer' => 'Normalises references (case, whitespace, prefix) for safe matches, never guesses between multiple candidates, flags amount mismatches, and produces an unmatched list; explains the cost of a wrong match versus a manual one.',
            'starter' => 'List five realistic reference mistakes and decide for each: auto-match, or human review?',
            'stretch' => 'Propose how you would measure whether the matching rules are too cautious or too aggressive over time.',
            'hint_low' => 'What is worse: a payment matched to the wrong invoice, or one waiting for a person?',
            'hint_mid' => 'Think about when two invoices could match the same messy reference.',
            'criteria' => [
                ['dim_problem_analysis', 'Understanding the risk', 'Clearly separates safe automatic matches from cases needing a human.', 'Ambiguous matches must not be auto-applied.', ['Clear rules with the reasoning about the cost of errors.', 'Clear safe/unsafe split.', 'Aggressive matching without caution.', 'No analysis.']],
                ['dim_implementation', 'Working reconciliation', 'Matches, flags and lists as described.', 'Over- and under-payment flags are required.', ['All outcomes handled and tested.', 'All outcomes handled.', 'Some outcomes missing.', 'Not working.']],
            ],
        ]);

        $s2aFix = $this->adaptiveTask('consequence', $s2, $rubric, 3, [
            'title' => 'Follow-up: double payments during a slow deploy',
            'brief' => "During last night's deploy the webhook endpoint was slow again and INV-1290 shows two identical payments with different database IDs but the same event_id. Explain the exact sequence that allowed this and fix it so it cannot recur.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Explains the concurrent check-then-insert race, adds a unique constraint on event_id (after cleaning duplicates), and handles the constraint violation as an already-processed event.',
            'hint' => 'Two requests, same event, both checked "not processed yet" before either saved.',
            'criteria' => [
                ['dim_debugging', 'Race explanation', 'Explains the interleaving and fixes it at the database level.', 'A retry-free timeline explanation is key.', ['Precise timeline, database-level fix, and data clean-up plan.', 'Correct explanation and fix.', 'Fix without a clear explanation.', 'Not fixed.']],
            ],
        ]);

        $s2bFix = $this->adaptiveTask('consequence', $s2, $rubric, 4, [
            'title' => 'Follow-up: a payment was matched to the wrong client',
            'brief' => "Reconciliation matched a €300 payment referenced \"inv 120\" to INV-120 (client A). It was actually for INV-1200 (client B), who is now receiving reminders for an invoice they paid. Explain what went wrong, fix the matching rules, and describe how to correct the data safely.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Recognises prefix/partial matching produced an ambiguous match that should have gone to review; tightens rules to require an unambiguous match (and amount check); outlines a reversal with an audit entry.',
            'hint' => 'How many invoices could "inv 120" plausibly refer to?',
            'criteria' => [
                ['dim_problem_analysis', 'Ambiguity handling', 'Identifies the ambiguous match and makes such cases go to review.', 'Needs both the rule change and a safe data correction.', ['Rule fixed, ambiguity principle stated, safe correction with audit trail.', 'Rule fixed with a correction plan.', 'Only this case patched.', 'Not addressed.']],
            ],
        ]);

        $s2Focus = $this->adaptiveTask('suggestion', $s2, $rubric, 5, [
            'title' => 'Development focus: designing for retries',
            'brief' => "List three places in PayFlow (or any system you know) where the same request or message could be delivered twice. For each, explain what would go wrong and how you would make it safe.",
            'domain' => 'design', 'roles' => $back, 'deliver' => 'written',
            'answer' => 'Three realistic duplicate-delivery points with consequences and a concrete idempotency technique for each.',
            'hint' => 'Think about users double-clicking, queues redelivering, and networks timing out.',
            'criteria' => [
                ['dim_design', 'Idempotency thinking', 'Realistic cases with concrete, appropriate protections.', 'Techniques should vary by case.', ['Three strong cases with specific protections and trade-offs.', 'Three cases with protections.', 'Vague protections.', 'No real cases.']],
            ],
        ]);

        $this->linkAdaptive($s2a, [$s2aFix], [$s2Focus]);
        $this->linkAdaptive($s2b, [$s2bFix], [$s2Focus]);

        // ── Scenario 3 ───────────────────────────────────────────────────────
        $s3 = $this->scenario($project, 3, [
            'title'        => 'Sprint 3 — Reminders and an Audit Trail',
            'narrative'    => 'Freelancers want PayFlow to chase late payers for them. At the same time, an enterprise prospect has asked to see how PayFlow proves who changed what on an invoice.',
            'trigger'      => "Email from Lena Fischer: \"Two things before the enterprise demo on the 28th. (1) Automatic overdue reminders at 3, 7 and 14 days. (2) A proper audit trail: who changed what and when, including anything our own jobs do. Their compliance team will try to break it.\"",
            'trigger_type' => 'email',
            'role_label'   => 'Backend Engineer',
            'autonomy'     => 'high',
        ], [
            ['type' => 'notes', 'title' => 'Reminder rules from the customer advisory board', 'content' => "- Reminders at 3, 7 and 14 days after the due date.\n- Never send the same reminder twice.\n- Don't send reminders for paid, void or disputed invoices.\n- Clients can opt out; respect that.\n- Send during business hours in the client's time zone.", 'signals' => ['exactly once per stage', 'time zones', 'opt-out']],
        ]);

        $s3a = $this->coreTask($s3, $rubric, 1, [
            'title' => 'Schedule overdue reminders',
            'brief' => "Implement a scheduled job that sends overdue reminders following the advisory board's rules. It must be safe to run more than once and on several servers. Write tests that don't depend on today's date.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel', 'redis'], 'concepts' => ['scheduling', 'idempotency', 'testing time'], 'time' => 90,
            'answer' => 'Records which reminder stage was sent per invoice (unique per stage), selects eligible invoices by status/opt-out/due date, respects client time zone, runs on one server (onOneServer/withoutOverlapping) and is still idempotent; tests freeze the clock.',
            'starter' => 'Store which stage (3, 7, 14) has been sent for each invoice. The job then only sends stages that are due and not yet sent.',
            'stretch' => 'The job was down for two days. Decide what should happen to reminders that were missed, and justify it from the client\'s point of view.',
            'hint_low' => 'How will a test pretend it is 7 days after the due date?',
            'hint_mid' => 'Read the architecture notes about scheduled jobs.',
            'criteria' => [
                ['dim_implementation', 'Correct reminders', 'Reminders follow every rule and are never duplicated.', 'Running on 3 servers must not triple-send; opt-out and statuses must be respected.', ['All rules, safe on multiple servers, time zones handled.', 'All rules and no duplicates.', 'Duplicates possible or a rule ignored.', 'Job does not work.']],
                ['dim_testing', 'Time-independent tests', 'Tests control the clock and cover each stage and exclusion.', 'Tests that depend on the real date are developing.', ['Frozen clock, every stage and exclusion covered.', 'Frozen clock with main cases.', 'Tests depend on real time.', 'No tests.']],
            ],
        ]);

        $s3b = $this->coreTask($s3, $rubric, 2, [
            'title' => 'Build the invoice audit trail',
            'brief' => "Record every change to an invoice: who (a user or a named system process), what changed (before and after), and when. Entries must not be editable afterwards. Provide a way for the invoice owner to read the history, and explain how you would answer \"who voided INV-204 and why?\".",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel', 'postgresql'], 'concepts' => ['audit logging', 'immutability'], 'architectural' => true,
            'answer' => 'An append-only audit table written from the transition methods (not only controllers) so jobs are captured with a system actor; stores before/after for changed fields; no update/delete paths (and DB permissions or triggers for stronger guarantees); an owner-scoped read endpoint.',
            'starter' => 'Decide where the audit entry is written so that changes made by jobs are captured too.',
            'stretch' => 'The compliance team asks how they can be sure nobody edited the audit table directly in the database. Propose an approach.',
            'hint_low' => 'If the reminder job changes an invoice, who is the actor?',
            'hint_mid' => 'Think about which layer every change passes through.',
            'criteria' => [
                ['dim_design', 'Trustworthy trail', 'Captures all changes including system ones and cannot be altered through the app.', 'Logging only in controllers misses jobs and is developing.', ['Written at the domain layer, system actors named, append-only with a database-level guarantee proposed.', 'Captures all changes, append-only in the app.', 'Misses system changes.', 'No trail.']],
                ['dim_communication', 'Answering the auditor', 'Clearly explains how to answer the INV-204 question from the data.', 'Should reference actor, time, before/after and a reason field.', ['Precise answer including the reason and limits of the data.', 'Clear answer.', 'Partial answer.', 'No answer.']],
            ],
        ]);

        $s3aFix = $this->adaptiveTask('consequence', $s3, $rubric, 3, [
            'title' => 'Follow-up: clients got the same reminder three times',
            'brief' => "Aisha forwarded an angry email from her client: three identical \"7 days overdue\" reminders within one minute. Explain why this happened with PayFlow's setup and fix it so it can't happen even if the job runs twice.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Connects it to the job running on all 3 servers; fixes with onOneServer/lock AND a unique sent-stage record so a second run is a no-op.',
            'hint' => 'How many servers run the scheduler?',
            'criteria' => [
                ['dim_debugging', 'Distributed duplicate', 'Explains the multi-server cause and fixes with defence in depth.', 'A lock alone is proficient; lock plus idempotent record is distinguished.', ['Cause explained; lock plus idempotency.', 'Cause explained with one reliable fix.', 'Partial fix.', 'Not fixed.']],
            ],
        ]);

        $s3bFix = $this->adaptiveTask('consequence', $s3, $rubric, 4, [
            'title' => 'Follow-up: the auditor can\'t tell who voided INV-204',
            'brief' => "In the demo, the compliance team asked who voided INV-204. The trail shows the change but the actor is empty, because a support script voided it. Fix the trail so every change has an actor, and explain how you'll enforce that going forward.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Makes actor required (user id or named system process), passes it through scripts and jobs, and adds a test or constraint that rejects actor-less entries.',
            'hint' => 'Can the database refuse an audit row without an actor?',
            'criteria' => [
                ['dim_design', 'Enforced accountability', 'Every change requires an actor, enforced not just by convention.', 'A NOT NULL constraint or required parameter plus test.', ['Enforced at data and code level, scripts updated.', 'Enforced in code with a test.', 'Convention only.', 'Not fixed.']],
            ],
        ]);

        $s3Focus = $this->adaptiveTask('suggestion', $s3, $rubric, 5, [
            'title' => 'Development focus: testing code that depends on time',
            'brief' => "Explain why tests that use the real current date are unreliable, and show (in code or pseudocode) how you would test that a reminder is sent exactly 7 days after the due date.",
            'domain' => 'testing', 'roles' => $back, 'deliver' => 'written',
            'answer' => 'Describes flaky/time-bomb tests and shows freezing or injecting a clock, testing day 6, 7 and 8.',
            'hint' => 'Test the day before, the day of, and the day after.',
            'criteria' => [
                ['dim_testing', 'Controlling time', 'Explains the problem and shows a controlled-clock test around the boundary.', 'Must test around day 7.', ['Clear explanation and boundary tests with a controlled clock.', 'Controlled clock with a test.', 'Explanation only.', 'No understanding.']],
            ],
        ]);

        $this->linkAdaptive($s3a, [$s3aFix], [$s3Focus]);
        $this->linkAdaptive($s3b, [$s3bFix], [$s3Focus]);

        $order = 1;
        foreach ([[$s1a, 'must_have'], [$s1b, 'must_have'], [$s2a, 'must_have'], [$s2b, 'should_have'], [$s3a, 'must_have'], [$s3b, 'must_have']] as [$task, $priority]) {
            $this->backlogItem($project, $task->title, strtok($task->task_brief, "\n"), $priority, $back, $task, $order++);
        }
        $this->backlogItem($project, 'Multi-currency invoices', 'Sales asked for it; product has not committed.', 'could_have', $back, null, $order++);
        $this->backlogItem($project, 'Crypto payments', 'Requested by one user. Not aligned with the roadmap.', 'wont_have', $back, null, $order);
    }
}
