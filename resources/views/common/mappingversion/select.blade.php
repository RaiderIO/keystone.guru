<?php


use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

/**
 * @var Dungeon                 $dungeon
 * @var Collection<GameVersion> $allGameVersions
 */
$dungeons        ??= collect([$dungeon]);
$dungeons        = $dungeons->keyBy('id');
$id              ??= 'mapping_version';
$selected        ??= null;
$allGameVersions = $allGameVersions->keyBy('id');

$mappingVersions = collect();

foreach ($dungeons as $dungeon) {
    $mappingVersions = $mappingVersions->union(
        $dungeon->loadMappingVersions()
            ->mappingVersions
    );
}

$mappingVersionsSelect = $mappingVersions->groupBy('dungeon_id')
    ->mapWithKeys(static function (Collection $mappingVersions, int $dungeonId) use ($allGameVersions, $dungeons) {
//            /** @var GameVersion $gameVersion */
//            $gameVersion = $allGameVersions->get($gameVersionId);
        /** @var Dungeon $dungeon */
        $dungeon = $dungeons->get($dungeonId);

        return [
            __($dungeon->name) => $mappingVersions
                ->sortByDesc('name')
                ->mapWithKeys(static function (MappingVersion $mappingVersion) use ($dungeon) {
                    if ($mappingVersion->merged) {
                        return [
                            $mappingVersion->id => __('view_common.mappingversion.select.mapping_version_readonly', [
                                'gameVersion' => __($mappingVersion->gameVersion->name),
                                'version'     => $mappingVersion->version
                            ])
                        ];
                    } else {
                        return [
                            $mappingVersion->id => __('view_common.mappingversion.select.mapping_version', [
                                'gameVersion' => __($mappingVersion->gameVersion->name),
                                'version'     => $mappingVersion->version
                            ])
                        ];
                    }
                })->toArray()
        ];
    });
?>

{{ html()->select($id, $mappingVersionsSelect, $selected)->class('form-control selectpicker') }}
