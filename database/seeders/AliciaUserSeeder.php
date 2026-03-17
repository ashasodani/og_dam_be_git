<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class AliciaUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
         $superAdminRole = Role::where('name','Super Admin')->first();
 
        

        $superadmins = User::firstOrCreate(
            ['email' => 'alicia@degeestmfg.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('alicia@degeest'),
            ]
        );
         $superadmins->assignRole($superAdminRole);
     
    }
}
