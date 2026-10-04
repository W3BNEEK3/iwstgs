<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tiroco only recommends outside resources whose links still work (design doc v2-05 §5).
Artisan::command('guide:check-resources', function (\Src\Shared\Application\Bus\CommandBus $bus) {
    $bus->dispatch(new \Src\Guidance\Application\Command\CheckGuideResourceLinks\CheckGuideResourceLinksCommand());
    $this->info('Resource links checked. Broken ones are flagged in Admin → Guide → Resources.');
})->purpose('Check that the guide\'s recommended resource links still work');

\Illuminate\Support\Facades\Schedule::command('guide:check-resources')->weekly();

// Milestone submissions wait for their commit's test run; this catches any whose webhook never arrived
// (and evaluates without tests once the wait is over). Design doc v2-01 §2.2.
Artisan::command('submissions:resume-pending', function (\Src\Shared\Application\Bus\CommandBus $bus) {
    $bus->dispatch(new \Src\Submission\Application\Command\ResumePendingSubmissions\ResumePendingSubmissionsCommand());
    $this->info('Pending milestone submissions checked.');
})->purpose('Finish milestone submissions whose CI result has arrived or timed out');

\Illuminate\Support\Facades\Schedule::command('submissions:resume-pending')->everyFiveMinutes()->withoutOverlapping();
