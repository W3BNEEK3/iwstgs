<?php

namespace Src\Simulation\Domain\Task;

/**
 * The five types a task can be.
 *
 * This enum is used both in the domain (to enforce valid task types in PHP)
 * and by the database (the ENUM column in the tasks table stores the ->value).
 *
 * PHP 8.1 backed enums let you convert between the PHP enum and the DB string
 * safely using from() and tryFrom():
 *   TaskType::from('core')         // returns TaskType::Core, throws on invalid
 *   TaskType::tryFrom('invalid')   // returns null instead of throwing
 */
enum TaskType: string
{
    case Core                   = 'core';
    case Consequence            = 'consequence';
    case Suggestion             = 'suggestion';
    case DiagnosticScenario     = 'diagnostic_scenario';
    case DiagnosticConsequence  = 'diagnostic_consequence';

    /**
     * true if this is an injected task (not part of the original content plan).
     * Consequence and suggestion tasks are injected at runtime by the EvalEngine.
     */
    public function isInjected(): bool
    {
        return match($this) {
            self::Core, self::DiagnosticScenario => false,
            default                              => true,
        };
    }

    /**
     * true if this task is part of the diagnostic pathway.
     */
    public function isDiagnostic(): bool
    {
        return match($this) {
            self::DiagnosticScenario, self::DiagnosticConsequence => true,
            default                                               => false,
        };
    }
}
