<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(UsersTableSeeder::class);
        $this->call(CurrenciesTableSeeder::class);
        $this->call(LanguagesTableSeeder::class);
        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsTableSeeder::class);
        $this->call(SystemSettingsTableSeeder::class);
        $this->call(MediaManagerTableSeeder::class);
        $this->call(PageTableSeeder::class);
        $this->call(PageLocalizationTableSeeder::class);
        $this->call(OpenAiModelTableSeeder::class);
        $this->call(AiChatCategoryTableSeeder::class);
        $this->call(FeatureCategorySeeder::class);
        $this->call(AiApplicationSeeder::class);
        $this->call(AiImageGenerateSeeder::class);
        $this->call(FeatureToolSeeder::class);
    }
}
