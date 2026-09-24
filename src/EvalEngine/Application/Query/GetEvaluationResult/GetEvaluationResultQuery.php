<?php
namespace Src\EvalEngine\Application\Query\GetEvaluationResult;

final class GetEvaluationResultQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $submissionId,
    ) {}
}
