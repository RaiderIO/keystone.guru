<?php

namespace App\Console\Commands\AdProvider;

use App\Service\AdProvider\AdProviderServiceInterface;
use Illuminate\Console\Command;

class SyncAdsTxt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adprovider:syncadstxt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Syncs our ads.txt with our Ad Provider's version of ads.txt";

    /**
     * Execute the console command.
     */
    public function handle(AdProviderServiceInterface $nitroPayService): int
    {
        //        $nitroPayUserId = config('keystoneguru.nitro_pay.user_id');
        //
        //        if (!is_numeric($nitroPayUserId)) {
        //            $this->warn('NitroPay userID not set, not syncing ads.txt');
        //            return 0;
        //        }
        //
        //        $nitroPayAdsTxt = $nitroPayService->getPlaywireAdsTxt($nitroPayUserId);
        //
        //        $venatusAdsTxt = file_get_contents(public_path('venatus-ads.txt'));
        //
        //        $nitroPayAdsTxtLength = strlen($nitroPayAdsTxt);
        //        $venatusAdsTxtLength  = strlen($venatusAdsTxt);
        //        $this->info(sprintf('NitroPay: %d, Venatus: %d', $nitroPayAdsTxtLength, $venatusAdsTxtLength));
        //
        //        if ($nitroPayAdsTxtLength > 0 && $venatusAdsTxtLength > 0) {
        //            if (
        //                file_put_contents(public_path('ads.txt'), sprintf("%s%s%s", $nitroPayAdsTxt, PHP_EOL . PHP_EOL, $venatusAdsTxt)) !== false
        //            ) {
        //                $this->info(sprintf('Writing %s successful', public_path('ads.txt')));
        //            } else {
        //                $this->warn(sprintf('Writing %s failed', public_path('ads.txt')));
        //            }
        //        } else {
        //            $this->warn('Not writing ads.txt, one of the sources returned no data');
        //        }

        $playwireParam1 = config('keystoneguru.playwire.param_1');
        $playwireParam2 = config('keystoneguru.playwire.param_2');

        if (!is_numeric($playwireParam1) || !is_numeric($playwireParam2)) {
            $this->warn('Playwire params not set, not syncing ads.txt');

            return 0;
        }

        $playwireAdsTxt = $nitroPayService->getPlaywireAdsTxt($playwireParam1, $playwireParam2);

        $playwireAdsTxtLength = strlen($playwireAdsTxt);
        $this->info(sprintf('Playwire: %d', $playwireAdsTxtLength));

        if (strlen($playwireAdsTxt) > 0) {
            if (file_put_contents(public_path('ads.txt'), $playwireAdsTxt) !== false) {
                $this->info(sprintf('Writing %s successful', public_path('ads.txt')));
            } else {
                $this->warn(sprintf('Writing %s failed', public_path('ads.txt')));
            }
        } else {
            $this->warn('Not writing ads.txt, the source returned no data');
        }

        return 0;
    }
}
