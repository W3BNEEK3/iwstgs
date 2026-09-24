<?php
namespace Src\AIMediation\Domain\Review;

enum ReviewerDecision: string
{
    case Proficient    = 'proficient';
    case NotProficient = 'not_proficient';
    case Escalate      = 'escalate';
}
