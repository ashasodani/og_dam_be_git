<?php

namespace App\Console\Commands;

use App\Models\Assets;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DescriptionImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asset:import-description';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import description of asset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching Brandfolder atachment data...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');


        $workspaceId = Workspaces::where('slug', 'degeest')->value('id');
        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }


        $page    = 1;
        $perPage = 25;

        do {

            $lastPage = $meta['last_page'] ?? $page;
            $assetKeys = Assets::whereHas('workspaces', function ($query) use ($slug) {
                $query->where('slug', 'degeest');
            })->select('id', 'asset_key')->get()->toArray();
            // dd($assetKeys);
            foreach ($assetKeys as $asset) {
                //$attachmentUrl = "https://brandfolder.com/api/v4/assets/fsngmgk6q7h68pjqc7nnrmr?include=attachments,tags,labels";
                $attachmentUrl = "https://brandfolder.com/api/v4/assets/{$asset['asset_key']}";

                $response = Http::withOptions([
                    'timeout' => 600,              // total request timeout (body + headers)
                    'connect_timeout' => 60,       // time allowed for DNS resolution and TCP connection
                ])->withToken($apiToken)->get($attachmentUrl, [
                    'include' => 'attachments,tags,labels',
                ]);
                if (! $response->successful()) {
                    $this->error("Failed to fetch assets for asset ID: {$asset['asset_key']}");
                    continue;
                }
                $dataTab = $response->json('data') ?? [];

                $attr = $dataTab['attributes'];
                //dd($attr['description']);

                $currentAsset = Assets::find($asset['id']);
                $currentAsset->description = $attr['description'];
                $currentAsset->save();

                //}
                $this->info("Imported attachment and assets: " . $asset['asset_key'] . "::" . $asset['id']);
            }

            $page++;
        } while ($page <= $lastPage);

        $this->info('All description and their assets have been imported successfully.');
    }
}
