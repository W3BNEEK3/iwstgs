<?php

namespace Src\LearnerProfile\Domain\Profile;

use Illuminate\Support\Str;
use Src\Shared\Domain\ValueObject;

final class LearnerProfileId extends ValueObject
{
    public function __construct(private readonly string $uuid) {}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public function value(): string
    {
        return $this->uuid;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->uuid === $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
