<?php
namespace Src\SimExecution\Application\Query\GetSprintBoard;

final class VaultItemBoardView
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $documentType,
    ) {}
}
