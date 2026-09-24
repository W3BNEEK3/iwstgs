<?php
namespace Src\SimExecution\Domain\Diagnostic;

enum DiagnosticPathway: string
{
    case Interview         = 'interview';
    case AssessmentTasks   = 'assessment_tasks';
    case DiagnosticScenario = 'diagnostic_scenario';
}
