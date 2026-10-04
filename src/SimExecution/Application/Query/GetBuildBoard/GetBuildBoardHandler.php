<?php
namespace Src\SimExecution\Application\Query\GetBuildBoard;

use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\FireScenarioEvents\FireScenarioEventsCommand;
use Src\SimExecution\Application\Query\GetBuildProgress\GetBuildProgressQuery;
use Src\SimExecution\Application\Query\GetStoryFeed\GetStoryFeedQuery;
use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Domain\Session\LearnerSessionRepository;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\GetScenario\GetScenarioQuery;
use Src\Simulation\Application\Query\GetStackVariant\GetStackVariantQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\SourceControl\Application\Query\GetGitHubConnection\GetGitHubConnectionQuery;
use Src\SourceControl\Application\Query\GetLinkedRepository\GetLinkedRepositoryQuery;
use Src\Submission\Application\Query\GetLatestSubmissionId\GetLatestSubmissionIdQuery;

/**
 * The Build-track board (design doc v2-01 §6): no sprint planning, a path of
 * milestones (done / current / locked) grouped by chapter, any fix-it cards
 * the platform injected, the chapter's story messages, and the repository
 * panel. Opening it also fires the current chapter's opening messages, which
 * is idempotent, so a chapter that began before scripted events were switched
 * on still gets them.
 */
final class GetBuildBoardHandler
{
    public function __construct(
        private readonly LearnerRepository $learners,
        private readonly LearnerSessionRepository $sessions,
        private readonly InjectedTaskCardRepository $cards,
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    public function handle(GetBuildBoardQuery $query): ?BuildBoardView
    {
        $learner = $query->userId !== null ? $this->learners->findByUserId($query->userId) : null;
        $session = $learner !== null ? $this->sessions->findByLearnerAndProject($learner->id(), $query->projectId) : null;
        if ($session === null || $session->stackVariantId() === null) {
            return null;
        }

        $project = $this->queryBus->ask(new GetProjectQuery($query->projectId));
        $variant = $this->queryBus->ask(new GetStackVariantQuery($session->stackVariantId()));
        if ($project === null || $variant === null) {
            return null;
        }

        $this->commandBus->dispatch(new FireScenarioEventsCommand($session->id(), 'scenario_start'));

        $progress = $this->queryBus->ask(new GetBuildProgressQuery($session->id()));

        $chapters = [];
        foreach ($progress->milestones as $milestone) {
            $chapters[$milestone['scenario_id']] ??= [
                'id'         => $milestone['scenario_id'],
                'title'      => $milestone['scenario_title'],
                'is_current' => $milestone['scenario_id'] === $session->currentScenarioId(),
                'milestones' => [],
            ];
            $chapters[$milestone['scenario_id']]['milestones'][] = $milestone;
        }

        $scenario = $session->currentScenarioId() !== null ? $this->queryBus->ask(new GetScenarioQuery($session->currentScenarioId())) : null;

        $cards = [];
        foreach ($this->cards->findAllForSession($session->id()) as $card) {
            if ($card->injectedTaskId === null) {
                continue;
            }
            $task = $this->queryBus->ask(new GetTaskQuery($card->injectedTaskId));
            $cards[$card->injectedTaskId] ??= [
                'task_id'   => $card->injectedTaskId,
                'title'     => $task?->toPrimitives()['title'] ?? 'A task from your team',
                'type'      => $card->cardType,
                'attempted' => $this->queryBus->ask(new GetLatestSubmissionIdQuery($session->id(), $card->injectedTaskId)) !== null,
                'ticket'    => $card->generatedTaskContent['incident_ticket_text'] ?? null,
            ];
        }

        return new BuildBoardView(
            projectId:         $project->id(),
            projectTitle:      $project->title(),
            sessionId:         $session->id(),
            sessionStatus:     $session->status()->value,
            variant:           $variant,
            progress:          $progress,
            chapters:          array_values($chapters),
            chapterTitle:      $scenario?->title(),
            chapterStory:      $scenario?->narrativeContext(),
            cards:             array_values($cards),
            feed:              $this->queryBus->ask(new GetStoryFeedQuery($session->id())),
            github:            $this->queryBus->ask(new GetGitHubConnectionQuery((string) $query->userId)),
            repository:        $this->queryBus->ask(new GetLinkedRepositoryQuery($session->id())),
            suggestedRepoName: $variant->suggestedRepoName(),
        );
    }
}
