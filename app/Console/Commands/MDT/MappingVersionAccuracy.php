<?php

namespace App\Console\Commands\MDT;

use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Mapping\MappingVersion;
use App\Service\MDT\MDTMappingVersionServiceInterface;
use Illuminate\Console\Command;

class MappingVersionAccuracy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mdt:accuracy {mappingVersionId} {--jitter=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the accuracy of a mapping version compared to MDT';

    /**
     * Execute the console command.
     */
    public function handle(
        MDTMappingVersionServiceInterface $mdtMappingVersionService,
    ): void {
        $mappingVersionId = $this->argument('mappingVersionId');
        $jitter           = $this->option('jitter');

        $mappingVersion = MappingVersion::firstWhere('id', $mappingVersionId);
        if (!$mappingVersion) {
            $this->error('Mapping version not found');

            return;
        }

        if ($jitter) {
            $floor = Floor::firstWhere('id', $jitter);
            /** @var FloorUnion $floorUnion */
            $floorUnion = $mappingVersion->getFloorUnionsForFloor($floor)->first();
            $step       = 0.1;
            $count      = 20;
            $size       = $floorUnion->size - (($count / 2) * $step);
            for ($i = 0; $i < $count; $i++) {
                $size += $step;
                $accuracyOfFloor = $mdtMappingVersionService->getFloorAccuracy($mappingVersion, $floor, $size);

                $this->info(
                    sprintf(
                        'Floor %d: %f%% (%f, %d, %s)',
                        $floor->index,
                        $accuracyOfFloor,
                        $size,
                        $floor->id,
                        __($floor->name),
                    ),
                );
            }
        } else {
            $accuracyByFloor = $mdtMappingVersionService->getMappingVersionAccuracy($mappingVersion);
            foreach ($accuracyByFloor as $floorId => $accuracy) {
                $floor = Floor::firstWhere('id', $floorId);

                $this->info(
                    sprintf(
                        'Floor %d: %f%% (%d, %s)',
                        $floor->index,
                        $accuracy,
                        $floor->id,
                        __($floor->name),
                    ),
                );
            }
        }
    }
}
