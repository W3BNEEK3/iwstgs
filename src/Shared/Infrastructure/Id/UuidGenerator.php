<?php

namespace Src\Shared\Infrastructure\Id;

use Illuminate\Support\Str;

/**
 * Generates UUIDs for use as entity identifiers.
 *
 * Wrapping Str::uuid() in its own class lets us swap the generation
 * strategy (e.g. ordered UUIDs, ULID) without touching any caller.
 * It also makes UUID generation injectable and testable — tests can
 * provide a fake implementation that returns predictable IDs.
 */
class UuidGenerator
{
    public static function generate(): string
    {
        return (string) Str::uuid();
    }
}
