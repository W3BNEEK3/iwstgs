<?php
namespace Src\Simulation\Domain\Scenario;

use Src\Shared\Domain\ValueObject;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class ScenarioTemplateId extends ValueObject
{
    public function __construct(private readonly string $uuid) {}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) UuidGenerator::generate());
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
