<?php
namespace Src\SourceControl\Application\Query\GetLinkedRepository;

final class GetLinkedRepositoryQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
    ) {}
}
