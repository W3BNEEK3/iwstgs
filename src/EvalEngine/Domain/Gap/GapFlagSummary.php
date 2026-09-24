<?php
namespace Src\EvalEngine\Domain\Gap;

final class GapFlagSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $dimensionId,
        public readonly ?string $subCriterionId,
        public readonly string $sourceTaskId,
        public readonly string $sourceSessionId,
        public readonly bool $isResolved,
        public readonly ?string $resolvedAt,
        public readonly ?string $createdAt,
    ) {}
}
