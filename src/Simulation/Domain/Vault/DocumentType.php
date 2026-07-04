<?php

namespace Src\Simulation\Domain\Vault;

enum DocumentType: string
{
    case BUSINESS_CONTEXT = 'business_context';
    case PRD = 'prd';
    case SRS = 'srs';
    case SAD = 'sad';
    case CODING_GUIDELINES = 'coding_guidelines';
    case GLOSSARY = 'glossary';
    case SPRINT_GOAL_TEMPLATE = 'sprint_goal_template';
}
