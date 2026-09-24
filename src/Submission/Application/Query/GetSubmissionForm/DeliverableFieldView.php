<?php
namespace Src\Submission\Application\Query\GetSubmissionForm;

final class DeliverableFieldView
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $label,
        public readonly ?string $description,
        public readonly bool $isRequired,
    ) {}
}
