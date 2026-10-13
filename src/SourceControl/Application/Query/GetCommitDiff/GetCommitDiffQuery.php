<?php
namespace Src\SourceControl\Application\Query\GetCommitDiff;

final class GetCommitDiffQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $baseSha,
        public readonly string $headSha,
    ) {}
}
