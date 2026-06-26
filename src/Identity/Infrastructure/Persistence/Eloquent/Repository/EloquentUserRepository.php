<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Repository;

use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserId;
use Src\Identity\Domain\User\UserRepository;
use Src\Identity\Infrastructure\Persistence\Eloquent\Mapper\UserMapper;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(private readonly UserMapper $mapper) {}

    public function save(User $user): void
    {
        $model = $this->mapper->toModel($user);
        $model->save();
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::where('email', $email)->first();
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsByEmail(string $email): bool
    {
        return UserModel::where('email', $email)->exists();
    }
}