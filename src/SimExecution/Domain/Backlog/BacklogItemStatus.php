<?php
namespace Src\SimExecution\Domain\Backlog;

enum BacklogItemStatus: string
{
    case Backlog    = 'backlog';
    case InSprint   = 'in_sprint';
    case InProgress = 'in_progress';
    case Done       = 'done';
    case Blocked    = 'blocked';
}
