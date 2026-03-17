<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Create Roles
         $adminRole = Role::firstOrCreate(['name' => 'Admin']);
         $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
         $collaborator = Role::firstOrCreate(['name' => 'Collaborator']);
         $guest = Role::firstOrCreate(['name' => 'Guest']);
 
         // Create Admin User
         $admin = User::firstOrCreate(
             ['email' => 'admin@degeest.com'],
             [
                 'name' => 'Admin',
                 'password' => bcrypt('Admin@123'),
             ]
         );
         $admin->assignRole($adminRole);
 
         // Create Regular User
         $collaborators = User::firstOrCreate(
             ['email' => 'collaborator@degeest.com'],
             [
                 'name' => 'Collaborator',
                 'password' => bcrypt('Admin@123'),
             ]
         );
         $collaborators->assignRole($collaborator);

         $guests = User::firstOrCreate(
            ['email' => 'guest@degeest.com'],
            [
                'name' => 'Guest',
                'password' => bcrypt('Admin@123'),
            ]
        );
        $guests->assignRole($guest);

        $superadmins = User::firstOrCreate(
            ['email' => 'superadmins@degeest.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('Admin@123'),
            ]
        );
         $superadmins->assignRole($superAdminRole);
     
    }
}
