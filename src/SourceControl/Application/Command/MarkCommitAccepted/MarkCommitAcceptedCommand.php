<?php
namespace Src\SourceControl\Application\Command\MarkCommitAccepted;

final class MarkCommitAcceptedCommand
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $sha,
    ) {}
}
