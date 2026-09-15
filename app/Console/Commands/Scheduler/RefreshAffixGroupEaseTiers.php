<?php

namespace App\Console\Commands\Scheduler;

use App\Service\AffixGroup\AffixGroupEaseTierServiceInterface;
use App\Service\AffixGroup\ArchonApiServiceInterface;
use App\Service\AffixGroup\Exceptions\InvalidResponseException;

class RefreshAffixGroupEaseTiers extends SchedulerCommand
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
        AffixGroupEaseTierServiceInterface $affixGroupEaseTierService,
    ): int {
        return $this->trackTime(function () use ($archonApiService, $affixGroupEaseTierService) {
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

            return 0;
        });
    }
}
