<?php
namespace App\Console\Commands;

use App\Jobs\UploadThumbToS3;
use App\Jobs\UploadHeaderToS3;

use App\Models\AddtionalLinks;
use App\Models\Portals;
use App\Models\Tiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportPortalsFromBrandfolder extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'brandfolder:import-portals';

    /**
     * The console command description.
     */
    protected $description = 'Import portals (and their tiles & links) from Brandfolder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching Brandfolder portal data...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');

        $url = "https://brandfolder.com/api/v4/organizations?include=portals";

        $response = Http::withToken($apiToken)->get($url);

        if (! $response->successful()) {
            $this->error('Failed to fetch portal data: ' . $response->body());
            return;
        }

        $data     = $response->json('data')[0] ?? [];
        $included = collect($response->json('included') ?? []);

        $portalIds = $data['relationships']['portals']['data'] ?? [];

        foreach ($portalIds as $portalMeta) {
            $portalId = $portalMeta['id'];

            // Find portal info in included
            $includedPortal = $included->firstWhere('id', $portalId);
            if (! $includedPortal) {
                $this->warn("Portal with ID {$portalId} not found in included array.");
                continue;
            }

            $attributes = $includedPortal['attributes'];

            $portal = Portals::firstOrCreate(
                ['slug' => $attributes['slug']],
                [
                    'name'    => $attributes['name'] ?? null,
                    'privacy' => $attributes['privacy'] ?? 'public',
                ]
            );
            $this->info("Imported portal: {$portal->name}");

            $portalPath = config('deegest.portal_document.portal_file_path');

            // Upload Portal Thumbnails
            if($attributes['slug'] === 'lestausa'){
                $attributes['url'] = 'https://cdn.fs.brandfolder.com/N8RP3EKVRWiKMyFQ0RoP?policy=eyJjYWxsIjpbInJlYWQiXSwiZXhwaXJ5IjoxNzUzMzM2NzI5LCJoYW5kbGUiOiJOOFJQM0VLVlJXaUtNeUZRMFJvUCJ9&signature=728c5b1d6afd9d73c886a67a3f410b0dffc082785514df05bc420f709193ca2d';
                $attributes['header_url'] = 'https://cdn.fs.brandfolder.com/N8RP3EKVRWiKMyFQ0RoP?policy=eyJjYWxsIjpbInJlYWQiXSwiZXhwaXJ5IjoxNzUzMzM2NzI5LCJoYW5kbGUiOiJOOFJQM0VLVlJXaUtNeUZRMFJvUCJ9&signature=728c5b1d6afd9d73c886a67a3f410b0dffc082785514df05bc420f709193ca2d';
            }
            if($attributes['slug'] === 'degeest_employee_portal'){
                $attributes['url'] = 'https://cdn.fs.brandfolder.com/TgtoBzqvQD2UeofqThG8?policy=eyJjYWxsIjpbInJlYWQiXSwiZXhwaXJ5IjoxNzUzMzM2NzI5LCJoYW5kbGUiOiJUZ3RvQnpxdlFEMlVlb2ZxVGhHOCJ9&signature=8a374dab71ce2e789686f45283b8396c6f1a0183c7f6dcb1b4c3da0bf7d5ae22';
                $attributes['header_url'] = 'https://cdn.fs.brandfolder.com/TgtoBzqvQD2UeofqThG8?policy=eyJjYWxsIjpbInJlYWQiXSwiZXhwaXJ5IjoxNzUzMzQ2MzY3LCJoYW5kbGUiOiJUZ3RvQnpxdlFEMlVlb2ZxVGhHOCJ9&signature=30ded685b258b0652ea4d7f364789806070ebca2187d0ebbdc2a218fca047a34';
            }

            if (! empty($attributes['url'])) {
                Log::info("Portal URL Job Dispatch: {$attributes['url']} (ID: {$portal->id})");

                $attr['thumbnail_url'] = $attributes['url'];
                UploadThumbToS3::dispatch($attr, $portal->id, $portalPath, 'portals', 130, 260, 'url');
            }
            if (! empty($attributes['header_url'])) {
                Log::info("Portal HEADER Job Dispatch: {$attributes['header_url']} (ID: {$portal->id})");

                $attr['thumbnail_url'] = $attributes['header_url'];
                UploadHeaderToS3::dispatch($attr, $portal->id, $portalPath, 'portals', 130, 260, 'url')->onQueue('header');
            }

            // === Import Tiles and Additional Links ===
            $this->importPortalCards($portalId, $portal->id, $apiToken);
        }

        $this->info("All portals, tiles, and additional links have been imported.");
    }

    /**
     * Import tiles and additional links for a specific portal.
     *
     * @param string $externalPortalId Brandfolder ID from API
     * @param int    $localPortalId    Local DB portal ID
     * @param string $apiToken
     */
    protected function importPortalCards(string $externalPortalId, int $localPortalId, string $apiToken): void
    {
        $url = "https://brandfolder.com/api/v4/private/portals/{$externalPortalId}/portal_cards?queue_priority=high";

        $response = Http::withToken($apiToken)->get($url);

        if (! $response->successful()) {
            $this->error("Failed to fetch portal cards for portal ID {$externalPortalId}");
            return;
        }

        $cards = $response->json('data') ?? [];

        foreach ($cards as $card) {
            $type       = $card['type'];
            $attributes = $card['attributes'] ?? [];
            if ($type === 'portal_tiles') {
              //  dd($attributes);
                $oldUrl = $attributes['link_url'];
                $qValue = $newUrl = str_replace('https://brandfolder.com/', env('APP_FE_URL'), $oldUrl);
                $tile = Tiles::firstOrCreate(
                    [
                        'portal_id' => $localPortalId,
                        'name'      => $attributes['name'],
                    ],
                    [
                        'tile_type'   => $attributes['sub_type'] ?? null,
                        'position'    => $attributes['position'] ?? 0,
                        'link_url'    => $qValue ?? $attributes['link_url'],
                        'tile_image'  => $attributes['image_url'] ?? null,
                        'description' => $attributes['description'] ?? null,
                        'grid_size'   => match ($attributes['display_width'] ?? '') {
                            'two-thirds'  => '2/3',
                            'one-third'   => '1/3',
                            'full'        => '1',
                            default       => null,
                        },
                        'tile_url'    => $attributes['image_url'] ?? null,
                    ]
                );

                if (! empty($attributes['image_url'])) {
                    $height = 130;
                    $width  = 260;

                    $tilePath              = config('deegest.tile_document.tile_file_path');
                    $attr['thumbnail_url'] = $attributes['image_url'];
                   // UploadThumbToS3::dispatch($attr, $tile->id, $tilePath, $table = 'tiles', $height, $width);
                    UploadHeaderToS3::dispatch($attr, $tile->id, $tilePath, 'tiles', 130, 260, 'url')->onQueue('header');
                }

                $this->info("Tile imported: {$attributes['name']}");
            }

            if ($type === 'portal_links') {
                $link = AddtionalLinks::firstOrCreate(
                    [
                        'portal_id' => $localPortalId,
                        'name'      => $attributes['name'],
                    ],
                    [
                        'link_url'  => $attributes['link_url'] ?? null,
                        'link_icon' => $attributes['icon_url'] ?? null,
                        'position'  => $attributes['position'] ?? 0,
                    ]
                );

                if (! empty($attributes['icon_url'])) {
                    $height   = 40;
                    $width    = 40;
                    $linkPath = config('deegest.external_link_document.external_link_file_path');

                    $attr['thumbnail_url'] = $attributes['icon_url'];
                    //UploadThumbToS3::dispatch($attr, $link->id, $linkPath, $table = 'additional_links', $height, $width);
                     UploadHeaderToS3::dispatch($attr, $link->id, $linkPath, 'additional_links', 130, 260, 'url')->onQueue('header');
                }

                $this->info("Link imported: {$attributes['name']}");
            }
        }
    }
}
