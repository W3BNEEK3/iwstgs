<?php
namespace Src\SimExecution\Domain\Board;

enum InjectedCardStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Submitted  = 'submitted';
    case Skipped    = 'skipped';
}
