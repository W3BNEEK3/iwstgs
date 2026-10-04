<?php
namespace Src\SourceControl\Application\Command\ConnectGitHubAccount;

final class ConnectGitHubAccountCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $oauthCode,
    ) {}
}
