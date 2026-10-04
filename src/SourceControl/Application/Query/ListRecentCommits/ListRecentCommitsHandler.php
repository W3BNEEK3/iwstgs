<?php
namespace Src\SourceControl\Application\Query\ListRecentCommits;

use Src\SourceControl\Domain\HostCommit;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\SourceControlRepository;

/**
 * Recent commits on the learner's default branch, newest first, for the
 * milestone commit picker. Throws HostUnavailable when GitHub can't be reached.
 *
 * @see HostCommit
 */
final class ListRecentCommitsHandler
{
    public function __construct(
        private readonly SourceControlRepository $store,
        private readonly RepositoryHost $host,
    ) {}

    public function handle(ListRecentCommitsQuery $query): array
    {
        $repo = $this->store->repositoryForSession($query->learnerSessionId);
        if ($repo === null || ! $repo->isUsable()) {
            return [];
        }

        return $this->host->recentCommits($repo->installationId, $repo->fullName, $repo->defaultBranch, $query->limit);
    }
}
