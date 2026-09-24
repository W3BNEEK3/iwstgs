<?php
namespace Src\SimExecution\Domain\Diagnostic;

enum DiagnosticStatus: string
{
    case InProgress = 'in_progress';
    case Complete   = 'complete';
    case Abandoned  = 'abandoned';
}
