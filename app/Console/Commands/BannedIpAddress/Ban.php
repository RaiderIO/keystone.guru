<?php

namespace App\Console\Commands\BannedIpAddress;

use App\Service\BannedIpAddress\BannedIpAddressServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class Ban extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bannedipaddress:ban
        {ipAddress : The IP address or CIDR range to ban, e.g. 1.2.3.4 or 1.2.3.0/24}
        {--reason= : Why this IP address is being banned}
        {--expires-at= : When the ban should be lifted automatically, e.g. "2026-08-01 00:00:00" - omit for a permanent ban}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bans an IP address (or CIDR range) from accessing the site, for incident response.';

    public function handle(BannedIpAddressServiceInterface $bannedIpAddressService): int
    {
        $ipAddress       = (string)$this->argument('ipAddress');
        $expiresAtOption = $this->option('expires-at');

        $bannedIpAddressService->ban(
            $ipAddress,
            $this->option('reason'),
            $expiresAtOption !== null ? Carbon::parse((string)$expiresAtOption) : null,
            null,
        );

        $this->info(sprintf('Banned %s', $ipAddress));

        return self::SUCCESS;
    }
}
