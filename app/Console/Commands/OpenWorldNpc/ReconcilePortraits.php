<?php

namespace App\Console\Commands\OpenWorldNpc;

use App\Models\Npc\OpenWorldNpc;
use App\Service\OpenWorldNpc\Exceptions\OpenWorldNpcPortraitsUnavailableException;
use App\Service\OpenWorldNpc\OpenWorldNpcPortraitServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ReconcilePortraits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openworldnpc:reconcileportraits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets whether each open-world NPC has a portrait from the portrait files that exist, and lists the NPCs that could not be rendered.';

    public function handle(OpenWorldNpcPortraitServiceInterface $openWorldNpcPortraitService): int
    {
        try {
            $result = $openWorldNpcPortraitService->reconcile();
        } catch (OpenWorldNpcPortraitsUnavailableException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%d open-world NPCs have a portrait file, %d do not',
            $result->getWithPortraitCount(),
            $result->getWithoutPortraitCount(),
        ));
        $this->info(sprintf('Now marked as having a portrait: %s', $this->formatIds($result->getGainedNpcIds())));
        $this->info(sprintf('No longer marked as having a portrait: %s', $this->formatIds($result->getLostNpcIds())));
        $this->info(sprintf(
            'Could not render (%d): %s',
            $result->getUnrenderableNpcs()->count(),
            $result->getUnrenderableNpcs()->isEmpty() ? 'none' : $result->getUnrenderableNpcs()
                ->map(static fn(OpenWorldNpc $npc) => sprintf('%d (display id %d, %s)', $npc->id, $npc->display_id, $npc->name))
                ->implode(', '),
        ));

        return self::SUCCESS;
    }

    /**
     * @param Collection<int, int> $ids
     */
    private function formatIds(Collection $ids): string
    {
        return $ids->isEmpty() ? 'none' : $ids->implode(', ');
    }
}
