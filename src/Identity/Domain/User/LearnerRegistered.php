<?php
namespace Src\Identity\Domain\User;

use Src\Shared\Domain\DomainEvent;

final class LearnerRegistered extends DomainEvent
{
    public function __construct(
       public readonly string $learnerId,
       public readonly string $userId,    
       public readonly string $fullname,        
    ){
        parent::__construct();
    }
}