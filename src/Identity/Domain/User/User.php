<?php
namespace Src\Identity\Domain\User;

use Src\Shared\Domain\AggregateRoot;

final class User extends AggregateRoot
{
    public function __construct(
        private readonly UserId    $userId,
        private string             $fullname,
        private string             $email,
        private string             $passwordHash
    ){}

    public static function register(
        UserId      $userId,
        string      $fullname,
        string      $email,
        string      $passwordHash
    ): self {
        $user = new self($userId, $fullname, $email, $passwordHash);

        $user->recordEvent(
            new UserRegistered(
                userId: (string) $userId,
                fullname: $fullname,
                email: $email
            )
        );

        return $user;
    }
    
    public static function reconstitute(
        UserId      $userId,
        string      $fullname,
        string      $email,
        string      $passwordHash
    ): self {
        return new self($userId, $fullname, $email, $passwordHash);
    }

    public function id(): string
    {
        return (string) $this->userId;
    }

    public function userId(): UserId {return $this->userId;}
    public function fullname(): string {return $this->fullname;}
    public function email(): string {return $this->email;}
    public function passwordHash(): string {return $this->passwordHash;}
}