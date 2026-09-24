<?php

namespace Src\Organizations\Domain\Organization;

use Src\Shared\Domain\Entity;

final class Organization extends Entity
{
    public function __construct(
        private readonly string $id,
        private string          $name,
        private string          $slug,
        private bool            $isActive,
    ) {}

    public function id(): string      { return $this->id; }
    public function name(): string    { return $this->name; }
    public function slug(): string    { return $this->slug; }
    public function isActive(): bool  { return $this->isActive; }
}
