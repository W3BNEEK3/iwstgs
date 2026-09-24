<?php
namespace Src\Guidance\Domain;

/**
 * Everything the in-app guide (voiced by Tiroco, the platform's AI mentor)
 * can say. Each step belongs to a page and is shown once, the first time
 * the learner reaches it. Steps with a `when` flag are milestone tips: they
 * only appear once the page reports that flag (e.g. the first consequence
 * card on the board). Content is authored rather than generated per view so
 * it is always accurate and instant; the AI-written part of the guide is the
 * per-project explainer.
 */
final class GuideCatalog
{
    /** Route name => guide page key. */
    public const ROUTE_PAGES = [
        'learn.enrol'              => 'enrol',
        'learn.diagnostic'         => 'diagnostic',
        'learn.catalogue'          => 'catalogue',
        'learn.catalogue.show'     => 'project',
        'learn.onboarding'         => 'onboarding',
        'learn.sprint-planning'    => 'sprint-planning',
        'learn.sprint-board'       => 'sprint-board',
        'learn.task-submit'        => 'task-submit',
        'learn.evaluation-result'  => 'evaluation-result',
        'learn.profile'            => 'profile',
    ];

    /**
     * @return array<int, array{key: string, page: string, when: ?string, title: string, cards: array<int, array{0: string, 1: string}>}>
     */
    public static function steps(): array
    {
        return [
            [
                'key' => 'welcome', 'page' => 'enrol', 'when' => null,
                'title' => 'Welcome to Areyna',
                'cards' => [
                    ['What this is', 'Areyna drops you into realistic software projects with a fictional team, real-looking tickets and people who need things from you. You learn by doing the work, not by watching videos.'],
                    ['How it works', 'First a short check-in sets your starting rank. Then you pick a project and a role, plan sprints, and submit work. Every submission is reviewed and shapes what comes next.'],
                    ['I\'m Tiroco', 'I\'m your guide. I\'ll pop up with short tips the first time you reach each part of the platform. Once you know your way around, you can turn me off, and I\'ll stay quiet.'],
                ],
            ],
            [
                'key' => 'diagnostic', 'page' => 'diagnostic', 'when' => 'diagnosticInProgress',
                'title' => 'Your check-in',
                'cards' => [
                    ['No pass or fail', 'These two tasks only decide where you start. Nobody fails the check-in, and your rank moves up as you do well later.'],
                    ['Explain your thinking', 'Write the way you would talk a teammate through it. Your reasoning matters more than perfect wording or perfect code.'],
                    ['Use the materials', 'The ticket in Reference Materials has everything you need. Hints on each task are there to help, and using them costs you nothing.'],
                ],
            ],
            [
                'key' => 'diagnostic-complete', 'page' => 'diagnostic', 'when' => 'diagnosticComplete',
                'title' => 'Your starting rank',
                'cards' => [
                    ['What your rank means', 'Ranks go Junior, Mid, Senior, each with levels 1 to 3. Your rank decides how much guidance you get and which roles you can apply for.'],
                    ['It will change', 'Three strong submissions in a row can move you up. Struggling brings more support, never a penalty. Next step: choose a project.'],
                ],
            ],
            [
                'key' => 'catalogue', 'page' => 'catalogue', 'when' => null,
                'title' => 'Choosing a project',
                'cards' => [
                    ['Start where you are', 'Each project shows a difficulty. If you have less than a year of experience, a beginner project is the best place to start.'],
                    ['Look inside first', 'Open a project to see what you\'ll build, the skills it trains and how long it takes. I explain every project before you apply.'],
                ],
            ],
            [
                'key' => 'project', 'page' => 'project', 'when' => null,
                'title' => 'Before you apply',
                'cards' => [
                    ['Read the explainer', 'The "About this project" section tells you what each sprint is about and which skills are assessed, so you know what you\'re signing up for.'],
                    ['Pick a role', 'Your role decides which tasks suit you and how your work is weighted. Roles you don\'t have the experience for yet are greyed out.'],
                    ['What happens next', 'After you enrol you\'ll meet the team in an induction, then plan your first sprint. You can leave and come back any time.'],
                ],
            ],
            [
                'key' => 'onboarding', 'page' => 'onboarding', 'when' => null,
                'title' => 'Your first day',
                'cards' => [
                    ['Meet the project', 'Read the business context and who the stakeholders are. The details here show up in your tasks, so skimming now saves time later.'],
                    ['The situation', 'Every sprint starts with a trigger: an email, a Slack message or an incident. It tells you what the team needs right now.'],
                ],
            ],
            [
                'key' => 'sprint-planning', 'page' => 'sprint-planning', 'when' => null,
                'title' => 'Planning a sprint',
                'cards' => [
                    ['The backlog', 'These are the items the team could work on. Priorities follow MoSCoW: must have, should have, could have, won\'t have.'],
                    ['Build your sprint', 'Move the items you commit to into the sprint. Must-haves first. Some items are there to test your judgement and don\'t need to be pulled in.'],
                    ['Set a goal', 'Write one sentence about what this sprint achieves and why. Then confirm the sprint to open your board.'],
                ],
            ],
            [
                'key' => 'next-scenario', 'page' => 'sprint-planning', 'when' => 'newScenario',
                'title' => 'A new chapter',
                'cards' => [
                    ['The story moved on', 'You finished a scenario, so the project has moved to its next situation, with new items in the backlog.'],
                    ['Tasks adapt to you', 'If you did well, tasks now come with less hand-holding and more complexity. If you struggled, you\'ll get more guidance. Watch the badges on each task.'],
                ],
            ],
            [
                'key' => 'sprint-board', 'page' => 'sprint-board', 'when' => null,
                'title' => 'Your sprint board',
                'cards' => [
                    ['Move your work', 'Use the buttons on each card to move it from To do, to In progress, to Done. It helps you, and the team, see where things are.'],
                    ['Open a task to submit', 'Each item links to its task. Read the brief and the reference materials, then submit your work there.'],
                    ['The Vault', 'Project documents such as the PRD and glossary live in the Artifact Vault. When a task says "check the requirements", look there.'],
                ],
            ],
            [
                'key' => 'consequence-card', 'page' => 'sprint-board', 'when' => 'hasConsequence',
                'title' => 'An amber card appeared',
                'cards' => [
                    ['This is a consequence', 'A recent submission missed something, and like in a real team, it came back as a problem to fix. Amber cards are that fallout.'],
                    ['Why it matters', 'You need to deal with it before this scenario can finish. It\'s a chance to close the gap, not a punishment.'],
                ],
            ],
            [
                'key' => 'suggestion-card', 'page' => 'sprint-board', 'when' => 'hasSuggestion',
                'title' => 'A teal card appeared',
                'cards' => [
                    ['A focused practice task', 'I noticed a pattern across several submissions, so I added a short task that targets that skill.'],
                    ['Worth doing', 'It won\'t block your progress, but it\'s the quickest way to stop the same issue coming up again.'],
                ],
            ],
            [
                'key' => 'task-submit', 'page' => 'task-submit', 'when' => null,
                'title' => 'Submitting your work',
                'cards' => [
                    ['Explain, then show', 'The written explanation is where you show your reasoning. Say what you did, the choices you made, and why. It counts as much as the code.'],
                    ['The three badges', 'Complexity is how hard the task is. Guidance is how much help you get. Realism is how much detail is given. They adjust to how you\'re doing.'],
                    ['You can resubmit', 'If you realise you missed something, submit again. Every attempt is reviewed, and improving is exactly what the platform rewards.'],
                ],
            ],
            [
                'key' => 'evaluation-result', 'page' => 'evaluation-result', 'when' => null,
                'title' => 'Under review',
                'cards' => [
                    ['A second look', 'The reviewer wasn\'t sure how to judge this submission, so a person will check it. If there\'s a follow-up question, answering it helps.'],
                ],
            ],
            [
                'key' => 'profile', 'page' => 'profile', 'when' => null,
                'title' => 'Your profile',
                'cards' => [
                    ['Six skills', 'The chart shows your level in the six skills every task measures, from problem analysis to communication.'],
                    ['Where to focus', 'The flattest side of the chart is your best next step. Suggestion cards will target it for you.'],
                ],
            ],
        ];
    }

    /** @return array{key: string, page: string, when: ?string, title: string, cards: array}|null */
    public static function find(string $key): ?array
    {
        foreach (self::steps() as $step) {
            if ($step['key'] === $key) {
                return $step;
            }
        }

        return null;
    }
}
