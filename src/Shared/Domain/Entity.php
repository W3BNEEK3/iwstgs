<?php

namespace Src\Shared\Domain;

/**
 * Base class for domain objects that have identity.
 *
 * Two entities with the same ID are the same entity, even if their
 * other properties differ. This is the core DDD distinction between
 * an Entity (identity-based equality) and a Value Object (value-based equality).
 */
abstract class Entity
{
    abstract public function id(): string;

    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->id() === $other->id();
    }
}
