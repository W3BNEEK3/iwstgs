<?php

namespace Src\Shared\Infrastructure\Clock;

/**
 * Provides the current system time.
 *
 * Wrapping time in a service makes it injectable and replaceable in tests.
 * Without this, code that calls new \DateTimeImmutable() or now() directly
 * cannot be tested with a fixed timestamp — tests become time-dependent and
 * brittle. Inject SystemClock; in tests, inject a FakeClock.
 */
class SystemClock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function nowAsString(): string
    {
        return now()->toDateTimeString();
    }
}
