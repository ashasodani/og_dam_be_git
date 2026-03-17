<?php
namespace App\Console\Commands;

use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\UploadThumbToS3;

class InsertDegeestWorkspace extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'workspace:insert-degeest';

    /**
     * The console command description.
     */
    protected $description = 'Insert DeGeest Corporation workspace into the database and trigger Brandfolder imports';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Inserting DeGeest Corporation workspace...');

        $workspace = Workspaces::firstOrCreate(
            ['slug' => 'degeest'],
            [
                'name'            => 'DeGeest Corporation',
                'privacy'         => 'public',
                'description'     => 'DeGeest Corporation description',
                'thumbnail_image' => 'https://thumbs.bfldr.com/at/st76pjqj26qkffnzvb8v8ppw/v/1122818504?expiry=1753277564&fit=bounds&height=400&sig=NDFlMmQ2NDA0YmUxMWM3YWQ4NTEyMjhlOTUzN2I2MGEyYzIzMDc1OA%3D%3D&width=550',
                'url'             => null,
            ]
        );
        $path = config('deegest.workspace_document.workspace_file_path');
        $attr['thumbnail_url']= 'https://thumbs.bfldr.com/at/st76pjqj26qkffnzvb8v8ppw/v/1122818504?expiry=1754491042&fit=bounds&height=400&sig=MWZmOWYxMGRkZjE5OGU4YjU3YWJhMTMzMDI0ZTVhM2ExMWVhYzg5Yw%3D%3D&width=550';
        $height=195;$width=390;
        UploadThumbToS3::dispatch($attr, $workspace->id,$path,$table = 'workspaces',$height,$width);

        $this->info("Workspace inserted/updated: {$workspace->name}");

        $this->info('Starting Brandfolder imports...');

        $commands = [
           // 'brandfolder:import-sections',
            // 'asset:import-description',
            // 'attachment:import-asset-attachment',
            // 'brandfolder:import-collections',
            // 'brandfolder:import-labels',
            // 'brandfolder:import-tags',
            // 'brandfolder:import-portals',
        ];

        foreach ($commands as $command) {
            $this->info("Running command: $command");
            Artisan::call($command);
            $this->info(Artisan::output());
        }

        $this->info('All Brandfolder data has been imported successfully.');
    }
}
