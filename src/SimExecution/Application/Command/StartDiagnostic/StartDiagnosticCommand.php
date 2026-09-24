<?php
namespace Src\SimExecution\Application\Command\StartDiagnostic;

final class StartDiagnosticCommand
{
    public function __construct(public readonly ?string $userId) {}
}
