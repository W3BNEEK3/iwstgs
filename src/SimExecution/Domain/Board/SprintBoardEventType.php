<?php
namespace Src\SimExecution\Domain\Board;

enum SprintBoardEventType: string
{
    case ItemMovedToSprint  = 'item_moved_to_sprint';
    case ItemStatusChanged  = 'item_status_changed';
    case PriorityChanged    = 'priority_changed';
    case SprintGoalWritten  = 'sprint_goal_written';
    case SprintConfirmed    = 'sprint_confirmed';
    case SprintSubmitted    = 'sprint_submitted';
}
