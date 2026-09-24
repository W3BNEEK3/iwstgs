<?php
namespace Src\SimExecution\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class DiagnosticContentNotConfiguredException extends DomainException
{
    public function __construct()
    {
        parent::__construct('no diagnostic scenario has been authored yet');
    }
}
