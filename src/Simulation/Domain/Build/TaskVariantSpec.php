<?php
namespace Src\Simulation\Domain\Build;

/** What a milestone means in one stack: extra brief, the acceptance tests it introduces, hints. */
final class TaskVariantSpec
{
    /**
     * @param string[] $acceptanceTests test IDs this task introduces, e.g. ["T2.1", "T2.2"]
     * @param array<string, string>|null $hints extra hints keyed by autonomy level (low/mid/high)
     */
    public function __construct(
        public readonly string $taskId,
        public readonly string $stackVariantId,
        public readonly ?string $briefAddendum,
        public readonly array $acceptanceTests,
        public readonly ?array $hints,
        public readonly ?string $referenceTag,
    ) {}
}
