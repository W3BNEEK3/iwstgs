<?php

namespace Src\Shared\Application\Bus;

use Illuminate\Contracts\Container\Container;

/**
 * Synchronous implementation of CommandBus.
 *
 * Resolves the handler class from the command class name by convention:
 *   RegisterLearnerCommand → RegisterLearnerHandler
 *
 * The handler is resolved from the Laravel container, which means any
 * dependencies the handler needs (repositories, services) are injected
 * automatically. You never instantiate handlers manually.
 *
 * Why synchronous? For the MVP, all commands are handled in-request.
 * A queue-backed bus can be swapped in later without touching any caller.
 */
class SynchronousCommandBus implements CommandBus
{
    public function __construct(private readonly Container $container) {}

    public function dispatch(object $command): void
    {
        $handlerClass = str_replace('Command', 'Handler', get_class($command));
        $handler = $this->container->make($handlerClass);
        $handler->handle($command);
    }
}
