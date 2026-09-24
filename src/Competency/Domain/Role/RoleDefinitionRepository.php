<?php
namespace Src\Competency\Domain\Role;

interface RoleDefinitionRepository
{
    /** @return RoleDefinitionSummary[] */
    public function all(): array;
}
