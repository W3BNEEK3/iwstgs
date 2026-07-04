<?php
namespace Src\Simulation\Domain\Rubric;

interface RubricCriterionRepository
{
    public function save(RubricCriterion $criterion): void;
    
    public function findById(RubricCriterionId $id): ?RubricCriterion;
    
    /** @return RubricCriterion[] */
    public function findByTask(string $taskId): array;
    
    public function remove(RubricCriterionId $id): void;

    /** Resolve the project's rubric_set for a task: task → scenario → project → rubric_set. */
    public function rubricSetIdForTask(string $taskId): ?string;
}
