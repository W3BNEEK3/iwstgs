<?php
namespace Src\Identity\Application\Command\RegisterUser;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ){}
}