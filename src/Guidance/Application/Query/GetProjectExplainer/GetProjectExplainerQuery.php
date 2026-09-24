<?php
namespace Src\Guidance\Application\Query\GetProjectExplainer;

final class GetProjectExplainerQuery
{
    public function __construct(public readonly string $projectId) {}
}
