<?php
namespace Src\SourceControl\Domain;

/** A learner's repository, linked to one project session (design doc v2-01 §1.8). */
final class LinkedRepository
{
    public const LINKED = 'linked';
    public const ACCESS_LOST = 'access_lost';

    public function __construct(
        public readonly string $id,
        public readonly string $learnerSessionId,
        public readonly int $githubRepoId,
        public readonly string $fullName,
        public readonly int $installationId,
        public readonly string $defaultBranch,
        public readonly string $templateRepo,
        public readonly string $status,
        public readonly string $startSha,
        public readonly ?string $lastAcceptedSha,
    ) {}

    public function isUsable(): bool
    {
        return $this->status === self::LINKED;
    }

    /** What a milestone's changes are compared against: the last accepted milestone, or the starter code. */
    public function diffBase(): string
    {
        return $this->lastAcceptedSha ?? $this->startSha;
    }

    public function htmlUrl(): string
    {
        return "https://github.com/{$this->fullName}";
    }
}
