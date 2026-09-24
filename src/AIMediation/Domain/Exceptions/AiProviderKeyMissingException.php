<?php
namespace Src\AIMediation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class AiProviderKeyMissingException extends DomainException
{
    public function __construct(string $envVar)
    {
        parent::__construct("{$envVar} is not configured — evaluation cannot run");
    }
}
