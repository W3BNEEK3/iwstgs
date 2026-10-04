<?php
namespace Src\SourceControl\Application\Query\GetGitHubConnection;

final class GetGitHubConnectionQuery
{
    public function __construct(
        public readonly string $userId,
    ) {}
}
