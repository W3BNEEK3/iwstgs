<?php
namespace Src\SourceControl\Application\Command\DisconnectGitHub;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\ListSessionsForLearner\ListSessionsForLearnerQuery;
use Src\SourceControl\Domain\LinkedRepository;
use Src\SourceControl\Domain\SourceControlRepository;

/** Forget the GitHub account; its repositories stop being usable until the learner reconnects. */
final class DisconnectGitHubHandler
{
    public function __construct(
        private readonly SourceControlRepository $store,
        private readonly QueryBus $queryBus,
    ) {}

    public function handle(DisconnectGitHubCommand $command): void
    {
        $connection = $this->store->connectionForUser($command->userId);
        if ($connection === null) {
            return;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($command->userId));
        foreach ($learnerId === null ? [] : $this->queryBus->ask(new ListSessionsForLearnerQuery($learnerId)) as $session) {
            $repo = $this->store->repositoryForSession($session->id);
            if ($repo !== null) {
                $this->store->setStatus($repo->id, LinkedRepository::ACCESS_LOST);
            }
        }

        $this->store->deleteConnection($command->userId);
    }
}
