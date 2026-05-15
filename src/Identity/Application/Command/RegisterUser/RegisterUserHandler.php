<?php
namespace Src\Identity\Application\Command\RegisterUser;

use Illuminate\Support\Facades\Hash;
use Src\Identity\Domain\Exceptions\EmailAlreadyExistsException;
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
            throw new EmailAlreadyExistsException($command->Email);
        }

        $user = User::register(
            id: UserId::fromString($uuidGenerator->generate),
            fullname: $command->name,
            email: $command->email,
            password: Hash::make(command->password),
        );

        $$this->repository->save($user);

        foreach ($user->releaseEvents() as $event){
            event($event);
        }
    }
}