<?php

namespace App\Console\Commands;

use App\Models\Collections;
use App\Models\Assets;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class CollectionsImportFromBrandFolderData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brandfolder:import-collections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Collections data from Brandfolder into local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching Brandfolder collection data...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');

        $baseUrl = "https://brandfolder.com/api/v4/brandfolders/{$slug}/collections";

        $workspaceId = Workspaces::where('slug', 'degeest')->value('id');
        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }

        $page          = 1;
        $perPage       = 25;
        $totalImported = 0;

        do {
            $response = Http::withToken($apiToken)
                ->get($baseUrl, [
                    'page'     => $page,
                    'per_page' => $perPage,
                ]);

            if (! $response->successful()) {
                $this->error('Failed to fetch page ' . $page . ': ' . $response->body());
                break;
            }

            $data     = $response->json('data');
            $meta     = $response->json('meta') ?? [];
            $lastPage = $meta['last_page'] ?? $page;
         
            foreach ($data as $item) {
                $attributes = $item['attributes'];
                $name       = $attributes['name'] ?? null;

                if (! $name) {
                    $this->warn('Skipped a collection due to missing name.');
                    continue;
                }

                // Determine privacy
                $privacy = 'public';
                if (! ($attributes['public'] ?? true)) {
                    $privacy = ($attributes['stealth'] ?? false) ? 'stealth' : 'private';
                }

                // Match by collection name instead of id
                $collection = Collections::firstOrCreate(
                    ['name' => $name],
                    [
                        'slug'    => $attributes['slug'] ?? null,
                        'privacy' => $privacy,
                    ]
                );
               // $assetCollection = "https://brandfolder.com/api/v4/collections/{$item['id']}/assets";
                 $assetCollection = "https://brandfolder.com/api/v4/collections/{$item['id']}/assets";

                $responseCollection = Http::withOptions([
                    'timeout' => 600,              // total request timeout (body + headers)
                    'connect_timeout' => 60,       // time allowed for DNS resolution and TCP connection
                ])->withToken($apiToken)->get($assetCollection, [
                    'per'            => 3000,
                    'queue_priority' => 'high',
                ]);
                if (! $responseCollection->successful()) {
                    $this->error("Failed to fetch assets for asset ID: {$item['id']}");
                    continue;
                }
                $dataAsset = $responseCollection->json('data') ?? [];
            //    dd($dataAsset);
                $assetIds = [];
                $assetIds = collect($dataAsset)
                    ->flatMap(function ($data) {
                        return Assets::where('asset_key', $data['id'])->orWhere('replicate_key', $data['id'])->pluck('id');
                    })
                    ->filter()  // removes nulls if any keys not found
                    ->values()  // reindex keys
                    ->toArray();

                   // dd($assetIds);
                $collection->assets()->syncWithoutDetaching($assetIds);
                // dd($assetIds);
                //sync asset ids to collections end
                // Attach to workspace
                $collection->workspaces()->syncWithoutDetaching([$workspaceId]);

                $this->info("Imported: {$collection->name}");
                $totalImported++;
            }

            $page++;
        } while ($page <= $lastPage);

        $this->info("All collections have been imported successfully.");
    }
}
