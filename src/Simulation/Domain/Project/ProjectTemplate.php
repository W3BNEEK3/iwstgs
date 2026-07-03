<?php
namespace Src\Simulation\Domain\Project;

use Src\Shared\Domain\AggregateRoot;

final class ProjectTemplate extends AggregateRoot
{
    public function __construct(
        private readonly ProjectTemplateId $id,
        private string   $title,
        private string   $projectType,
        private string   $businessContext,
        private array    $specializationTags,
        private string   $difficultyLevel,
        private ?string  $tagline = null,
        private ?string  $businessDomain = null,
        private ?array   $stakeholders = null,
        private ?array   $overarchingConstraints = null,
        private ?array   $techContext = null,
        private ?string  $organisationId = null,
        private ?string  $codingGuidelines = null,
        private ?array   $velocityEstimate = null,
        private bool     $isPublished = false,
        private bool     $isActive = true,
    ) {}

    public static function create(
        ProjectTemplateId $id,
        string $title,
        string $projectType,
        string $businessContext,
        array  $specializationTags,
        string $difficultyLevel,
        ?string $tagline = null,
        ?string $businessDomain = null,
        ?array  $stakeholders = null,
        ?array  $overarchingConstraints = null,
        ?array  $techContext = null,
        ?string $organisationId = null,
        ?string $codingGuidelines = null,
        ?array  $velocityEstimate = null,
    ): self {
        $project = new self(
            $id, $title, $projectType, $businessContext, $specializationTags,
            $difficultyLevel, $tagline, $businessDomain, $stakeholders,
            $overarchingConstraints, $techContext, $organisationId,
            $codingGuidelines, $velocityEstimate,
            isPublished: false, isActive: true,
        );

        $project->recordEvent(new ProjectTemplateCreated((string) $id, $title));

        return $project;
    }

    public static function reconstitute(
        ProjectTemplateId $id,
        string $title,
        string $projectType,
        string $businessContext,
        array  $specializationTags,
        string $difficultyLevel,
        ?string $tagline,
        ?string $businessDomain,
        ?array  $stakeholders,
        ?array  $overarchingConstraints,
        ?array  $techContext,
        ?string $organisationId,
        ?string $codingGuidelines,
        ?array  $velocityEstimate,
        bool    $isPublished,
        bool    $isActive,
    ): self {
        return new self(
            $id, $title, $projectType, $businessContext, $specializationTags,
            $difficultyLevel, $tagline, $businessDomain, $stakeholders,
            $overarchingConstraints, $techContext, $organisationId,
            $codingGuidelines, $velocityEstimate, $isPublished, $isActive,
        );
    }

    // --- Behaviour (this is why it's an entity, not an array) ---

    public function rename(string $title): void          { $this->title = $title; }
    public function updateBusinessContext(string $c): void { $this->businessContext = $c; }
    public function publish(): void                       { $this->isPublished = true; }
    public function unpublish(): void                     { $this->isPublished = false; }

    public function id(): string { return (string) $this->id; }
    public function projectTemplateId(): ProjectTemplateId { return $this->id; }
    public function isPublished(): bool { return $this->isPublished; }

    /** Flat representation for the mapper — keeps persistence code short. */
    public function toPrimitives(): array
    {
        return [
            'id'                      => (string) $this->id,
            'title'                   => $this->title,
            'project_type'            => $this->projectType,
            'business_context'        => $this->businessContext,
            'specialization_tags'     => $this->specializationTags,
            'difficulty_level'        => $this->difficultyLevel,
            'tagline'                 => $this->tagline,
            'business_domain'         => $this->businessDomain,
            'stakeholders'            => $this->stakeholders,
            'overarching_constraints' => $this->overarchingConstraints,
            'tech_context'            => $this->techContext,
            'organisation_id'         => $this->organisationId,
            'coding_guidelines'       => $this->codingGuidelines,
            'velocity_estimate'       => $this->velocityEstimate,
            'is_published'            => $this->isPublished,
            'is_active'               => $this->isActive,
        ];
    }
}
