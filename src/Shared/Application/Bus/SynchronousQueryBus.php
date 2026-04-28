<?php

namespace Src\Shared\Application\Bus;

use Illuminate\Contracts\Container\Container;

/**
 * Synchronous implementation of QueryBus.
 *
 * Resolves the handler class from the query class name by convention:
 *   GetLearnerProfileQuery → GetLearnerProfileHandler
 *
 * Query handlers return data (read-only). They must never mutate state.
 */
class SynchronousQueryBus implements QueryBus
{
    public function __construct(private readonly Container $container) {}

    public function ask(object $query): mixed
    {
        $handlerClass = str_replace('Query', 'Handler', get_class($query));
        $handler = $this->container->make($handlerClass);
        return $handler->handle($query);
    }
}
