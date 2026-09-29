<?php
namespace Src\Guidance\Application\Command\DeleteGuideContent;

final class DeleteGuideContentCommand
{
    public function __construct(
        public readonly string $type, // tip | resource
        public readonly string $id,
    ) {}
}
