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

    /**
     * Every criterion across every task, with its task's title attached —
     * for the admin competence-dimensions screen, which groups task-level
     * criteria under each canonical dimension rather than by task.
     *
     * @return array<int, array{id:string,taskId:string,taskTitle:string,parentDimensionId:string,taskDimensionLabel:string,complexityLevel:string,weight:string}>
     */
    public function findAllWithTaskTitle(): array;
}
