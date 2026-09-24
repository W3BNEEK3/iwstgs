<?php

namespace Src\Simulation\Domain\Project;

/**
 * The difficulty level of a project template.
 *
 * Shown in the project catalogue so learners can choose appropriate projects.
 */
enum DifficultyLevel: string
{
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced     = 'advanced';
}
