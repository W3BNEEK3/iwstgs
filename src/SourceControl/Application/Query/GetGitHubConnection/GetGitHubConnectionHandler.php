<?php
namespace Src\SourceControl\Application\Query\GetGitHubConnection;

use Src\SourceControl\Domain\GitHubConnection;
use Src\SourceControl\Domain\SourceControlRepository;

final class GetGitHubConnectionHandler
{
    public function __construct(private readonly SourceControlRepository $store) {}

    public function handle(GetGitHubConnectionQuery $query): ?GitHubConnection
    {
        return $this->store->connectionForUser($query->userId);
    }
}
