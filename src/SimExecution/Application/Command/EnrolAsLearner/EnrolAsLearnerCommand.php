<?php
namespace Src\SimExecution\Application\Command\EnrolAsLearner;

final class EnrolAsLearnerCommand
{
    public function __construct(
        public readonly string   $userId,
        public readonly string   $entryCategory,
        public readonly ?int     $yearsExperience = null,
        public readonly ?string  $organisationId = null
    ){}
}