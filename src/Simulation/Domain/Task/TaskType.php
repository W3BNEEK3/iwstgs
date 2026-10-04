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
    /** v2: one step of a build in the learner's own repository, checked by acceptance tests + AI review. */
    case Milestone              = 'milestone';
    /** v2: review a teammate's pull request (Work Experience). */
    case Review                 = 'review';

    /**
     * true if this is an injected task (not part of the original content plan).
     * Consequence and suggestion tasks are injected at runtime by the EvalEngine.
     */
    public function isInjected(): bool
    {
        return match($this) {
            self::Core, self::DiagnosticScenario, self::Milestone, self::Review => false,
            default                              => true,
        };
    }

    /** Tasks every learner on the scenario must complete (not injected, not diagnostic). */
    public function isPlanned(): bool
    {
        return in_array($this, [self::Core, self::Milestone, self::Review], true);
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
