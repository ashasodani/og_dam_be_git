<?php
namespace Database\Seeders;

use App\Models\Portals;
use Illuminate\Database\Seeder;

class PortalsTableSeeder extends Seeder
{
    public function run(): void
    {
        $portals = [
            [
                'name'            => 'Client Access',
                'slug'            => 'client-access',
                'privacy'         => 'private',
                'link'            => 'https://www.dropbox.com',
                'description'     => 'Portal for client resource access.',
                'thumbnail_image' => null,
                'url'             => null,
            ]
        ];

        foreach ($portals as $portal) {
            Portals::create($portal);
        }
    }
}
