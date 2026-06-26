<?php
namespace Src\Shared\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Src\Shared\Infrastructure\Modules\{
    AImediationModule, 
    IdentityModule,
    };

class ModulesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        forEach($this->modules() as $module){
            Route::middleware('web')
                ->prefix($module->prefix() ?? '')
                ->name($module->name() . '.' ?? '')
                ->group(base_path($module->routePath()) ?? 'routes/error/404.php');
        }
    }
    
    public function modules()
    {
        return [
           new AImediationModule(),
           new IdentityModule(),
        ];
    }
}