<?php

namespace App\Console\Commands\OpenWorldNpc;

use App\Service\OpenWorldNpc\Exceptions\InvalidOpenWorldNpcPortraitBatchException;
use App\Service\OpenWorldNpc\Exceptions\OpenWorldNpcPortraitsUnavailableException;
use App\Service\OpenWorldNpc\OpenWorldNpcPortraitServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MarkPortraitBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openworldnpc:markportraitbatch
                            {npcsCsv : The npcs.csv of the generated batch, absolute or relative to the project root}
                            {--unrenderable=* : NPC ids whose portrait came out wrong; their file is removed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks the open-world NPCs of a generated portrait batch as having a portrait, or as unrenderable.';

    public function handle(OpenWorldNpcPortraitServiceInterface $openWorldNpcPortraitService): int
    {
        $unrenderableNpcIds = collect((array)$this->option('unrenderable'))
            ->flatMap(static fn(string $value) => explode(',', $value))
            ->map(static fn(string $value) => trim($value))
            ->filter(static fn(string $value) => $value !== '');
        $invalidIds = $unrenderableNpcIds->reject(static fn(string $value) => ctype_digit($value));
        if ($invalidIds->isNotEmpty()) {
            $this->error(sprintf('--unrenderable takes NPC ids, got: %s', $invalidIds->implode(', ')));

            return self::FAILURE;
        }

        $npcsCsvPath = (string)$this->argument('npcsCsv');
        if (!str_starts_with($npcsCsvPath, '/')) {
            $npcsCsvPath = base_path($npcsCsvPath);
        }

        try {
            $result = $openWorldNpcPortraitService->markBatch(
                $openWorldNpcPortraitService->readBatchNpcIds($npcsCsvPath),
                $unrenderableNpcIds->map(static fn(string $value) => (int)$value)->values(),
            );
        } catch (InvalidOpenWorldNpcPortraitBatchException|OpenWorldNpcPortraitsUnavailableException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Marked %d NPCs as having a portrait: %s',
            $result->getPortraitNpcIds()->count(),
            $this->formatIds($result->getPortraitNpcIds()),
        ));
        $this->info(sprintf(
            'Could not render %d NPCs: %s',
            $result->getUnrenderableNpcIds()->count(),
            $this->formatIds($result->getUnrenderableNpcIds()),
        ));
        $this->info(sprintf('Removed blank or rejected portrait files of: %s', $this->formatIds($result->getRemovedNpcIds())));
        if ($result->getUnknownNpcIds()->isNotEmpty()) {
            $this->warn(sprintf('Not open-world NPCs, left alone: %s', $this->formatIds($result->getUnknownNpcIds())));
        }

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
