<?php

namespace Src\Shared\Domain;

/**
 * Base class for aggregate roots.
 *
 * An aggregate root is the single entry point to a cluster of related domain
 * objects. It is the only object external code may hold a reference to.
 * It collects domain events that occurred during its lifetime so those events
 * can be dispatched after the aggregate is persisted — keeping side-effects
 * outside the transaction boundary.
 */
abstract class AggregateRoot extends Entity
{
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /**
     * Returns all recorded events and clears the internal list.
     * Call this after saving the aggregate to dispatch events.
     *
     * @return DomainEvent[]
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
