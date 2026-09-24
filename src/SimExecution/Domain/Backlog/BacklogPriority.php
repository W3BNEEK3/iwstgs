<?php
namespace Src\SimExecution\Domain\Backlog;

enum BacklogPriority: string
{
    case MustHave   = 'must_have';
    case ShouldHave = 'should_have';
    case CouldHave  = 'could_have';
    case WontHave   = 'wont_have';
}
