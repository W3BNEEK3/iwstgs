<?php
namespace Src\SimExecution\Domain\Session;

enum SessionStatus: string
{
    case Diagnostic = 'diagnostic';
    case Active     = 'active';
    case Complete   = 'complete';
    case Suspended  = 'suspended';
}
