<?php
namespace Src\Submission\Application\Query\GetSubmissionForm;

final class SubmissionFormView
{
    /**
     * @param DeliverableFieldView[] $deliverables
     * @param GuidancePromptFieldView[] $guidancePrompts
     * @param array<array{type: string, title: string, content: string}> $referenceMaterials
     */
    public function __construct(
        public readonly string $taskId,
        public readonly string $taskTitle,
        public readonly string $taskBrief,
        public readonly ?int $timeLimitMinutes,
        public readonly int $nextAttemptNumber,
        public readonly array $deliverables,
        public readonly string $resolvedScenarioText,
        public readonly ?string $scaffoldingHint,
        public readonly ?string $contextNote,
        public readonly array $guidancePrompts,
        public readonly string $cacComplexity,
        public readonly string $cacAutonomy,
        public readonly string $cacContextFidelity,
        public readonly array $referenceMaterials = [],
    ) {}
}
