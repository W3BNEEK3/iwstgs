<?php
namespace Src\SimExecution\Domain\Enrollment;

enum EntryCategory: string
{
    case Inexperienced = 'inexperienced';
    case Experienced = 'experienced';
}