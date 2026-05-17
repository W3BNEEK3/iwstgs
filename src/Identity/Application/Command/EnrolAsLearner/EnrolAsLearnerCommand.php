<?php
namespace Src\Identity\Application\Command\EnrolAsLearner;

final class EnrolAsLearner
{
    public function __construct(
        public readonly string  $userId,
        public readonly string  $entryCategory,
        public readonly ?int    $yearsExperience = null,
        public readonly ?string $organisationId = null,
    ){}
    
}