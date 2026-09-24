<?php
namespace Src\Shared\Infrastructure\Modules;

use Src\Shared\Domain\Contract\Module;

class AImediationModule implements Module
{
    public function prefix(): string
    {
        return 'aimediation';
    }
    
    public function name(): string
    {
        return 'aimediation';
    }
    
    public function routePath(): string
    {
        return 'routes/ai_mediation.php';
    }
}