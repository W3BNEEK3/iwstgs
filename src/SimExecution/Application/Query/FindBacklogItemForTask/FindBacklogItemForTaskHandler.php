<?php
namespace Src\SimExecution\Application\Query\FindBacklogItemForTask;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Domain\Backlog\BacklogItemStatus;
use Src\SimExecution\Domain\Backlog\LearnerBacklogItemRepository;
use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;
use Src\Simulation\Application\Query\ListBacklogItemTemplatesByProject\ListBacklogItemTemplatesByProjectQuery;
use Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary;

/**
 * LearnerBacklogItem only stores template_item_id, not task_id directly —
 * this cross-references via the project's backlog_item_templates (which do
 * carry task_id) to find which of the learner's own backlog items is tied
 * to the given task, matching GetSprintBoardHandler's own template lookup.
 *
 * An injected (Phase 9 adaptive) item has no template_item_id at all — its
 * task link lives on its injected_task_cards row instead, so those are
 * checked via injected_task_id.
 */
final class FindBacklogItemForTaskHandler
{
    public function __construct(
        private readonly LearnerBacklogItemRepository $backlogItems,
        private readonly InjectedTaskCardRepository $injectedCards,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(FindBacklogItemForTaskQuery $query): ?string
    {
        /** @var BacklogItemTemplateSummary[] $templates */
        $templates = $this->queryBus->ask(new ListBacklogItemTemplatesByProjectQuery($query->projectId));
        $templateIdsForTask = [];
        foreach ($templates as $template) {
            if ($template->taskId === $query->taskId) {
                $templateIdsForTask[] = $template->id;
            }
        }

        foreach ($this->backlogItems->findAllForSession($query->learnerSessionId) as $item) {
            if ($item->status() === BacklogItemStatus::Done) {
                continue;
            }

            if ($item->templateItemId() !== null && in_array($item->templateItemId(), $templateIdsForTask, true)) {
                return $item->id();
            }

            if ($item->isInjected() && $item->injectedCardId() !== null) {
                $card = $this->injectedCards->findById($item->injectedCardId());
                if ($card !== null && $card->injectedTaskId === $query->taskId) {
                    return $item->id();
                }
            }
        }

        return null;
    }
}
