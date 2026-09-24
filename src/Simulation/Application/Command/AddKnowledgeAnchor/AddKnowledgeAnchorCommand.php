<?php
namespace Src\Simulation\Application\Command\AddKnowledgeAnchor;

final class AddKnowledgeAnchorCommand
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $conceptName,
        public readonly bool    $isRequired,
        public readonly ?string $conceptId = null,
        public readonly ?string $domain = null,
        public readonly ?string $applicationExpectation = null,
        public readonly ?string $remediationHint = null,
    ) {}
}
