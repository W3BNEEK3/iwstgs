<?php
namespace Src\Reporting\Application\Query\GetSessionSummary;

final class GetSessionSummaryQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sessionId,
    ) {}
}
