<?php
namespace Src\SimExecution\Application\Query\GetSprintPlanning;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItem;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\SimExecution\Domain\Session\SessionStatus;
use Src\SimExecution\Domain\Sprint\LearnerSprintRepository;
use Src\SimExecution\Domain\Sprint\SprintGoalPolicy;
use Src\SimExecution\Domain\Sprint\SprintStatus;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Simulation\Application\Query\ListBacklogItemTemplatesByProject\ListBacklogItemTemplatesByProjectQuery;
use Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary;

/**
 * Only surfaces items with status Backlog (available) or InSprint on the
 * current sprint (already planned). An item left in_progress/blocked when a
 * previous sprint was submitted stays attached to that old sprint rather
 * than reappearing here — carrying unfinished work forward is a backlog-
 * grooming concern out of scope for Phase 6.
 */
final class GetSprintPlanningHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly LearnerSprintRepository $sprints,
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(GetSprintPlanningQuery $query): ?SprintPlanningView
    {
        $learner = $query->userId !== null ? $this->learners->findByUserId($query->userId) : null;
        if ($learner === null) {
            return null;
        }

        $session = $this->sessions->findByLearnerAndProject($learner->id(), $query->projectId);
        if ($session === null || $session->status() !== SessionStatus::Active) {
            return null;
        }

        $sprint = $this->sprints->findLatestForSession($session->id());
        if ($sprint === null || $sprint->status() !== SprintStatus::Planning) {
            return null;
        }

        /** @var BacklogItemTemplateSummary[] $templates */
        $templates = $this->queryBus->ask(new ListBacklogItemTemplatesByProjectQuery($query->projectId));
        $templatesById = [];
        foreach ($templates as $template) {
            $templatesById[$template->id] = $template;
        }

        $items = $this->backlogItems->findAllForSession($session->id());
        $backlogViews = [];
        $sprintViews = [];

        foreach ($items as $item) {
            $template = $templatesById[$item->templateItemId()] ?? null;
            $view = new BacklogItemPlanningView(
                id:          $item->id(),
                title:       $template?->title ?? '(item content unavailable)',
                description: $template?->description,
                priority:    $item->priority()->value,
                isInjected:  $item->isInjected(),
            );

            if ($item->status() === BacklogItemStatus::Backlog) {
                $backlogViews[] = $view;
            } elseif ($item->status() === BacklogItemStatus::InSprint && $item->sprintId() === $sprint->id()) {
                $sprintViews[] = $view;
            }
        }

        $rank = $this->queryBus->ask(new GetLearnerRankQuery($learner->id()));
        $showsQualityHint = SprintGoalPolicy::showsQualityHint($rank?->rankTier ?? 'Junior', $rank?->rankLevel ?? 1);

        return new SprintPlanningView(
            sprintId:         $sprint->id(),
            sprintNumber:     $sprint->sprintNumber(),
            sprintGoal:       $sprint->sprintGoal(),
            sprintGoalSource: $sprint->sprintGoalSource()->value,
            showsQualityHint: $showsQualityHint,
            backlogItems:     $backlogViews,
            sprintItems:      $sprintViews,
        );
    }
}
