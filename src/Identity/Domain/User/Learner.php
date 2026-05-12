<?php
namespace Src\Identity\Domain\User;

use Src\Shared\Domain\AggregateRoot;

final class Learner extends AggregateRoot
{
    public function __construct(
        private readonly LearnerId   $learnerId,
        private string               $fullname,
        private readonly UserId      $userId,
        private EntryCategory        $entryCategory,
        private ?int                 $yearsExperience,
        private ?string              $organizationId
    ){}

    public static function register(
        LearnerId       $id,
        string          $fullname,
        UserId          $userId,
        EntryCategory   $entryCategory,
        ?int            $yearsExperience = null,
        ?string         $organizationId = null
    ): self {
        $learner = new self($id, $fullname, $userId, $entryCategory, $yearsExperience, $organizationId);

        $learner->recordEvent(
            new LearnerRegistered(
                learnerid: (string) $id,
                userId: (string) $userId,
                fullname: $fullname
            )
        );

        return $learner;
    }

    public static function reconstitute(
        LearnerId       $id,
        string          $fullname,
        UserId          $userId,
        EntryCategory   $entryCategory,
        ?int            $yearsExperience = null,
        ?string         $organizationId = null
    ): self {
        return new self($id, $fullname, $userId, $entryCategory, $yearsExperience, $organizationId);
    }

    public function id(): string
    {
        return (string) $this->learnerId;
    }

    public function learnerId(): LearnerId {return $this->learnerId};
    public function fullname(): string {return $this->fullname};
    public function userId(): UserId {return $this->userId};
    public function entryCategory(): EntryCategory {return $this->entryCategory};
    public function yearsExperience(): ?int {return $this->yearsExperience};
    public function organizationId(): ?string {return $this->organizationId};   
}