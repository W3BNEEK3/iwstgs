<?php
namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\AggregateRoot;

final class Learner extends AggregateRoot
{
    public function __construct(
        private readonly LearnerId   $learnerId,
        private readonly string      $userId,
        private readonly string      $fullname,
        private EntryCategory        $entryCategory,
        private ?int                 $yearsExperience,
        private ?string              $organisationId
    ){}

    public static function enrol(
        LearnerId       $id,
        string          $userId,
        string          $fullname,
        EntryCategory   $entryCategory,
        ?int            $yearsExperience = null,
        ?string         $organisationId = null
    ): self{
        $learner = new self($id, $userId, $fullname, $entryCategory, $yearsExperience, $organisationId);

        $learner->recordEvent(new LearnerEnrolled(
            learnerId: (string) $id,
            userId: $userId,
            entryCategory: $entryCategory->value,
            yearsExperience: $yearsExperience
        ));

        return $learner;
    }

    public static function reconstitute(
        LearnerId        $id,
        string           $userId,
        string           $fullname,
        EntryCategory    $entryCategory,
        ?int             $yearsExperience = null,
        ?string          $organisationId = null
    ): self{
        return new self($id, $userId, $fullname, $entryCategory, $yearsExperience, $organisationId);
    }

    public function id(): string {return (string) $this->learnerId;}

    public function learnerId(): LearnerId {return $this->learnerId;}
    public function userId(): string {return $this->userId;}
    public function fullname(): string {return $this->fullname;}
    public function entryCategory(): EntryCategory {return $this->entryCategory;}
    public function yearsExperience(): ?int {return $this->yearsExperience;}
    public function organisationId(): ?string {return $this->organisationId;}
}