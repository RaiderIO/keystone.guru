<?php

namespace App\Console\Commands\BannedIpAddress;

use App\Models\Laratrust\Role;
use App\Models\User;
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
        {adminId : The user ID of the admin performing this ban, so it can always be attributed to someone}
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
        $ipAddress = (string)$this->argument('ipAddress');
        $admin     = User::find((int)$this->argument('adminId'));

        if ($admin === null || !$admin->hasRole(Role::ROLE_ADMIN)) {
            $this->error(sprintf('%s is not a valid admin user ID', $this->argument('adminId')));

            return self::FAILURE;
        }

        $expiresAtOption = $this->option('expires-at');

        $bannedIpAddressService->ban(
            $ipAddress,
            $this->option('reason'),
            $expiresAtOption !== null ? Carbon::parse((string)$expiresAtOption) : null,
            $admin->id,
        );

        $this->info(sprintf('Banned %s (attributed to %s)', $ipAddress, $admin->name));

        return self::SUCCESS;
    }
}
