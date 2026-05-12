<?php

namespace Src\Shared\Domain;

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
