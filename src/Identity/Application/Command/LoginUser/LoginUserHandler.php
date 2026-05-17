<?php
namespace Src\Identity\Application\Command\LoginUser;

use Src\Identity\Domain\Auth\AuthenticationService;

final class LoginUserHandler
{
    public function __construct(
        private readonly AuthenticationService $authService,
    ) {}

    public function handle(LoginUserCommand $command): void
    {
        $user = $this->authService->authenticate($command->email, $command->password);
    }
}