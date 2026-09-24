<?php
namespace Src\SimExecution\Domain\Backlog;

interface LearnerBacklogItemRepository
{
    public function save(LearnerBacklogItem $item): void;

    public function findById(string $id): ?LearnerBacklogItem;

    /** @return LearnerBacklogItem[] */
    public function findAllForSession(string $learnerSessionId): array;

    /** @return LearnerBacklogItem[] */
    public function findAllForSprint(string $sprintId): array;

    public function existsForSession(string $learnerSessionId): bool;

    /** @return string[] template_item_id values already copied into this session's backlog */
    public function seededTemplateIdsForSession(string $learnerSessionId): array;
}
