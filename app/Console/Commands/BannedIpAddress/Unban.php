<?php

namespace App\Console\Commands\BannedIpAddress;

use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use Illuminate\Console\Command;

class Unban extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bannedipaddress:unban {ipAddress : The exact IP address or CIDR range as it was banned}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes a ban on an IP address (or CIDR range), for incident response.';

    public function handle(BannedIpAddressServiceInterface $bannedIpAddressService): int
    {
        $ipAddress       = (string)$this->argument('ipAddress');
        $bannedIpAddress = $bannedIpAddressService->all()->firstWhere('ip_address', $ipAddress);

        if ($bannedIpAddress === null) {
            $this->error(sprintf('%s is not currently banned', $ipAddress));

            return self::FAILURE;
        }

        $bannedIpAddressService->unban($bannedIpAddress);

        $this->info(sprintf('Unbanned %s', $ipAddress));

        return self::SUCCESS;
    }
}
