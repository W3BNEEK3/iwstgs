<?php
namespace Src\SourceControl\Application\Query\GetCiSnapshot;

final class GetCiSnapshotQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $sha,
        public readonly ?string $expectedWorkflowSha256 = null,
    ) {}
}
