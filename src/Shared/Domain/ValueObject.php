<?php

namespace Src\Shared\Domain;

/**
 * Base class for Value Objects.
 *
 * Value objects have no identity — they are defined entirely by their value.
 * Two value objects containing the same value are equal regardless of reference.
 * Value objects must be immutable: once constructed, they never change.
 *
 * Example: LearnerId('abc-123') === LearnerId('abc-123')
 */
abstract class ValueObject
{
    abstract public function value(): mixed;

    abstract public function equals(self $other): bool;
}
