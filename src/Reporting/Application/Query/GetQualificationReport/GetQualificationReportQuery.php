<?php
namespace Src\Reporting\Application\Query\GetQualificationReport;

final class GetQualificationReportQuery
{
    public function __construct(public readonly ?string $userId) {}
}
