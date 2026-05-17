<?php
namespace Src\Identity\Application\Command\LoginUser;

final class LoginUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool $remember = false,
    ) {}
}