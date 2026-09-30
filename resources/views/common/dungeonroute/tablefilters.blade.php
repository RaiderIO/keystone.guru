<?php
/**
 * The filter columns of a route table: dungeon, affixes, attributes, requirements and (optionally) tags.
 * Renders only the columns; the caller supplies the surrounding `.row`.
 *
 * @var string                          $columnClass          Classes of every filter column.
 * @var string                          $dungeonSelectId
 * @var string                          $affixSelectId
 * @var string                          $attributesSelectId
 * @var string                          $requirementsSelectId
 * @var string                          $tagsSelectId
 * @var Collection<int, AffixGroup>     $affixgroups
 * @var Collection<int, RouteAttribute> $allRouteAttributes
 * @var Collection<int, Tag>            $searchTags
 * @var bool                            $showFavoriteRequirement
 * @var bool                            $showTags
 * @var array<string, mixed>            $dungeonSelectOptions Extra parameters for common.dungeon.select.
 */

use App\Models\AffixGroup\AffixGroup;
use App\Models\RouteAttribute;
use App\Models\Tags\Tag;
use Illuminate\Support\Collection;

$columnClass          ??= 'col-lg ps-1 pe-1';
$dungeonSelectOptions ??= [];

$requirements = ['enough_enemy_forces' => __('view_common.dungeonroute.tablefilters.enemy_enemy_forces')];
if ($showFavoriteRequirement) {
    $requirements['favorite'] = __('view_common.dungeonroute.tablefilters.favorite');
}
?>
<div class="{{ $columnClass }}">
    @include('common.dungeon.select', array_merge([
        'id' => $dungeonSelectId,
        'allowSeasonSelection' => true,
        'showSeasons' => true,
        'showAll' => true,
        'showExpansions' => true,
        'required' => false,
    ], $dungeonSelectOptions))
</div>
<div class="{{ $columnClass }}">
    {{ html()->label(__('view_common.dungeonroute.tablefilters.affixes'), sprintf('%s[]', $affixSelectId)) }}
    {{
        html()
            ->multiselect(sprintf('%s[]', $affixSelectId), $affixgroups->pluck('text', 'id'))
            ->id($affixSelectId)
            ->class('form-control affixselect selectpicker')
            ->data('selected-text-format', 'count > 1')
            ->data('none-selected-text', __('view_common.dungeonroute.tablefilters.select_affixes'))
            ->data('count-selected-text', __('view_common.dungeonroute.tablefilters.affixes_selected'))
         }}
</div>
<div class="{{ $columnClass }}">
    @include('common.dungeonroute.attributes', [
        'id' => $attributesSelectId,
        'selectedIds' => array_merge( [-1], $allRouteAttributes->pluck('id')->toArray() ),
        'showNoAttributes' => true,
    ])
</div>
<div class="{{ $columnClass }}">
    {{ html()->label(__('view_common.dungeonroute.tablefilters.requirements'), $requirementsSelectId) }}
    {{
        html()
            ->multiselect('dungeon_id', $requirements, 0)
            ->id($requirementsSelectId)
            ->class('form-control selectpicker')
            ->data('selected-text-format', 'count > 1')
            ->data('none-selected-text', __('view_common.dungeonroute.tablefilters.select_requirements'))
            ->data('count-selected-text', __('view_common.dungeonroute.tablefilters.requirements_selected'))
    }}
</div>
@if($showTags)
    <div class="{{ $columnClass }}">
        {{ html()->label(__('view_common.dungeonroute.tablefilters.tags'), sprintf('%s[]', $tagsSelectId)) }}
        {{
            html()
                ->multiselect(sprintf('%s[]', $tagsSelectId), $searchTags->pluck('name', 'name'))
                ->id($tagsSelectId)
                ->class('form-control selectpicker')
                ->attribute('title', $searchTags->isEmpty() ?
                    __('view_common.dungeonroute.tablefilters.tags_title') : __('view_common.dungeonroute.tablefilters.select_tags')
                )
                ->data('selected-text-format', 'count > 1')
                ->data('none-selected-text', __('view_common.dungeonroute.tablefilters.select_tags'))
                ->data('count-selected-text', __('view_common.dungeonroute.tablefilters.tags_selected'))
         }}
    </div>
@endif
