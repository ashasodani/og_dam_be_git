<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\ShareLinks;

class ExpireDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-datee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark items as expired if expiry_date (UTC) has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('UTC');
         //\Log::info('started cron expiry-date'.$now);
        $expiredItems = Sharelinks::where('expiry_date', '<', $now)->where('status', '!=', 0)->get();
        if ($expiredItems->isEmpty()) {
            $this->info('No items to expire.');
            return;
        }

        foreach ($expiredItems as $item) {
            $item->status = 0; // or whatever field you want
            $item->save();
            $this->info("Item {$item->id} expired.");
            //\Log::info("Item {$item->id} expired.");
        }
        //\Log::info('end cron'.$now);
        $this->info('Expiration check completed.');
    }
}
