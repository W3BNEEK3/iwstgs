<?php
namespace Src\SimExecution\Application\Query\GetUserIdForLearner;

final class GetUserIdForLearnerQuery
{
    public function __construct(public readonly string $learnerId) {}
}
