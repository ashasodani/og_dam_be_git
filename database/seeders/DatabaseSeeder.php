<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndUserSeeder::class);
        //$this->call(PortalsTableSeeder::class);
        $this->call(AssetExtensionSeeder::class);
       // $this->call(WorkspacesTableSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(AssetExtensionSeeder::class);
        $this->call(CountriesSeeder::class);
    }
}
