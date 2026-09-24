<?php
namespace Src\EvalEngine\Domain\Evaluation;

/**
 * Gap 2 — Repository for follow_up_prompt_templates. Selecting a template
 * is the mechanism by which routeUncertain() knows what question to put
 * to the learner. The templates are authored by content designers (via
 * admin UI) and linked to tasks by task_id or dimension_id.
 */
interface FollowUpPromptTemplateRepository
{
    /**
     * Returns the most relevant prompt template for this task, or null if
     * none has been authored. Matching priority: task-specific template
     * first, then any dimension-level fallback, then null.
     */
    public function findForTask(string $taskId): ?FollowUpPromptTemplate;
}
