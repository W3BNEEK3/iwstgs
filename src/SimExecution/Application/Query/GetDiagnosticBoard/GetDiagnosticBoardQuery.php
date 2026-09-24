<?php
namespace Src\SimExecution\Application\Query\GetDiagnosticBoard;

final class GetDiagnosticBoardQuery
{
    public function __construct(public readonly ?string $userId) {}
}
