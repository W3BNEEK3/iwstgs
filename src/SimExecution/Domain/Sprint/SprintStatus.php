<?php
namespace Src\SimExecution\Domain\Sprint;

enum SprintStatus: string
{
    case Planning  = 'planning';
    case Active    = 'active';
    case Submitted = 'submitted';
    case Evaluated = 'evaluated';
}
