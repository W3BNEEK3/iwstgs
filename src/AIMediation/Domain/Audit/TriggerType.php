<?php
namespace Src\AIMediation\Domain\Audit;

enum TriggerType: string
{
    case SubmissionReceived        = 'submission_received';
    case FailureDetected           = 'failure_detected';
    case UncertainEvaluation       = 'uncertain_evaluation';
    case MismatchDetected          = 'mismatch_detected';
    case HabitDetected             = 'habit_detected';
    case DimensionWeakness         = 'dimension_weakness';
    case FailureThresholdExceeded  = 'failure_threshold_exceeded';
    case ScenarioComplete          = 'scenario_complete';
    case KnowledgeAnchorPartial    = 'knowledge_anchor_partial';
}
