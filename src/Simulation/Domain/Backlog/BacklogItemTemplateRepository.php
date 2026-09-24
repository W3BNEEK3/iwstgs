<?php
namespace Src\Simulation\Domain\Backlog;

/**
 * Read-only: backlog_item_templates is authored content with no lifecycle of
 * its own yet (no admin UI exists for it — seeded directly, same as the
 * MedQueue project/scenario/task fixtures). Same "summary repository"
 * shape as RoleDefinitionRepository/CompetenceDimensionRepository.
 */
interface BacklogItemTemplateRepository
{
    /** @return BacklogItemTemplateSummary[] ordered by display_order */
    public function allForProject(string $projectId): array;

    public function findById(string $id): ?BacklogItemTemplateSummary;
}
