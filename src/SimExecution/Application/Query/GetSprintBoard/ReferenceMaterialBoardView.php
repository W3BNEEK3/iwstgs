<?php
namespace Src\SimExecution\Application\Query\GetSprintBoard;

final class ReferenceMaterialBoardView
{
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly string $content,
    ) {}
}
