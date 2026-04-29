<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
   

    /* call() is a laravel method in DatabaseSeeder class that takes an array of seeders, */
    /* and runs them in the order they are listed. */
    public function run(): void
    {
        $this->call([
            FeatureFlagSeeder::class,
        ]);
    }
}
