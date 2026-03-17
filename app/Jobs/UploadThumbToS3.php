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

class UploadThumbToS3 implements ShouldQueue
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
        //dd($this->tableId,$this->table);
        $url = $this->assetAttributes['thumbnail_url'] ?? null;

        if (! $url) {
            Log::error("Missing thumbnail_url for {$this->table} ID: {$this->tableId}");
            return;
        }
       // dd($url);
        try {
            $contents      = file_get_contents($url);
            $originalImage = $url;
            $height        = $this->height;
            $width         = $this->width;

            $thumbnail = Image::make($originalImage)
                ->orientate() // Fix mobile orientation
                ->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->resizeCanvas($width, $height, 'center', false, [0, 0, 0, 0])
                ->sharpen(10)
                ->encode('webp', 100);

            $timestamp    = time() . Str::random(10);
            $filename     = "{$timestamp}.webp";
            $documentPath = $this->path . $filename;
            
            Storage::disk('s3')->put($documentPath, (string) $thumbnail);

            // Set column mapping based on table name
            $field = match ($this->table) {
                'tiles' => 'tile_url',
                'additional_links' => 'link_icon',
                'workspaces'       => 'url',
                'portals'          => 'url',
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
