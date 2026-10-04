<?php
namespace Src\SourceControl\Application\Query\ListRecentCommits;

final class ListRecentCommitsQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly int $limit = 10,
    ) {}
}
