<?php

namespace App\Console\Commands;

use App\Models\Tags;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TagsImportFromBrandFolderData extends Command
{
    protected $signature   = 'brandfolder:import-tags';
    protected $description = 'Import global and collection-wise Tags from Brandfolder into local database';

    public function handle()
    {
        $this->info('Starting Brandfolder tags import...');

        $slug        = config('services.brandfolder.slug');
        $apiToken    = config('services.brandfolder.token');
        $workspaceId = Workspaces::where('slug', 'degeest')->value('id');

        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }

        $totalImported = 0;

        // ---------------------------
        // 1. Import global tags
        // ---------------------------
        $this->info('Importing global tags...');
        $this->importTagsFromUrl("https://brandfolder.com/api/v4/brandfolders/{$slug}/tags", $apiToken, $workspaceId, $totalImported);

        // ---------------------------
        // 2. Import tags collection-wise
        // ---------------------------
        $this->info('Fetching collections to import collection-wise tags...');
        $collectionsResponse = Http::withOptions([
            'timeout' => 600,              // total request timeout (body + headers)
            'connect_timeout' => 600,       // time allowed for DNS resolution and TCP connection
        ])->withToken($apiToken)->get("https://brandfolder.com/api/v4/brandfolders/{$slug}/collections");

        if (! $collectionsResponse->successful()) {
            $this->error("Failed to fetch collections: " . $collectionsResponse->body());
            return;
        }

        $collections = $collectionsResponse->json('data') ?? [];

        foreach ($collections as $collection) {
            $collectionId   = $collection['id'];
            $collectionName = $collection['attributes']['name'] ?? 'Unnamed Collection';

            $this->info("Importing tags for collection: {$collectionName} ({$collectionId})");

            $this->importTagsFromUrl("https://brandfolder.com/api/v4/collections/{$collectionId}/tags", $apiToken, $workspaceId, $totalImported);
        }

        $this->info("Total tags imported or updated: {$totalImported}");
    }

    /**
     * Import tags from a given URL and attach them to the workspace.
     */
    private function importTagsFromUrl(string $url, string $apiToken, int $workspaceId, int &$totalImported)
    {
        $page    = 1;
        $perPage = 25;

        do {
            $response = Http::withToken($apiToken)->get($url, [
                'page'     => $page,
                'per_page' => $perPage,
            ]);

            if (! $response->successful()) {
                $this->error("Failed to fetch tags from {$url} (page {$page}): " . $response->body());
                break;
            }

            $data     = $response->json('data') ?? [];
            $meta     = $response->json('meta') ?? [];
            $lastPage = $meta['last_page'] ?? $page;

            foreach ($data as $item) {
                $attributes = $item['attributes'];

                $tag = Tags::firstOrCreate(
                    ['name' => $attributes['name']],
                    ['auto_generated' => $attributes['auto_generated'] ?? false]
                );

                // Attach tag to workspace without duplication
                $tag->workspaces()->syncWithoutDetaching([$workspaceId]);

                $this->info("Imported tag: " . $attributes['name']);
                $totalImported++;
            }

            $page++;
        } while ($page <= $lastPage);
    }
}
