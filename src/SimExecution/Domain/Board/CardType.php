<?php
namespace Src\SimExecution\Domain\Board;

enum CardType: string
{
    case Consequence          = 'consequence';
    case Suggestion           = 'suggestion';
    case DiagnosticConsequence = 'diagnostic_consequence';
}
