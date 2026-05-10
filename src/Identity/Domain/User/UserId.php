<?php
namespace Src\Identity\Domain\User;

use Src\Shared\Domain\ValueObject;
use Illuminate\Support\Str;

final class UserId extends ValueObject
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
    
    public function _toString(): string 
    {
        return $this->uuid;
    }
}