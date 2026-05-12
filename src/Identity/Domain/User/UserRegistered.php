<?php
namespace Src\Identity\Domain\User;

use Src\Shared\Domain\DomainEvent;

final class UserRegistered extends DomainEvent
{
    public function __construct(
       public readonly string $userId,
       public readonly string $email,
       public readonly string $fullname,
    ){
        parent::__construct();
    }
}