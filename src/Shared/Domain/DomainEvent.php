<?php

namespace Src\Shared\Domain;

/**
 * Base class for domain events.
 *
 * A domain event represents something that happened in the domain — a fact.
 * Events are named in past tense: LearnerRegistered, TaskSubmitted, RankEscalated.
 * They carry the data that describes what happened and when it happened.
 *
 * Events are recorded on aggregate roots and dispatched AFTER the aggregate
 * is persisted, so listeners never run inside an uncommitted transaction.
 */
abstract class DomainEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
