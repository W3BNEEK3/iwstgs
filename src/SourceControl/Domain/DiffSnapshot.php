<?php
namespace Src\SourceControl\Domain;

/** A milestone's changes, ready for review: the unified diff (trimmed and filtered) and a summary. */
final class DiffSnapshot
{
    /** @param array{files: array<int, array{filename: string, status: string, additions: int, deletions: int, included: bool}>, commits: int, truncated: bool, omitted: string[]} $summary */
    public function __construct(
        public readonly string $text,
        public readonly array $summary,
    ) {}
}
