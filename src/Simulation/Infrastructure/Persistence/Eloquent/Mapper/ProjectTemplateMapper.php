<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;

final class ProjectTemplateMapper
{
    public function toEntity(ProjectTemplateModel $m): ProjectTemplate
    {
        return ProjectTemplate::reconstitute(
            id:                     ProjectTemplateId::fromString($m->id),
            title:                  $m->title,
            projectType:            $m->project_type,
            businessContext:        $m->business_context,
            specializationTags:     $m->specialization_tags ?? [],
            difficultyLevel:        $m->difficulty_level instanceof \BackedEnum ? $m->difficulty_level->value : $m->difficulty_level,
            tagline:                $m->tagline,
            businessDomain:         $m->business_domain,
            stakeholders:           $m->stakeholders,
            overarchingConstraints: $m->overarching_constraints,
            techContext:            $m->tech_context,
            organisationId:         $m->organisation_id,
            codingGuidelines:       $m->coding_guidelines,
            velocityEstimate:       $m->velocity_estimate,
            isPublished:            (bool) $m->is_published,
            isActive:               (bool) $m->is_active,
        );
    }
}
