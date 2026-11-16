<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data DEMO untuk pengembangan lokal. Semua akun berpassword: "password".
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Seeder demo tidak dijalankan di production.');

            return;
        }

        $this->call([
            UserSeeder::class,
            SiteContentSeeder::class,
            JobOpeningSeeder::class,
            DocumentTemplateSeeder::class,
            ApplicationSeeder::class,
            ActivityLogSeeder::class,
            DashboardStatsSeeder::class,
        ]);
    }
}
