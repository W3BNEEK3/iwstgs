<?php
namespace Src\Simulation\Domain\Task;

// KnowledgeAnchor — references a concept_tag by concept_id
final class KnowledgeAnchor
{
    public function __construct(
        private readonly string $id,
        private ?string $conceptId,
        private string $conceptName,
        private ?string $domain,
        private ?string $applicationExpectation,
        private bool $isRequired,
        private ?string $remediationHint,
    ) {}

    public function id(): string { return $this->id; }

    public function toPrimitives(): array
    {
        return [
            'id'                      => $this->id,
            'concept_id'              => $this->conceptId,
            'concept_name'            => $this->conceptName,
            'domain'                  => $this->domain,
            'application_expectation' => $this->applicationExpectation,
            'is_required'             => $this->isRequired,
            'remediation_hint'        => $this->remediationHint,
        ];
    }
}
