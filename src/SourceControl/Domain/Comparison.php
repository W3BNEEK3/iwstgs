<?php
namespace Src\SourceControl\Domain;

/** The changes between two commits. */
final class Comparison
{
    /** @param array<int, array{filename: string, status: string, additions: int, deletions: int, patch: ?string}> $files */
    public function __construct(
        public readonly array $files,
        public readonly int $commitCount,
    ) {}
}
