<?php

namespace Src\Shared\Application\Bus;

/**
 * Contract for the query bus.
 *
 * A query represents a request to read data without changing state.
 * Queries return a result; commands do not. This is the Query side of CQRS-lite.
 * Keeping reads separate from writes makes both easier to optimise independently.
 */
interface QueryBus
{
    public function ask(object $query): mixed;
}
