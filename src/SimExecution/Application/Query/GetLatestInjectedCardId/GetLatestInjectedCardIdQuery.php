<?php
namespace Src\SimExecution\Application\Query\GetLatestInjectedCardId;

final class GetLatestInjectedCardIdQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $sourceTaskId,
    ) {}
}
