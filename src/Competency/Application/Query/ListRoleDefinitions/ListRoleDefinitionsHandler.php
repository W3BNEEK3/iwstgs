<?php
namespace Src\Competency\Application\Query\ListRoleDefinitions;

use Src\Competency\Domain\Role\RoleDefinitionRepository;

final class ListRoleDefinitionsHandler
{
    public function __construct(private readonly RoleDefinitionRepository $repository) {}

    /** @return \Src\Competency\Domain\Role\RoleDefinitionSummary[] */
    public function handle(ListRoleDefinitionsQuery $query): array
    {
        return $this->repository->all();
    }
}
