<?php
namespace Src\SourceControl\Domain;

final class GitHubConnection
{
    public function __construct(
        public readonly string $userId,
        public readonly int $githubUserId,
        public readonly string $githubLogin,
        public readonly string $connectedAt,
    ) {}
}
