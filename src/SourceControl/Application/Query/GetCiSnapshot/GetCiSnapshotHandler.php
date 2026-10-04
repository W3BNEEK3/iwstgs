<?php
namespace Src\SourceControl\Application\Query\GetCiSnapshot;

use Src\SourceControl\Application\Service\CiReader;
use Src\SourceControl\Domain\CiSnapshot;
use Src\SourceControl\Domain\SourceControlRepository;

final class GetCiSnapshotHandler
{
    public function __construct(
        private readonly SourceControlRepository $store,
        private readonly CiReader $ci,
    ) {}

    public function handle(GetCiSnapshotQuery $query): CiSnapshot
    {
        $repo = $this->store->repositoryForSession($query->learnerSessionId);
        if ($repo === null || ! $repo->isUsable()) {
            return new CiSnapshot(CiSnapshot::NO_RUN);
        }

        return $this->ci->read($repo, $query->sha, $query->expectedWorkflowSha256);
    }
}
