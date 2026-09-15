<?php

namespace App\Console\Commands\MapIcon;

use App\Logic\MDT\Entity\MDTMapPOI;
use App\Models\Dungeon;
use App\Models\Season;
use App\Service\MDT\MDTMappingImportServiceInterface;
use App\Service\WagoTools\Exceptions\WagoToolsDownloadException;
use App\Service\WagoTools\WagoToolsServiceInterface;
use App\Service\Wowhead\WowheadServiceInterface;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DownloadMDTItemIcons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapicon:downloadmdtitemicons {dungeon}
                            {--product=wow : The CDN product to read DB2 data for, e.g. wow, wowt or wow_classic}
                            {--build= : A specific game build, e.g. 12.1.0.69214; defaults to the most recent one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads the source icons of all MDT map POIs that have no map icon type yet';

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(
        MDTMappingImportServiceInterface $mappingImportService,
        WagoToolsServiceInterface        $wagoToolsService,
        WowheadServiceInterface          $wowheadService,
    ): int {
        $product = (string)$this->option('product');
        $build   = $this->option('build') === null ? null : (string)$this->option('build');
        $build ??= $wagoToolsService->getLatestBuild($product);

        if ($build === null) {
            $this->error(sprintf('Unable to resolve a game build for product %s', $product));

            return 1;
        }

        $dungeonArgument = $this->argument('dungeon');

        if (is_numeric($dungeonArgument)) {
            $dungeons = Season::findOrFail($dungeonArgument)->dungeons;
        } else {
            $dungeons = new Collection([Dungeon::where('key', $dungeonArgument)->firstOrFail()]);
        }

        /** @var Collection<int, MDTMapPOI> $unhandledMapPOIs */
        $unhandledMapPOIs = new Collection();
        $dungeonKeyByPOI  = [];

        foreach ($dungeons as $dungeon) {
            foreach ($mappingImportService->getUnhandledMapPOIs($dungeon) as $mdtMapPOI) {
                $dungeonKeyByPOI[spl_object_id($mdtMapPOI)] = $dungeon->key;
                $unhandledMapPOIs->push($mdtMapPOI);
            }
        }

        if ($unhandledMapPOIs->isEmpty()) {
            $this->info('No unhandled MDT map POIs - nothing to download.');

            return 0;
        }

        // One texture may be shared by several POIs, and resolving them costs a ~9MB DB2 download each time
        $fileDataIds = $unhandledMapPOIs
            ->map(static fn(MDTMapPOI $mdtMapPOI): ?int => $mdtMapPOI->getTextureFileDataId())
            ->filter()
            ->unique()
            ->values()
            ->all();

        try {
            $iconFileNames = $wagoToolsService->getIconFileNamesByFileDataIds($fileDataIds, $build);
        } catch (WagoToolsDownloadException $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $targetFolder = realpath(base_path('../keystone.guru.assets/images/mapicon_gen'));
        if ($targetFolder === false) {
            $this->error('Unable to find the keystone.guru.assets repository next to this checkout - is it cloned, and is it mounted into this container?');

            return 1;
        }

        $rows        = [];
        $downloaded  = [];
        $hasFailures = false;

        foreach ($unhandledMapPOIs as $mdtMapPOI) {
            $fileDataId   = $mdtMapPOI->getTextureFileDataId();
            $iconFileName = $fileDataId === null ? null : ($iconFileNames[$fileDataId] ?? null);

            $status = 'no texture';
            if ($iconFileName !== null) {
                if (isset($downloaded[$iconFileName])) {
                    $status = $downloaded[$iconFileName];
                } elseif (file_exists(sprintf('%s/%s.jpg', $targetFolder, $iconFileName))) {
                    $status = 'already present';
                } elseif ($wowheadService->downloadIcon($iconFileName, $targetFolder)) {
                    $status = 'downloaded';
                } else {
                    $status      = 'DOWNLOAD FAILED';
                    $hasFailures = true;
                }

                $downloaded[$iconFileName] = $status;
            } elseif ($fileDataId !== null) {
                $status      = 'NOT AN ICON IN ManifestInterfaceData';
                $hasFailures = true;
            }

            $rows[] = [
                $dungeonKeyByPOI[spl_object_id($mdtMapPOI)],
                $mdtMapPOI->getType()->value,
                $mdtMapPOI->getSpellId() ?? '-',
                $fileDataId ?? '-',
                $iconFileName ?? '-',
                $status,
                $mdtMapPOI->getSpellId() === null
                    ? '-'
                    : sprintf('https://www.wowhead.com/spell=%d', $mdtMapPOI->getSpellId()),
            ];
        }

        $this->table(
            ['Dungeon', 'MDT type', 'Spell ID', 'Texture ID', 'Icon', 'Status', 'Wowhead'],
            $rows,
        );

        $this->newLine();
        $this->line('Next: add a MapIconType for each item (constant + id in MapIconType::ALL,');
        $this->line('database/seeders/mapicontypedata/map_icon_types.json,');
        $this->line('lang/en_US/mapicontypes.php, GenerateItemIcons and Conversion::MAP_POI_GENERIC_ITEM_SPELL_ID_MAP_ICON_TYPE_MAPPING),');
        $this->line('then run <comment>mapicon:generateitemicons</comment>.');

        return $hasFailures ? 1 : 0;
    }
}
