<?php
namespace Src\Identity\Application\DTO;
// Data Transfer Object for representing user information
final class UserDTO
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $email,
        private readonly bool   $isLearner,
        private readonly array  $roles
    ) {}
}