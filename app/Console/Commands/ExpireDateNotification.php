<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Repositories\ShareLinkRepository;
use App\Models\ShareLinks;
use App\Models\User;

class ExpireDateNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:expire-date-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification if it is expire in next 24 hours';
    /**
     * ExpireDateNotification constructor.
     *
     * @param ShareLinkRepository $shareLinkRepository The repository for handling share link operations.
     */

    public function __construct(ShareLinkRepository $shareLinkRepository)
    {
        parent::__construct();
        $this->shareLinkRepository = $shareLinkRepository;
    }

    /**
     * Execute the console command.
     */
      protected $shareLinkRepository;
    public function handle()
    {
        $now = Carbon::now('UTC');
        $next24h = $now->copy()->addHours(24);
        //\Log::info('app:expire-date mail notification started at ' . $now);
        $this->info('Running expiration notification  check at ' . $now);
        $sharelinkData = ShareLinks::whereNotNull('expiry_date')
            ->where('expiry_date', '>', $now)             // after now
            ->where('expiry_date', '<=', $next24h)       // within next 24h
            ->where('status', '!=', 0)
            ->get();
        foreach ($sharelinkData as $link) {
            if($link->is_notify){
               // \Log::info('app:expire-date notification sending at ' . $now);
                $this->info('app:expire-date notification at ' . $now);
                $this->notifyUserForShareLinkExpired($link);
            }
        }
        $this->info('Expiration check notification completed.');
       // \Log::info('Expiration check notification completed at ' . Carbon::now('UTC'));

    }
    public function notifyUserForShareLinkExpired($sharelink){
        $user = User::find($sharelink->create_by);
        if (! $user) {
            return null;
        }
        $title = "Share Link Expiring Soon";

        $message = "Your share link {$sharelink->name} will expire within the next 24 hours.";
        $type="share_link_expired";
        $url = $sharelink->url;
        $url = stripslashes($url);
        $this->shareLinkRepository->notifyUser($user,
                    $title,
                    $message,
                    $url,
                    $type,
                    null,
                    true,
                    true,
                    $sharelink->name);
    }

}