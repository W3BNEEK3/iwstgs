<?php
namespace Src\Identity\Domain\Auth;

use Src\Identity\Domain\User\User;

interface AuthenticationService
{
    public function authenticate(string $email, string $password): ?User;
    public function logout(): void;
}