<?php
namespace Src\Identity\Application\Command\RegisterUser;

use Illuminate\Support\Facades\Hash;
use Src\Identity\Domain\Exceptions\EmailAlreadyTakenException;
use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserId;
use Src\Identity\Domain\User\UserRepository;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UuidGenerator $uuidGenerator
    ){}

    public function handle(RegisterUserCommand $command): void
    {
        if ($this->repository->findByEmail($command->email)!== null){
            throw new EmailAlreadyTakenException($command->email);
        }

        $user = User::register(
            id: UserId::fromString($this->uuidGenerator->generate()),
            fullname: $command->name,
            email: $command->email,
            passwordHash: Hash::make($command->password),
        );

        $this->repository->save($user);

        foreach ($user->releaseEvents() as $event){
            event($event);
        }
    }
}