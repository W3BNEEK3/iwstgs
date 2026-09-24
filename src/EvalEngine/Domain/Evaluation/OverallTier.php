<?php
namespace Src\EvalEngine\Domain\Evaluation;

enum OverallTier: string
{
    case Beginning     = 'beginning';
    case Developing    = 'developing';
    case Proficient    = 'proficient';
    case Distinguished = 'distinguished';
}
