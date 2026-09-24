<?php

namespace Src\Simulation\Domain\Vault;

use Src\Simulation\Domain\Project\ProjectTemplateId;

final class ArtifactVaultItem
{
    private function __construct(
        private readonly string $id,
        private readonly ProjectTemplateId $projectId,
        private DocumentType $documentType,
        private string $title,
        private string $content,
        private ?string $rankGate,
        private ?PhaseGate $phaseGate,
        private bool $isReferenceDoc,
        private int $displayOrder,
    ) {}

    public static function create(
        string $id,
        ProjectTemplateId $projectId,
        DocumentType $documentType,
        string $title,
        string $content,
        ?string $rankGate,
        ?PhaseGate $phaseGate,
        bool $isReferenceDoc,
        int $displayOrder
    ): self {
        return new self(
            $id,
            $projectId,
            $documentType,
            $title,
            $content,
            $rankGate,
            $phaseGate,
            $isReferenceDoc,
            $displayOrder
        );
    }

    public static function reconstitute(
        string $id,
        string $projectId,
        string $documentType,
        string $title,
        string $content,
        ?string $rankGate,
        ?string $phaseGate,
        bool $isReferenceDoc,
        int $displayOrder
    ): self {
        return new self(
            $id,
            ProjectTemplateId::fromString($projectId),
            DocumentType::from($documentType),
            $title,
            $content,
            $rankGate,
            $phaseGate ? PhaseGate::from($phaseGate) : null,
            $isReferenceDoc,
            $displayOrder
        );
    }

    public function toPrimitives(): array
    {
        return [
            'id' => $this->id,
            'project_id' => (string) $this->projectId,
            'document_type' => $this->documentType->value,
            'title' => $this->title,
            'content' => $this->content,
            'rank_gate' => $this->rankGate,
            'phase_gate' => $this->phaseGate?->value,
            'is_reference_doc' => $this->isReferenceDoc,
            'display_order' => $this->displayOrder,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProjectId(): ProjectTemplateId
    {
        return $this->projectId;
    }

    public function getDocumentType(): DocumentType
    {
        return $this->documentType;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
