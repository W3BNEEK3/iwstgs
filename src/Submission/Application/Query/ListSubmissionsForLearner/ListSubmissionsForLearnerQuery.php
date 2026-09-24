<?php
namespace Src\Submission\Application\Query\ListSubmissionsForLearner;

final class ListSubmissionsForLearnerQuery
{
    public function __construct(public readonly string $learnerId) {}
}
