<?php

namespace Src\Simulation\Domain\ConceptTag;

/**
 * The five categories a concept tag can belong to.
 *
 * Backed enum (PHP 8.1+): each case has an underlying string value.
 * The value matches exactly what is stored in the concept_tags.category column.
 *
 * Usage:
 *   ConceptTagCategory::from('se_concept')         // parses safely from DB
 *   ConceptTagCategory::SeConcept->value           // returns 'se_concept'
 *   ConceptTagCategory::SeConcept->label()         // returns 'Software Engineering Concept'
 */
enum ConceptTagCategory: string
{
    case SeConcept            = 'se_concept';
    case CsFundamental        = 'cs_fundamental';
    case ToolTechnology       = 'tool_technology';
    case ProfessionalPractice = 'professional_practice';
    case Regulatory           = 'regulatory';

    /**
     * Returns a human-readable label for display in the admin UI.
     *
     * This is a method on the enum — enums in PHP 8.1+ can have methods,
     * just like classes. They cannot have mutable properties, but methods
     * and constants are allowed.
     */
    public function label(): string
    {
        // match expression (PHP 8.0+): like switch but requires exhaustive matching
        // and returns a value. Unlike switch, it throws UnhandledMatchError if no
        // arm matches — making it safer than switch's implicit fall-through.
        return match($this) {
            self::SeConcept            => 'Software Engineering Concept',
            self::CsFundamental        => 'Computer Science Fundamental',
            self::ToolTechnology       => 'Tool or Technology',
            self::ProfessionalPractice => 'Professional Practice',
            self::Regulatory           => 'Regulatory / Compliance',
        };
    }
}
