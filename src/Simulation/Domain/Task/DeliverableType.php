<?php
namespace Src\Simulation\Domain\Task;

enum DeliverableType: string
{
    case WrittenExplanation = 'written_explanation';
    case Artifact           = 'artifact';
    case Code               = 'code';
    case Diagram            = 'diagram';
    case Document           = 'document';
}
