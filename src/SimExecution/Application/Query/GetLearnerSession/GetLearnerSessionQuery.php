<?php
namespace Src\SimExecution\Application\Query\GetLearnerSession;

final class GetLearnerSessionQuery
{
    public function __construct(public readonly string $sessionId) {}
}
