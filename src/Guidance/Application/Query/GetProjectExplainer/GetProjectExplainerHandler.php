<?php
namespace Src\Guidance\Application\Query\GetProjectExplainer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\Competency\Application\Query\ListRoleDefinitions\ListRoleDefinitionsQuery;
use Src\Competency\Domain\Role\RoleDefinitionSummary;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\ListCriteriaByTask\ListCriteriaByTaskQuery;
use Src\Simulation\Application\Query\ListScenariosByProject\ListScenariosByProjectQuery;
use Src\Simulation\Application\Query\ListTasksByScenario\ListTasksByScenarioQuery;
use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Rubric\RubricCriterion;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\Task;

/**
 * The "about to apply" explanation on a project page. Facts (sprints, skills,
 * time, roles) come straight from the authored content; the opening summary
 * is written by the AI provider once per version of the project and cached,
 * with a plain summary built from the same facts whenever the AI is
 * unavailable, so the page never depends on it.
 */
final class GetProjectExplainerHandler
{
    private const FAILURE_BACKOFF_SECONDS = 600;

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly AiTextGeneratorClient $generator,
    ) {}

    public function handle(GetProjectExplainerQuery $query): ?ProjectExplainerView
    {
        /** @var ProjectTemplate|null $project */
        $project = $this->queryBus->ask(new GetProjectQuery($query->projectId));
        if ($project === null) {
            return null;
        }

        /** @var ScenarioTemplate[] $scenarios */
        $scenarios = array_values(array_filter(
            $this->queryBus->ask(new ListScenariosByProjectQuery($project->id())),
            fn (ScenarioTemplate $s) => $s->isPublished() && $s->isActive() && ! $s->isDiagnostic(),
        ));
        usort($scenarios, fn (ScenarioTemplate $a, ScenarioTemplate $b) => $a->sequenceOrder() <=> $b->sequenceOrder());

        $coreTasks = 0;
        $minutes = 0;
        $dimensionCounts = [];
        foreach ($scenarios as $scenario) {
            /** @var Task $task */
            foreach ($this->queryBus->ask(new ListTasksByScenarioQuery($scenario->id())) as $task) {
                $p = $task->toPrimitives();
                if ($p['task_type'] !== 'core' || ! $p['is_published']) {
                    continue;
                }
                $coreTasks++;
                $minutes += $p['time_limit_minutes'] ?? 60;
                /** @var RubricCriterion $criterion */
                foreach ($this->queryBus->ask(new ListCriteriaByTaskQuery($task->id())) as $criterion) {
                    $dim = $criterion->toPrimitives()['parent_dimension_id'];
                    $dimensionCounts[$dim] = ($dimensionCounts[$dim] ?? 0) + 1;
                }
            }
        }

        $labels = [];
        foreach ($this->queryBus->ask(new ListCompetenceDimensionsQuery()) as $dimension) {
            $labels[$dimension->id] = $dimension->shortLabel;
        }
        arsort($dimensionCounts);
        $skills = array_values(array_filter(array_map(fn ($id) => $labels[$id] ?? null, array_keys($dimensionCounts))));

        $scenarioViews = array_map(fn (ScenarioTemplate $s) => [
            'title' => $s->title(),
            'blurb' => $this->firstSentence($s->narrativeContext()),
        ], $scenarios);

        [$summary, $isAi] = $this->summary($project, $scenarioViews, $skills);

        return new ProjectExplainerView(
            summary:        $summary,
            isAiWritten:    $isAi,
            scenarios:      $scenarioViews,
            skills:         $skills,
            coreTaskCount:  $coreTasks,
            estimatedHours: (int) max(1, ceil($minutes / 60)),
            stack:          $project->techContext()['existing_stack'] ?? [],
            roles:          $this->roles($project, $labels),
        );
    }

    /** @return array{0: string, 1: bool} */
    private function summary(ProjectTemplate $project, array $scenarios, array $skills): array
    {
        $key = 'guidance.project_explainer.' . $project->id() . '.' . md5(json_encode([
            $project->title(), $project->businessContext(), $project->difficultyLevel(), $scenarios, $skills,
        ]));

        if (($cached = Cache::get($key)) !== null) {
            return [$cached, true];
        }

        if (! Cache::has("{$key}.failed")) {
            try {
                $text = trim($this->generator->generate($this->systemPrompt(), [
                    ['type' => 'text', 'text' => $this->facts($project, $scenarios, $skills)],
                ]));
                if ($text !== '') {
                    Cache::forever($key, $text);
                    return [$text, true];
                }
            } catch (\Throwable $e) {
                Log::warning("Project explainer generation failed for {$project->id()}: {$e->getMessage()}");
                Cache::put("{$key}.failed", true, self::FAILURE_BACKOFF_SECONDS);
            }
        }

        return [$this->fallbackSummary($project, $scenarios, $skills), false];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
            You are Tiroco, the friendly guide inside a software-engineering training
            platform. A learner is looking at a project and deciding whether to apply.
            Explain, in second person, what the project is about, what they will be
            doing across its sprints, and what they will get better at.

            Rules:
            - Use only the facts provided. Do not invent names, numbers or features.
            - Plain, encouraging language that a developer in their first year understands.
            - At most 3 short paragraphs, under 120 words in total.
            - Plain text only: no markdown, no lists, no headings.
            PROMPT;
    }

    private function facts(ProjectTemplate $project, array $scenarios, array $skills): string
    {
        $lines = [
            "Project: {$project->title()}",
            'Difficulty: ' . $project->difficultyLevel(),
            'Tagline: ' . ($project->tagline() ?? ''),
            "Context: {$project->businessContext()}",
            'Sprints:',
        ];
        foreach ($scenarios as $i => $s) {
            $lines[] = '  ' . ($i + 1) . ". {$s['title']}: {$s['blurb']}";
        }
        $lines[] = 'Skills assessed most: ' . implode(', ', array_slice($skills, 0, 4));

        return implode("\n", $lines);
    }

    private function fallbackSummary(ProjectTemplate $project, array $scenarios, array $skills): string
    {
        $name = trim(explode('—', $project->title())[0]);
        $text = "{$name} is a {$project->difficultyLevel()} project. " . ($project->tagline() ?? '');

        if ($scenarios !== []) {
            $text .= ' Across ' . count($scenarios) . ' sprints you will work through: '
                . implode('; ', array_map(fn ($s) => preg_replace('/^Sprint \d+\s*[—-]\s*/u', '', $s['title']), $scenarios)) . '.';
        }
        if ($skills !== []) {
            $text .= ' It mostly trains ' . $this->humanList(array_slice($skills, 0, 3)) . '.';
        }

        return trim($text);
    }

    /** @return array<int, array{title: string, focus: string[]}> */
    private function roles(ProjectTemplate $project, array $labels): array
    {
        $roles = [];
        /** @var RoleDefinitionSummary $role */
        foreach ($this->queryBus->ask(new ListRoleDefinitionsQuery()) as $role) {
            if (array_intersect($project->specializationTags(), $role->specializationTags) === []) {
                continue;
            }
            $weights = $role->dimensionWeights;
            arsort($weights);
            $roles[] = [
                'title' => $role->title,
                'focus' => array_values(array_filter(array_map(fn ($id) => $labels[$id] ?? null, array_slice(array_keys($weights), 0, 2)))),
            ];
        }

        return $roles;
    }

    private function firstSentence(string $text): string
    {
        return preg_match('/^.*?[.!?](\s|$)/su', $text, $m) ? trim($m[0]) : $text;
    }

    private function humanList(array $items): string
    {
        $last = array_pop($items);

        return $items === [] ? (string) $last : implode(', ', $items) . ' and ' . $last;
    }
}
