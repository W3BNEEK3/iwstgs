<?php
namespace Src\SourceControl\Application\Query\GetCommitDiff;

use Src\SourceControl\Application\Service\DiffBuilder;
use Src\SourceControl\Domain\DiffSnapshot;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\SourceControlRepository;

/** A milestone's changes as the AI reviewer sees them (secrets and generated files left out). */
final class GetCommitDiffHandler
{
    public function __construct(
        private readonly SourceControlRepository $store,
        private readonly RepositoryHost $host,
        private readonly DiffBuilder $diffs,
    ) {}

    public function handle(GetCommitDiffQuery $query): ?DiffSnapshot
    {
        $repo = $this->store->repositoryForSession($query->learnerSessionId);
        if ($repo === null) {
            return null;
        }

        return $this->diffs->build($this->host->compare($repo->installationId, $repo->fullName, $query->baseSha, $query->headSha));
    }
}
