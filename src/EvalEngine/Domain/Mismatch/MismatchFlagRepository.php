<?php
namespace Src\EvalEngine\Domain\Mismatch;

/**
 * mismatch_flags has no domain aggregate here — created by MismatchDetector
 * when a submission's quality significantly exceeds the learner's current
 * rank (underestimation). The full "surface an escalation prompt and record
 * the learner's response" flow (prompt_issued/learner_response/resolution)
 * has no UI yet — same disclosed-gap treatment as Phase 8's follow-up
 * prompts. This only records the detected fact.
 */
interface MismatchFlagRepository
{
    public function create(string $learnerId, string $mismatchType): string;
}
