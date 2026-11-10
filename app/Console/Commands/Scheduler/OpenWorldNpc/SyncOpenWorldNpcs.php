<?php

namespace App\Console\Commands\Scheduler\OpenWorldNpc;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcFileException;
use App\Service\OpenWorldNpc\OpenWorldNpcSyncServiceInterface;
use Illuminate\Support\Collection;

class SyncOpenWorldNpcs extends SchedulerCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openworldnpc:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds the NPCs in Raider.IO\'s daily list of NPCs that kill players in the open world, and fills in their display ids.';

    public function handle(OpenWorldNpcSyncServiceInterface $openWorldNpcSyncService): int
    {
        return $this->trackTime(function () use ($openWorldNpcSyncService): int {
            if (!$openWorldNpcSyncService->isConfigured()) {
                $this->warn('No bucket is configured for the open-world NPC list; skipping');

                return self::SUCCESS;
            }

            try {
                $result = $openWorldNpcSyncService->sync();
            } catch (InvalidOpenWorldNpcFileException $exception) {
                $this->error(sprintf('Unable to read the open-world NPC list: %s', $exception->getMessage()));

                return self::FAILURE;
            }

            $this->info(sprintf(
                'Read %d NPCs (%d invalid entries): %d new, %d renamed, %d unchanged, %d skipped as they exist in npcs',
                $result->getEntryCount(),
                $result->getInvalidEntryCount(),
                $result->getCreatedNpcIds()->count(),
                $result->getRenamedNpcIds()->count(),
                $result->getUnchangedCount(),
                $result->getSkippedNpcIds()->count(),
            ));
            $this->info(sprintf('New NPC ids: %s', $this->formatIds($result->getCreatedNpcIds())));
            $this->info(sprintf(
                'Display ids found: %d; still without a display id: %s',
                $result->getDisplayIdFilledNpcIds()->count(),
                $this->formatIds($result->getDisplayIdMissingNpcIds()),
            ));
            $this->info(sprintf(
                'NPC ids still without a portrait (%d): %s',
                $result->getWithoutPortraitNpcIds()->count(),
                $this->formatIds($result->getWithoutPortraitNpcIds()),
            ));

            return self::SUCCESS;
        });
    }

    /**
     * @param Collection<int, int> $ids
     */
    private function formatIds(Collection $ids): string
    {
        return $ids->isEmpty() ? 'none' : $ids->implode(', ');
    }
}
