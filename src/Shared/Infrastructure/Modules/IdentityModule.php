<?php
namespace Src\Shared\Infrastructure\Modules;

use Src\Shared\Domain\Contract\Module;

class IdentityModule implements Module
{
    public function prefix(): string
    {
        return 'identity';
    }

    public function name(): string
    {
        return 'identity';
    }

    public function routePath(): string
    {
        return 'routes/identity.php';
    }
}
