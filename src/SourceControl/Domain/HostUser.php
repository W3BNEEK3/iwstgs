<?php
namespace Src\SourceControl\Domain;

final class HostUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $login,
    ) {}
}
