<?php
namespace Src\Application\Command\EnrolAsLearner;

final class EnrolAsLearnerCommand
{
    public function __construct(
        private readonly string   $userId,
        private readonly string   $entryCategory,
        private readonly ?int     $yearsExperience = null,
        private readonly ?string  $organisationId = null
    ){}
}