<?php

namespace Src\Identity\Infrastructure\Auth;

use Illuminate\Support\Facades\Auth;
use Src\Identity\Domain\Auth\AuthenticationService;
use Src\Identity\Domain\Exceptions\InvalidCredentialsException;
use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserRepository;

final class LaravelAuthService implements AuthenticationService
{
    public function __construct(private readonly UserRepository $repository) {}

    public function authenticate(string $email, string $password): User
    {
        $success = Auth::attempt(['email' => $email, 'password' => $password]);

        if (! $success) {
            throw new InvalidCredentialsException();
        }

        $user = $this->repository->findByEmail($email);

        if ($user === null) {
            throw new InvalidCredentialsException();
        }

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}