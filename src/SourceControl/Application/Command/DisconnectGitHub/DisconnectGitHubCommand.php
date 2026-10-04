<?php
namespace Src\SourceControl\Application\Command\DisconnectGitHub;

final class DisconnectGitHubCommand
{
    public function __construct(
        public readonly string $userId,
    ) {}
}
