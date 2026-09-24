<?php
namespace Src\SimExecution\Domain\Sprint;

/**
 * Integration Spec §15 (Sprint Goal Interaction by Rank):
 *   Junior-1..2   -> SystemDefined            (fully pre-written, learner acknowledges)
 *   Junior-3/Mid-1 -> LearnerCompletedTemplate (pre-written with blanks, learner completes)
 *   Mid-2+        -> LearnerDefined            (blank, learner writes in full)
 * See SprintGoalPolicy for the rank -> source mapping.
 */
enum SprintGoalSource: string
{
    case SystemDefined            = 'system_defined';
    case LearnerDefined           = 'learner_defined';
    case LearnerCompletedTemplate = 'learner_completed_template';
}
