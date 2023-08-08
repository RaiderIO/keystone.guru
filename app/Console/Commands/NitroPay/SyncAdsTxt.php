<?php

namespace App\Console\Commands\NitroPay;

use App\Service\NitroPay\NitroPayServiceInterface;
use Illuminate\Console\Command;

class SyncAdsTxt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nitropay:syncadstxt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncs our ads.txt with NitroPay\'s version of ads.txt and merge it with additional ad providers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(NitroPayServiceInterface $nitroPayService)
    {
        $nitroPayUserId = config('keystoneguru.nitro_pay.user_id');

        if (!is_numeric($nitroPayUserId)) {
            $this->warn('NitroPay userID not set, not syncing ads.txt');
            return 0;
        }

        $nitroPayAdsTxt = $nitroPayService->getAdsTxt($nitroPayUserId);

        $venatusAdsTxt = file_get_contents(public_path('venatus-ads.txt'));

        $nitroPayAdsTxtLength = strlen($nitroPayAdsTxt);
        $venatusAdsTxtLength  = strlen($venatusAdsTxt);
        $this->info(sprintf('NitroPay: %d, Venatus: %d', $nitroPayAdsTxtLength, $venatusAdsTxtLength));

        if ($nitroPayAdsTxtLength > 0 && $venatusAdsTxtLength > 0) {
            if (
                file_put_contents(public_path('ads.txt'), sprintf("%s%s%s", $nitroPayAdsTxt, PHP_EOL . PHP_EOL, $venatusAdsTxt)) !== false
            ) {
                $this->info(sprintf('Writing %s successful', public_path('ads.txt')));
            } else {
                $this->warn(sprintf('Writing %s failed', public_path('ads.txt')));
            }
        } else {
            $this->warn('Not writing ads.txt, one of the sources returned no data');
        }

        return 0;
    }
}
