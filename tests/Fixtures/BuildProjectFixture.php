<?php

namespace Tests\Fixtures;

use Database\Seeders\Concerns\AuthorsSimulationContent;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

/**
 * A small Build-track project for tests: two chapters, three milestones, one
 * stack (plus a rank-gated harder one), acceptance tests per milestone, story
 * events and a regression consequence.
 */
final class BuildProjectFixture
{
    use AuthorsSimulationContent;

    public const TEMPLATE = 'areyna-templates/tasklet-express-ejs';
    public const WORKFLOW = "name: Areyna acceptance tests\non: push\n";

    public ProjectTemplateModel $project;
    public string $variantId;
    public string $hardVariantId;
    public TaskModel $m1;
    public TaskModel $m2;
    public TaskModel $m3;
    public TaskModel $regressionFix;
    public string $chapterOneId;
    public string $chapterTwoId;

    public static function create(): self
    {
        $fixture = new self();
        $fixture->build();

        return $fixture;
    }

    private function build(): void
    {
        [$this->project, $rubricSet] = $this->project('Tasklet — A To-do List', [
            'project_type'        => 'web_application',
            'track'               => 'build',
            'tagline'             => 'Build a to-do list from an empty repository.',
            'business_domain'     => 'productivity',
            'business_context'    => 'Nora wants a simple to-do list she can use on her phone.',
            'specialization_tags' => ['build'],
            'difficulty_level'    => 'beginner',
        ]);

        $this->variantId = $this->stackVariant($this->project, 'express-ejs', [
            'name' => 'Express + EJS', 'languages' => ['JavaScript'], 'difficulty' => 1,
            'template_repo' => self::TEMPLATE, 'workflow_sha256' => hash('sha256', self::WORKFLOW),
            'setup_notes' => 'Requires Node 20+.',
        ]);
        $this->hardVariantId = $this->stackVariant($this->project, 'express-react', [
            'name' => 'Express API + React', 'languages' => ['JavaScript'], 'difficulty' => 2,
            'template_repo' => 'areyna-templates/tasklet-express-react', 'min_rank_tier' => 'Mid', 'min_rank_level' => 1,
        ]);

        $one = $this->scenario($this->project, 1, [
            'title' => 'Get it on the screen', 'narrative' => 'Nora asked you to build her a to-do list.',
            'trigger' => 'A message from Nora.', 'trigger_type' => 'slack_message', 'role_label' => 'Developer', 'autonomy' => 'low',
        ]);
        $two = $this->scenario($this->project, 2, [
            'title' => 'Make it last', 'narrative' => 'Nora lost her tasks when she closed the tab.',
            'trigger' => 'Another message from Nora.', 'trigger_type' => 'slack_message', 'role_label' => 'Developer',
        ]);
        $this->chapterOneId = $one->id;
        $this->chapterTwoId = $two->id;

        $this->m1 = $this->milestone($one, $rubricSet, 1, $this->spec('Show a list of tasks'));
        $this->m2 = $this->milestone($one, $rubricSet, 2, $this->spec('Add a task'));
        $this->m3 = $this->milestone($two, $rubricSet, 1, $this->spec('Keep tasks after a restart'));
        $this->regressionFix = $this->adaptiveTask('consequence', $one, $rubricSet, 9, [
            'title' => 'Something that used to work broke', 'brief' => 'Nora says adding tasks stopped working. Find what changed and fix it.',
            'domain' => 'debugging', 'roles' => ['build'], 'answer' => 'Restores the broken behaviour.', 'deliver' => 'written',
            'hint' => 'Compare the last good commit with now.',
            'criteria' => [['dim_debugging', 'Finding the cause', 'Identifies what changed.', 'Look for a root cause.', ['a', 'b', 'c', 'd']]],
        ]);
        $this->project->update(['regression_consequence_task_id' => $this->regressionFix->id]);

        $this->variantSpec($this->m1, $this->variantId, ['T1.1', 'T1.2'], 'Render the list from an array in views/index.ejs.', ['low' => 'Start with res.render().']);
        $this->variantSpec($this->m2, $this->variantId, ['T2.1']);
        $this->variantSpec($this->m3, $this->variantId, ['T3.1']);

        $this->scenarioEvent($one, 'scenario_start', 'stakeholder_message', 'Nora Adeyemi', 'Anything that shows my list on my phone would be amazing!', null, 'Client', 1);
        $this->scenarioEvent($one, 'after_task', 'requirement_change', 'Nora Adeyemi', 'Could I add tasks too, not just see them?', $this->m1, 'Client', 2);
        $this->scenarioEvent($two, 'scenario_start', 'stakeholder_message', 'Nora Adeyemi', 'I closed the tab and everything was gone!', null, 'Client', 1);
    }

    private function spec(string $title): array
    {
        return [
            'title' => $title, 'brief' => "{$title}.", 'domain' => 'web', 'roles' => ['build'],
            'answer' => 'A working feature with a clear explanation.', 'starter' => 'Start small.', 'stretch' => 'Add a test.',
            'hint_low' => 'Read the starter README first.', 'hint_mid' => 'Keep it simple.',
            'criteria' => [
                ['dim_implementation', 'Working feature', 'The feature works as described.', 'Check the diff.', ['a', 'b', 'c', 'd']],
                ['dim_communication', 'Explanation', 'Explains what changed and why.', 'Read the explanation.', ['a', 'b', 'c', 'd']],
            ],
        ];
    }

    /** An areyna-report.json with the given test IDs passing and the rest failing. */
    public static function report(array $passing, array $failing = [], bool $startedOk = true): array
    {
        $tests = [];
        foreach ($passing as $id) {
            $tests[] = ['id' => $id, 'milestone' => (int) $id[1], 'title' => "Test {$id}", 'status' => 'passed'];
        }
        foreach ($failing as $id) {
            $tests[] = ['id' => $id, 'milestone' => (int) $id[1], 'title' => "Test {$id}", 'status' => 'failed', 'message' => 'expected 4 items, found 3'];
        }

        return ['suite' => 'tasklet-acceptance', 'version' => '1.0.0', 'startedOk' => $startedOk, 'tests' => $tests];
    }
}
