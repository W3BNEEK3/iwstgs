<?php
namespace Src\SimExecution\Application\Query\ListSessionsForLearner;

final class ListSessionsForLearnerQuery
{
    public function __construct(public readonly string $learnerId) {}
}
