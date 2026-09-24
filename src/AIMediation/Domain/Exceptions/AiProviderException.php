<?php
namespace Src\AIMediation\Domain\Exceptions;

use Src\Shared\Domain\DomainException;

final class AiProviderException extends DomainException
{
    public function __construct(string $provider, string $reason)
    {
        parent::__construct("{$provider} API error: {$reason}");
    }
}
