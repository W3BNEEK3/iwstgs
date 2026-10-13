<?php
namespace Src\SourceControl\Domain;

final class HostCommit
{
    public function __construct(
        public readonly string $sha,
        public readonly string $message,
        public readonly string $committedAt,
        public readonly string $htmlUrl,
    ) {}

    public function shortSha(): string
    {
        return substr($this->sha, 0, 7);
    }

    public function headline(): string
    {
        return strtok($this->message, "\n") ?: '(no message)';
    }
}
