<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Assets;

class CleanAssetChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:chunks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete temporary chunk folders for specific asset IDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assetIds = Assets::where('is_completed', false)->pluck('id')->toArray();

        foreach ($assetIds as $id) {
            $path = storage_path("app/public/uploads/temp/chunk/{$id}");

            if (File::exists($path)) {
                File::deleteDirectory($path);
                $this->info("Deleted: {$path}");
            }
        }

        $this->info('✅ Cleanup done.');
        return Command::SUCCESS;
    }
}
