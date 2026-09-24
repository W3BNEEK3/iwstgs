<?php
namespace Src\LearnerProfile\Domain\Profile;

enum DimensionTier: string
{
    case Untested     = 'untested';
    case Basic        = 'basic';
    case Intermediate = 'intermediate';
    case Advanced     = 'advanced';
}
