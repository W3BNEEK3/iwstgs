<?php

namespace Src\Shared\Application\Bus;

/**
 * Contract for the command bus.
 *
 * A command represents an intention to change state — "do this thing."
 * Commands have exactly one handler. The bus decouples the dispatcher
 * (controller) from the handler, so the controller never imports the handler
 * class directly. This is the Command side of CQRS-lite.
 */
interface CommandBus
{
    public function dispatch(object $command): void;
}
