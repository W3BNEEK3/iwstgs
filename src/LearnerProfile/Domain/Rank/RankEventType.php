<?php
namespace Src\LearnerProfile\Domain\Rank;

enum RankEventType: string
{
    case InitialAssignment    = 'initial_assignment';
    case Escalation           = 'escalation';
    case DeEscalation         = 'de_escalation';
    case SubLevelProgression  = 'sub_level_progression';
    case ReviewTriggered      = 'review_triggered';
}
