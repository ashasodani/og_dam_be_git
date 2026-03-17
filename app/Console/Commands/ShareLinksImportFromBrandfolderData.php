<?php
namespace App\Console\Commands;

use App\Models\Assets;
use App\Models\ShareLinks;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ShareLinksImportFromBrandfolderData extends Command
{
    protected $signature   = 'brandfolder:import-sharelinks';
    protected $description = 'Import Share Links from BrandFolder and attach to assets using digest key';

    public function handle()
    {
        $this->info('Importing Share Links from BrandFolder...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');
        $url      = "https://brandfolder.com/api/v4/brandfolders/{$slug}/share_manifests";

        $workspaceId = Workspaces::where('slug', 'degeest-corporation')->value('id');
        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }

        $page     = 1;
        $perPage  = 500;
        $imported = 0;

        do {
            $response = Http::withToken($apiToken)->get($url, [
                'queue_priority' => 'high',
                'order'          => 'desc',
                'page'           => $page,
                'per'            => $perPage,
                'search'         => '',
                'sort_by'        => 'created_at',
            ]);

            if (! $response->successful()) {
                $this->error("Error fetching page $page: " . $response->body());
                break;
            }

            $data = $response->json('data') ?? [];
            if (empty($data)) {
                break;
            }

            foreach ($data as $item) {
                $attr   = $item['attributes'] ?? [];
                $digest = $attr['digest'] ?? null;

                $assetId = null;

                if ($digest) {
                    $assetResponse = Http::withToken($apiToken)->get("https://brandfolder.com/api/v4/sections/s4wn3thsrftcwb2thjhhm7v/assets", [
                        'digest'         => $digest,
                        'fast_jsonapi'   => true,
                        'fields'         => 'asset_data,availability,extension,position,thumbnail_url,type',
                        'include'        => 'task',
                        'localUTC'       => now()->timestamp,
                        'order'          => 'DESC',
                        'per'            => 500,
                        'sort_by'        => 'created_at',
                        'strict_search'  => false,
                        'ugt_locale'     => 'en',
                        'queue_priority' => 'high',
                    ]);

                    if ($assetResponse->successful()) {
                        $assets = $assetResponse->json('data') ?? [];

                        foreach ($assets as $bfAsset) {
                            $bfAssetId = $bfAsset['id'];
                            $asset     = Assets::where('asset_key', $bfAssetId)->first();
                            if ($asset) {
                                $assetId = $asset->id;
                                break;
                            }
                        }
                    } else {
                        $this->warn("Failed to fetch assets using digest: {$digest}");
                    }
                }

                // Create or update share link
                $shareLink = ShareLinks::updateOrCreate(
                    ['url' => $attr['link']],
                    [
                        'name'             => $attr['name'] ?? null,
                        'url'              => $attr['link'] ?? null,
                        'is_private'       => false,
                        'is_email_address' => $attr['notifications_optin'] ?? false,
                        'email'            => null,
                        'is_password'      => false,
                        's_password'       => null,
                        'is_expired'       => false,
                        'expiry_date'      => null,
                        'timezone'         => null,
                        'create_by'        => is_numeric($attr['created_by']) ? $attr['created_by'] : null,
                        'view_count'       => false,
                        'status'           => '0',
                        'context_type'     => 'brand-folder',
                        'is_notify'        => $attr['notifications_optin'] ?? false,
                    ]
                );

                // Attach asset using pivot table
                if ($assetId) {
                    $shareLink->assets()->syncWithoutDetaching([$assetId]);
                    $this->info("Imported ShareLink: {$shareLink->name} → Attached to Asset ID: {$assetId}");
                } else {
                    $this->info("Imported ShareLink: {$shareLink->name} → No matching asset found");
                }

                $imported++;
            }

            $page++;
        } while (count($data) === $perPage);

        $this->info("Share Links Imported successfully.");
    }
}
