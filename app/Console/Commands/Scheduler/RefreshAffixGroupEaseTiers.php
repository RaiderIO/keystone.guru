<?php

namespace App\Console\Commands\Scheduler;

use App\Models\AffixGroup\AffixGroupEaseTier;
use App\Models\AffixGroup\AffixGroupEaseTierPull;
use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\AffixGroup\ArchonApiServiceInterface;
use App\Service\AffixGroup\Exceptions\InvalidResponseException;
use Illuminate\Console\Command;

class RefreshAffixGroupEaseTiers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affixgroupeasetiers:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the affix group ease tiers from Archon.gg';

    /**
     * Execute the console command.
     */
    public function handle(
        ArchonApiServiceInterface          $archonApiService,
        AffixGroupEaseTierServiceInterface $affixGroupEaseTierService
    ): int {
        try {
            $tierLists = $archonApiService->getDungeonEaseTierListOverall();
        } catch (InvalidResponseException $invalidResponseException) {
            $this->warn(sprintf('Invalid response: %s', $invalidResponseException->getMessage()));

            // Don't fail the deployment when this happens
            return 0;
        }

        if (!isset($tierLists['encounterTierList'])) {
            $this->warn(sprintf('Invalid response: %s', json_encode($tierLists)));

            // Don't fail the deployment when this happens
            return 0;
        }

        $affixGroupEaseTierService->parseTierList($tierLists);

        // Clear model cache so that it will be refreshed upon next request
        $this->call('modelCache:clear', ['--model' => AffixGroupEaseTier::class]);
        $this->call('modelCache:clear', ['--model' => AffixGroupEaseTierPull::class]);

        return 0;
    }
}
