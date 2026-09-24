<?php
namespace Src\EvalEngine\Application\Command\AnswerFollowUpPrompt;

/**
 * Gap 2 — Issued when a learner submits their answer to a follow-up
 * clarification prompt generated after an uncertain evaluation. The handler
 * re-evaluates the original submission combined with this answer.
 */
final class AnswerFollowUpPromptCommand
{
    public function __construct(
        public readonly string $evaluationId,
        public readonly string $submissionId,
        public readonly string $learnerId,
        public readonly string $learnerSessionId,
        public readonly string $answerText,
    ) {}
}
