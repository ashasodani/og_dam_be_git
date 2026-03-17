<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use App\Models\Assets;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UploadAssetToS3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $assetAttributes;
    protected $assetId;
    protected $path;
    protected $assetkay;

    /**
     * Create a new job instance.
     */
    public function __construct(array $assetAttributes, int $assetId, string $path,string $assetkay)
    {
        $this->assetAttributes = $assetAttributes;
        $this->assetId = $assetId;
        $this->path = $path;
         $this->assetkay = $assetkay;
    }
    //  public $queue = 'assets';
    /**
     * Execute the job.
     */
    public function handle(): void
    {
            $url = $this->assetAttributes['url'];
          
            $thumb_url = $this->assetAttributes['thumbnail_url'];
            if (!$url) {
                \Log::error("Missing url for asset.");
                return;
            }
             if (!$thumb_url) {
                \Log::error("Missing url for asset.");
                return;
            }
        try {
             $response = Http::get($url);

            if (! $response->ok()) {
                throw new \Exception("Failed to download file from URL: $url");
            }
             $responseThumb = Http::get($thumb_url);

            if (! $responseThumb->ok()) {
                throw new \Exception("Failed to download file from URL: $thumb_url");
            }
            $set = Storage::disk('s3')->put($this->path, $response->body());
            $set=Storage::disk('s3')->url($set);
            $data = [
                'asset_url' => $this->path,
                'extension'=>$this->assetAttributes['extension'],
                //'thumbnail_url' => $setThumb,
                'updated_at' => Carbon::now(),
            ];
            
            Assets::where('id', $this->assetId)->update($data);
        } catch (\Throwable $e) {
             report($e);
            \Log::error("Failed to upload asset: " . $e->getMessage());
        }
    }
    public function failed(\Throwable $exception)
        {
            Assets::where('id', $this->assetId)->update([
                'upload_status' => 'failed'
            ]);
            // This will NOT stop the queue, but lets you handle failed job
            \Log::error("Job failed permanently: " . $exception->getMessage());
        }
}
