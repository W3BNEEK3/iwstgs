<?php
namespace Src\SimExecution\Application\Query\GetBuildProgress;

final class GetBuildProgressQuery
{
    public function __construct(public readonly string $learnerSessionId) {}
}
