<?php
namespace Src\AIMediation\Domain\Audit;

enum ActionTaken: string
{
    case TaskCompleted                 = 'task_completed';
    case ConsequenceInjected           = 'consequence_injected';
    case SuggestionQueued              = 'suggestion_queued';
    case FollowUpPromptIssued          = 'follow_up_prompt_issued';
    case RankReviewTriggered           = 'rank_review_triggered';
    case MismatchFlagged               = 'mismatch_flagged';
    case HumanReviewQueued             = 'human_review_queued';
    case DimensionTargetedNextScenario = 'dimension_targeted_next_scenario';
    case KnowledgeAnchorHintIssued     = 'knowledge_anchor_hint_issued';
    case NoAction                      = 'no_action';
}
