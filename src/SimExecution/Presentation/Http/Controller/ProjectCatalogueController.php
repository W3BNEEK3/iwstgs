<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Guidance\Application\Query\GetProjectExplainer\GetProjectExplainerQuery;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetProjectDetail\GetProjectDetailQuery;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\ListPublishedProjects\ListPublishedProjectsQuery;
use Src\Simulation\Application\Query\ListStackVariants\ListStackVariantsQuery;
use Src\Simulation\Domain\Project\ProjectTemplate;

/**
 * The project catalogue, grouped by track (design doc v2-01 §6): Build
 * projects you make for real in your own repository, Work Experience
 * projects, and the classic challenges. A track whose feature flag is off is
 * hidden; with only classic projects the page looks as it always has.
 */
class ProjectCatalogueController
{
    private const TRACK_FLAGS = [
        ProjectTemplate::TRACK_BUILD           => 'tracks.build',
        ProjectTemplate::TRACK_WORK_EXPERIENCE => 'tracks.work_experience',
    ];

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly FeatureFlagService $flags,
    ) {}

    public function index(): View
    {
        $groups = [
            ProjectTemplate::TRACK_BUILD           => [],
            ProjectTemplate::TRACK_WORK_EXPERIENCE => [],
            ProjectTemplate::TRACK_CLASSIC         => [],
        ];
        $variants = [];

        /** @var ProjectTemplate $project */
        foreach ($this->queryBus->ask(new ListPublishedProjectsQuery()) as $project) {
            if (! $this->isVisible($project)) {
                continue;
            }
            $groups[$project->track()][] = $project;
            if ($project->track() !== ProjectTemplate::TRACK_CLASSIC) {
                $variants[$project->id()] = $this->queryBus->ask(new ListStackVariantsQuery($project->id()));
            }
        }

        return view('learn.catalogue', [
            'groups'   => array_filter($groups),
            'variants' => $variants,
            // `projects` keeps the flat list for the single-track (classic-only) layout.
            'projects' => array_merge(...array_values($groups)),
        ]);
    }

    public function show(string $project): View
    {
        $template = $this->queryBus->ask(new GetProjectQuery($project));
        abort_if($template === null || ! $this->isVisible($template), 404);

        $detail = $this->queryBus->ask(new GetProjectDetailQuery(
            projectId: $project,
            userId: Auth::id(),
        ));
        abort_if($detail === null, 404);

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery((string) Auth::id()));
        $rank = $learnerId !== null ? $this->queryBus->ask(new GetLearnerRankQuery($learnerId)) : null;

        return view('learn.catalogue-show', [
            'project'   => $detail,
            'isBuild'   => $template->isBuildTrack(),
            'variants'  => $template->isBuildTrack() ? $this->queryBus->ask(new ListStackVariantsQuery($project)) : [],
            'rank'      => $rank,
            'explainer' => $this->queryBus->ask(new GetProjectExplainerQuery($project)),
        ]);
    }

    private function isVisible(ProjectTemplate $project): bool
    {
        $flag = self::TRACK_FLAGS[$project->track()] ?? null;

        return $flag === null || $this->flags->isEnabled($flag);
    }
}
