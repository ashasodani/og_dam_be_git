<?php
namespace Database\Seeders;

use App\Models\Sections;
use App\Models\Workspaces;
use Illuminate\Database\Seeder;

class WorkspacesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Create 4 shared sections
        $filesSection1 = Sections::create([
            'name'               => 'Files Section 1',
            'default_asset_type' => 'files',
            'position'           => 1,
            'asset_type'         => 'files',
        ]);

        $filesSection2 = Sections::create([
            'name'               => 'Files Section 2',
            'default_asset_type' => 'files',
            'position'           => 2,
            'asset_type'         => 'files',
        ]);

        $colorsSection = Sections::create([
            'name'               => 'Colors Section',
            'default_asset_type' => 'colors',
            'position'           => 3,
            'asset_type'         => 'colors',
        ]);

        $pressSection = Sections::create([
            'name'               => 'Press Section',
            'default_asset_type' => 'press/links',
            'position'           => 4,
            'asset_type'         => 'press/links',
        ]);

        // Create workspaces and attach existing sections
        $workspaces = [
            [
                'name'            => 'Design Hub',
                'slug'            => 'design-hub',
                'privacy'         => 'public',
                'description'     => 'Workspace for design-related assets.',
                'thumbnail_image' => null,
                'url'             => null,
            ]
        ];

        foreach ($workspaces as $workspaceData) {
            $workspace = Workspaces::create($workspaceData);

            // Attach the same 4 sections to each workspace
            $workspace->sections()->attach([
                $filesSection1->id,
                $filesSection2->id,
                $colorsSection->id,
                $pressSection->id,
            ]);
        }
    }
}
