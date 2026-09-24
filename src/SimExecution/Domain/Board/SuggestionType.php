<?php
namespace Src\SimExecution\Domain\Board;

enum SuggestionType: string
{
    case Corrective  = 'corrective';
    case Extensional = 'extensional';
}
