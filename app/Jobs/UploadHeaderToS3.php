<?php

namespace App\Jobs;

use App\Models\AddtionalLinks;
use App\Models\Assets;
use App\Models\Portals;
use App\Models\Tiles;
use App\Models\Workspaces;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;

class UploadHeaderToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $assetAttributes;
    protected $tableId;
    protected $path;
    protected $table;
    protected $height;
    protected $width;
    protected string $field;

    /**
     * Create a new job instance.
     */
    public function __construct(array $assetAttributes, int $tableId, string $path, string $table, int $height, int $width, string $field = 'url')
    {
        $this->assetAttributes = $assetAttributes;
        $this->tableId         = $tableId;
        $this->path            = $path;
        $this->table           = $table;
        $this->height          = $height;
        $this->width           = $width;
        $this->field = $field;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $url = $this->assetAttributes['thumbnail_url'] ?? null;
            $response = Http::get($url);

            if (! $url) {
                Log::error("Missing thumbnail_url for {$this->table} ID: {$this->tableId}");
                return;
            }
            if ($response->successful()) {
                $contents = $response->body();
                $timestamp    = time() . Str::random(10);
                // generate a filename with extension if you know it
                $filename     = "{$timestamp}.webp"; // or .png/.pdf as per content-type

                // store to S3
                // get S3 URL
               
            }
            $documentPath = $this->path . $filename;

            Storage::disk('s3')->put($documentPath,$contents);
            $url = Storage::disk('s3')->url($documentPath);

            $field = match ($this->table) {
                'tiles' => 'tile_url',
                'additional_links' => 'link_icon',
                'workspaces'       => 'url',
                'portals'          => 'header_url',
                default            => 'url', // for assets etc.
            };

            $data = [$field => $documentPath];

            // Update appropriate model
            match ($this->table) {
                'tiles'            => Tiles::where('id', $this->tableId)->update($data),
                'additional_links' => AddtionalLinks::where('id', $this->tableId)->update($data),
                'workspaces'       => Workspaces::where('id', $this->tableId)->update($data),
                'portals'          => Portals::where('id', $this->tableId)->update($data),
                'assets'           => Assets::where('id', $this->tableId)->update($data),
                default            => Log::warning("Unknown table '{$this->table}' ID: {$this->tableId}"),
            };

            Log::info("Uploaded thumbnail for {$this->table} ID: {$this->tableId} → {$documentPath}");
        } catch (\Throwable $e) {
            Log::error("Failed to upload: " . $e->getMessage());
        }
    }
}
