<?php
namespace Src\SourceControl\Application\Command\ConnectGitHubAccount;

use Src\SourceControl\Domain\GitHubAccountInUse;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\SourceControlRepository;

/** "Connect GitHub": records which GitHub account belongs to this Areyna user. One account per user and vice versa. */
final class ConnectGitHubAccountHandler
{
    public function __construct(
        private readonly SourceControlRepository $store,
        private readonly RepositoryHost $host,
    ) {}

    public function handle(ConnectGitHubAccountCommand $command): void
    {
        $user = $this->host->identifyUser($command->oauthCode);

        $existing = $this->store->connectionForGitHubUser($user->id);
        if ($existing !== null && $existing->userId !== $command->userId) {
            throw new GitHubAccountInUse($user->login);
        }

        $this->store->saveConnection($command->userId, $user->id, $user->login);
    }
}
