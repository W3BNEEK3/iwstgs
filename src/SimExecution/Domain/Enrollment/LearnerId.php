<?php
namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\ValueObject;
use Src\Domain\Infrastructure\Id\UuidGenerator;

final class LearnerId extends ValueObject
{
    public function __construct(private readonly string $uuid){}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) UuidGenerator::generate());
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->uuid === $other->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}