<?php
namespace Src\SourceControl\Application\Query\GetLinkedRepository;

use Src\SourceControl\Domain\LinkedRepository;
use Src\SourceControl\Domain\SourceControlRepository;

final class GetLinkedRepositoryHandler
{
    public function __construct(private readonly SourceControlRepository $store) {}

    public function handle(GetLinkedRepositoryQuery $query): ?LinkedRepository
    {
        return $this->store->repositoryForSession($query->learnerSessionId);
    }
}
