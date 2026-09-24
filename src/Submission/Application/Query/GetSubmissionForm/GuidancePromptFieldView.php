<?php
namespace Src\Submission\Application\Query\GetSubmissionForm;

final class GuidancePromptFieldView
{
    public function __construct(
        public readonly string $promptText,
        public readonly string $deliveryMode,
    ) {}
}
