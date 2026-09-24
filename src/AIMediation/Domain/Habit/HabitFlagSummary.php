<?php
namespace Src\AIMediation\Domain\Habit;

final class HabitFlagSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $learnerId,
        public readonly string $habitDescription,
        public readonly ?string $sourceTaskId,
        public readonly int $observationCount,
        public readonly ?string $suggestionCardId,
        public readonly bool $isResolved,
    ) {}
}
