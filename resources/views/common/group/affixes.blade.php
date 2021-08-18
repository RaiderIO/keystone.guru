@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $seasonService \App\Service\Season\SeasonService */
/** @var $affixes \Illuminate\Support\Collection */
/** This is the display of affixes when selecting them when creating a new route */

/** @var \Illuminate\Support\Collection|\App\Models\AffixGroup[] $affixGroups */
$currentSeason = $seasonService->getCurrentSeason();
$defaultSelected = $defaultSelected ?? [];
$teemingSelector = $teemingSelector ?? null;
$names = $names ?? true;
$id = $id ?? 'affixes';

$presets = [];
for ($i = 0; $i < $currentSeason->presets; $i++) {
    $presets[$i] = sprintf('Preset %s', $i + 1);
}
?>

@include('common.general.inline', ['path' => 'common/group/affixes', 'options' => [
    'selectSelector'   => '#' . $id,
    'teemingSelector'  => $teemingSelector,
    'affixGroups'      => $affixGroups,
    'modal'            => isset($modal) ? $modal : false,
]])

<div class="form-group">
    {!! Form::select($id . '[]', $affixGroups->pluck('id', 'id'),
        !isset($dungeonroute) ? $defaultSelected : $dungeonroute->affixgroups->pluck(['affix_group_id']),
        ['id' => $id, 'class' => 'form-control affixselect d-none', 'multiple'=>'multiple']) !!}

    <div id="{{ $id }}_list_custom" class="affix_list col-lg-12">
        @foreach($affixGroups as $affixGroup)
            <?php $isTeeming = $affixGroup->hasAffix(\App\Models\Affix::AFFIX_TEEMING); ?>
            <div
                class="row affix_list_row {{ $isTeeming ? 'affix_row_teeming' : 'affix_row_no_teeming' }}"
                {{ $isTeeming ? 'style="display: none;"' : '' }}
                data-id="{{ $affixGroup->id }}">
                <?php
                /** @var \App\Models\AffixGroup $affixGroup */
                $count = 0;
                foreach($affixGroup->affixes as $affix){
                $last = count($affixGroup->affixes) - 1 === $count;
                ?>
                <div class="col col-md pr-0 affix_row">
                    <div class="row no-gutters">
                        <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->key) }}"
                             data-toggle="tooltip"
                             title="{{ __($affix->description) }}"
                             style="height: 24px;">
                        </div>
                        @if($names)
                            <div class="col d-md-block d-none pl-1">
                                @if($last)
                                    @if( $affixGroup->seasonal_index !== null )
                                        {{ sprintf(__('affixes.seasonal_index_preset'), __($affix->name), $affixGroup->seasonal_index + 1) }}
                                    @endif
                                @else
                                {{ __($affix->name) }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <?php $count++;
                } ?>
                <span class="col col-md-auto text-right pl-0">
                    <span class="check" style="visibility: hidden;">
                        <i class="fas fa-check"></i>
                    </span>
                </span>
            </div>
        @endforeach
    </div>
</div>

@if($isAwakened)
    <div class="form-group">
        {!! Form::label('seasonal_index', __('Awakened enemy set')) !!} <span class="form-required">*</span>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    __('Awakened enemies (pillar bosses) for M+ levels 10 and higher come in two sets. Each set of affixes is marked either A or B.
    You may attach multiple affixes to your route whom can have both A and B sets. Choose here which set will be displayed on the map.
    You can always adjust your selection from the Route Settings menu later.')
     }}"></i>
        {!! Form::select('seasonal_index', $presets, isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
            ['id' => 'seasonal_index', 'class' => 'form-control selectpicker']) !!}
    </div>
@endif

@if($isTormented)
    <div class="form-group">
        {!! Form::label('seasonal_index', __('Tormented preset')) !!} <span class="form-required">*</span>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    sprintf(__('Tormented enemies for M+ levels 10 and higher come in %s presets.
    You may attach multiple affixes to your route whom can contain any combination of presets. Choose here which preset will be displayed on the map.
    You can always adjust your selection from the Route Settings menu later.'), $currentSeason->presets)
     }}"></i>
        {!! Form::select('seasonal_index', $presets,
            isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
            ['id' => 'seasonal_index', 'class' => 'form-control selectpicker']) !!}
    </div>
@endif