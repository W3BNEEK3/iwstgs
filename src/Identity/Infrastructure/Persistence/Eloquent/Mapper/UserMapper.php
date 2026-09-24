<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserId;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;

final class UserMapper
{
    public function toEntity(UserModel $model): User
    {
        return User::reconstitute(
            id:           UserId::fromString($model->id),
            fullname:         $model->name,
            email:        $model->email,
            passwordHash: $model->password,
        );
    }

    public function toModel(User $entity): UserModel
    {
        return new UserModel([
            'id'       => (string) $entity->userId(),
            'name'     => $entity->fullname(),
            'email'    => $entity->email(),
            'password' => $entity->passwordHash(),
        ]);
    }
}