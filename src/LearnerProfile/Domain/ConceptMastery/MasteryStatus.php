<?php
namespace Src\LearnerProfile\Domain\ConceptMastery;

enum MasteryStatus: string
{
    case NotEncountered = 'not_encountered';
    case Encountered    = 'encountered';
    case PartiallyMet   = 'partially_met';
    case Mastered       = 'mastered';
}
