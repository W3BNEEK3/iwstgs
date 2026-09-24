<?php
namespace Src\SimExecution\Application\Query\GetLearnerName;

final class GetLearnerNameQuery
{
    public function __construct(public readonly string $learnerId) {}
}
