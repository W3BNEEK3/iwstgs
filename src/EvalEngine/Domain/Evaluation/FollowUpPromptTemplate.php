<?php
namespace Src\EvalEngine\Domain\Evaluation;

/**
 * Read-only value object returned by FollowUpPromptTemplateRepository.
 */
final class FollowUpPromptTemplate
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $taskId,
        public readonly ?string $dimensionId,
        public readonly string $promptText,
    ) {}

    public function promptText(): string
    {
        return $this->promptText;
    }
}
