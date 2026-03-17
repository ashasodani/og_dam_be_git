<?php

namespace App\Console\Commands;

use App\Models\AssetExtension;
use App\Models\Assets;
use App\Models\Sections;
use App\Models\Tags;
use App\Models\Workspaces;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Jobs\UploadThumbToS3;
use App\Jobs\UploadAssetToS3;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttachmentsImportReplicate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachment:import-asset-attachment-replicate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import attachment of asset';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching Brandfolder attachmentssss data...');

        $slug     = config('services.brandfolder.slug');
        $apiToken = config('services.brandfolder.token');

        $workspaceId = Workspaces::where('slug', 'degeest')->value('id');
        if (! $workspaceId) {
            $this->error('Workspace with slug "degeest" not found.');
            return;
        }

        $assetKeys = Assets::whereHas('workspaces', function ($query) use ($slug) {
            $query->where('slug', 'degeest');
            })->select('id', 'asset_key', 'url')->get();
          
        foreach ($assetKeys as $asset) {
           // if($asset->asset_key === 'vrs4j4rvssvb6kqcjfbtbg') {
                
                
                $attachmentUrl = "https://brandfolder.com/api/v4/assets/{$asset->asset_key}?include=attachments,tags,labels";

                $response = Http::withOptions([
                    'timeout' => 600,              // total request timeout (body + headers)
                    'connect_timeout' => 600,       // time allowed for DNS resolution and TCP connection
                ])->withToken($apiToken)->get($attachmentUrl);
                if (! $response->successful()) {
                    $this->error("Failed to fetch asset: {$asset->id}::{$asset->asset_key}");
                    continue;
                }
              
                $dataTab = $response->json('included') ?? [];
                $attachments = [];
                $tags = [];
                foreach ($dataTab as $item) {
                    if ($item['type'] === 'attachments') {
                        $attachments[] = $item;
                    } elseif ($item['type'] === 'tags') {
                        $tags[] = $item;
                    }
                }

                $sortedItems = array_merge($attachments,$tags);
                $assetModel = Assets::find($asset->id);
               

                foreach ($sortedItems as $index => $item) {
                   

                    $attr = $item['attributes'];
                    if ($item['type'] === 'tags') {
                        $tag = Tags::firstOrCreate(['name' => $attr['name']]);
                        $tag->workspaces()->syncWithoutDetaching($workspaceId);
                        $tag->assets()->syncWithoutDetaching($asset->id);
                        Assets::where('replicate_key', $asset->asset_key)
                        ->get()
                        ->each(function ($replicateModel) use ($tag) {
                            $replicateModel->tags()->syncWithoutDetaching($tag->id);
                        });
                        continue;
                    }

                    // attachment
                    if (count($sortedItems) === 1 || $index === 0) {
                        $this->saveMetadata($assetModel, $attr);
                        $this->dispatchUploadJobs($assetModel, $attr);
                    } else {
                        $this->info("Importing {$index}");
                        $newAsset = $assetModel->replicate();
                        $newAsset->asset_key = substr(Str::random(20), 0, 14);
                        $newAsset->extension = $attr['extension'];
                        $newAsset->replicate_key = $asset->asset_key;
                        $newAsset->save();
                        $newAsset->workspaces()->attach($assetModel->workspaces()->pluck('workspaces.id'));
                        $newAsset->sections()->attach($assetModel->sections()->pluck('sections.id'));
                        $newAsset->tags()->attach($assetModel->tags()->pluck('tags.id'));


                        $this->saveMetadata($newAsset, $attr, false);
                        $this->dispatchUploadJobs($newAsset, $attr);
                        /* replicate image thumb upload */
                        $path = config('deegest.asset_document.asset_file_path');
                        $height = 268;$width = 422;
                        UploadThumbToS3::dispatch($attr, $newAsset->id,$path,$table = 'assets',$height,$width);
                    }
                }

                $this->info("Imported asset: {$asset->asset_key}::{$asset->id}");
           // }
        }

        $this->info('All assets and attachments have been imported successfully.');
    }
    private function saveMetadata($asset, $attr, $updateOrInsert = true)
    {
        $data = collect([
            ['name' => 'original_name', 'value' => $attr['filename'] ?? null],
            ['name' => 'extension',     'value' => $attr['extension'] ?? null],
            ['name' => 'mime_type',     'value' => $attr['mimetype']],
            ['name' => 'size',          'value' => $attr['size'] ?? null],
            ['name' => 'temp_path',     'value' => $attr['mimetype'] ?? null],
        ])->map(function ($meta) use ($asset) {
            return array_merge($meta, [
                'asset_id'   => $asset->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        })->toArray();

        foreach ($data as $meta) {
            if ($updateOrInsert) {
                \DB::table('asset_meta_data')->updateOrInsert(
                    ['asset_id' => $meta['asset_id'], 'name' => $meta['name']],
                    ['value' => $meta['value'], 'updated_at' => now(), 'created_at' => now()]
                );
            } else {
                \DB::table('asset_meta_data')->insert($meta);
            }
        }
    }

    // helper method: dispatch S3 jobs
    private function dispatchUploadJobs($asset, $attr)
    {
        $documentPath = config('deegest.asset_document.asset_file_path');
        $s3Path = "{$documentPath}{$asset->id}/{$attr['filename']}";
        $height = 268;
        $width  = 422;

        UploadAssetToS3::dispatch($attr, $asset->id, $s3Path, $asset->asset_key)->onQueue('assets');
        // UploadThumbToS3::dispatch(...) // uncomment if needed
    }
}
