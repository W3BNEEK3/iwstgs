<?php
namespace Src\SimExecution\Domain\Board;

enum VisualTreatment: string
{
    case ConsequenceAmber = 'consequence_amber';
    case SuggestionTeal   = 'suggestion_teal';
    case DiagnosticDeepBlue = 'diagnostic_deep_blue';
}
