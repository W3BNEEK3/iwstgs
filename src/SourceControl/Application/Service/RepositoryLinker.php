<?php
namespace Src\SourceControl\Application\Service;

use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\LearnerSessionView;
use Src\SimExecution\Application\Query\ListSessionsForLearner\ListSessionsForLearnerQuery;
use Src\Simulation\Application\Query\GetStackVariant\GetStackVariantQuery;
use Src\Simulation\Domain\Build\StackVariant;
use Src\SourceControl\Domain\HostRepository;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\SourceControlRepository;

/**
 * Matches the repositories a learner gave the Areyna App access to with the
 * projects waiting for one (design doc v2-01 §2.1): a repository is linked
 * to a session when it belongs to the learner's GitHub account and was
 * generated from that session's stack template. Runs on the installation
 * webhooks and when the learner presses "Check again", so linking works
 * even where webhooks can't reach Areyna (local development).
 */
final class RepositoryLinker
{
    public function __construct(
        private readonly RepositoryHost $host,
        private readonly SourceControlRepository $store,
        private readonly QueryBus $queryBus,
    ) {}

    public function linkForUser(string $userId): LinkOutcome
    {
        $connection = $this->store->connectionForUser($userId);
        if ($connection === null) {
            return new LinkOutcome(LinkOutcome::NOT_CONNECTED);
        }

        $installationId = $this->host->findInstallationForAccount($connection->githubUserId);
        if ($installationId === null) {
            return new LinkOutcome(LinkOutcome::NOT_INSTALLED);
        }

        $waiting = $this->sessionsWaitingForRepo($userId);
        if ($waiting === []) {
            return new LinkOutcome(LinkOutcome::NOTHING_WAITING);
        }

        $repos = array_filter(
            $this->host->installationRepositories($installationId),
            fn (HostRepository $r) => $r->ownerId === $connection->githubUserId,
        );

        $linked = 0;
        $notFromTemplate = [];
        foreach ($waiting as [$session, $variant]) {
            $match = null;
            foreach ($repos as $repo) {
                if ($repo->templateFullName !== null && strcasecmp($repo->templateFullName, $variant->templateRepo) === 0) {
                    $match = $repo;
                    break;
                }
            }

            if ($match === null) {
                // Same name as the template suggests, but created some other way (e.g. a plain new repo).
                foreach ($repos as $repo) {
                    if ($repo->templateFullName === null && strcasecmp(basename($repo->fullName), $variant->suggestedRepoName()) === 0) {
                        $notFromTemplate[] = $repo->fullName;
                    }
                }
                continue;
            }

            $this->store->link($session->id, $match, $installationId, $variant->templateRepo);
            $linked++;
        }

        if ($linked > 0) {
            return new LinkOutcome(LinkOutcome::LINKED, $linked);
        }

        return new LinkOutcome($notFromTemplate !== [] ? LinkOutcome::NOT_FROM_TEMPLATE : LinkOutcome::NO_MATCH, 0, $notFromTemplate);
    }

    /** @return array<int, array{0: LearnerSessionView, 1: StackVariant}> */
    private function sessionsWaitingForRepo(string $userId): array
    {
        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($userId));
        if ($learnerId === null) {
            return [];
        }

        $waiting = [];
        /** @var LearnerSessionView $session */
        foreach ($this->queryBus->ask(new ListSessionsForLearnerQuery($learnerId)) as $session) {
            if ($session->stackVariantId === null || $session->status === 'complete') {
                continue;
            }
            $existing = $this->store->repositoryForSession($session->id);
            if ($existing !== null && $existing->isUsable()) {
                continue;
            }
            $variant = $this->queryBus->ask(new GetStackVariantQuery($session->stackVariantId));
            if ($variant !== null) {
                $waiting[] = [$session, $variant];
            }
        }

        return $waiting;
    }
}
