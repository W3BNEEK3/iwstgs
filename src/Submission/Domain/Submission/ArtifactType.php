<?php
namespace Src\Submission\Domain\Submission;

enum ArtifactType: string
{
    case Diagram  = 'diagram';
    case Document = 'document';
    case Notes    = 'notes';
    case Other    = 'other';
}
