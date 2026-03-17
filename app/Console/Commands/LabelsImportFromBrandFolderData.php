<?php
namespace App\Console\Commands;

use App\Models\Labels;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LabelsImportFromBrandFolderData extends Command
{
    protected $signature   = 'brandfolder:import-labels';
    protected $description = 'Import labels from Brandfolder into local database without changing schema';

    public function handle()
    {
        $this->info('Fetching Brandfolder labels...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');
        $baseUrl  = "https://brandfolder.com/api/v4/brandfolders/{$slug}/labels";

        $workspaceId = Workspaces::where('slug', 'degeest-corporation')->value('id');
        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }

        $page          = 1;
        $perPage       = 25;
        $totalImported = 0;
        $bfMap         = []; // brandfolder_id => local_id
        $rawLabels     = [];

        do {
            $response = Http::withToken($apiToken)->get($baseUrl, [
                'page'     => $page,
                'per_page' => $perPage,
            ]);

            if (! $response->successful()) {
                $this->error("Failed to fetch page $page: " . $response->body());
                break;
            }

            $data     = $response->json('data') ?? [];
            $meta     = $response->json('meta') ?? [];
            $lastPage = $meta['last_page'] ?? $page;

            $rawLabels = array_merge($rawLabels, $data);
            $this->info("Fetched page $page with " . count($data) . " labels");

            $page++;
        } while ($page <= $lastPage);

        // Step 1: Insert all labels (without parent_key), and map BF ID → local ID
        foreach ($rawLabels as $item) {
            $bfId       = $item['id'];
            $attributes = $item['attributes'];
            $name       = $attributes['name'] ?? null;

            // Use updateOrCreate to prevent duplicates by name
            $label = Labels::updateOrCreate(
                ['name' => $name],
                ['parent_key' => null]
            );

            // Attach to workspace
            $label?->workspaces()->syncWithoutDetaching([$workspaceId]);

            $bfMap[$bfId] = $label->id;
        }

        // Step 2: Set parent_key using in-memory mapping
        foreach ($rawLabels as $item) {
            $bfId       = $item['id'];
            $attributes = $item['attributes'];
            $path       = $attributes['path'] ?? [];
            $depth      = $attributes['depth'] ?? 1;

            if ($depth > 1 && count($path) >= 2) {
                $parentBfId = $path[count($path) - 2];

                if (isset($bfMap[$parentBfId], $bfMap[$bfId])) {
                    Labels::where('id', $bfMap[$bfId])->update([
                        'parent_key' => $bfMap[$parentBfId],
                    ]);
                }
            }
        }

        $this->info("All labels have been imported successfully.");
    }
}
