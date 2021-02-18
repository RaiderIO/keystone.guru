@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $seasonService \App\Service\Season\SeasonService */
/** This is the display of affixes when selecting them when creating a new route */

/** @var \Illuminate\Support\Collection|\App\Models\AffixGroup[] $affixGroups */
$currentSeason = $seasonService->getCurrentSeason();
$seasonalIndexLetters = $currentSeason->getSeasonalIndexesAsLetters();
$affixGroups = $currentSeason->affixgroups()->with(['affixes:affixes.id,affixes.name,affixes.description'])->get();
$affixes = \App\Models\Affix::all();
$defaultSelected = isset($defaultSelected) ? $defaultSelected : [];
?>

@include('common.general.inline', ['path' => 'common/group/affixes', 'options' => [
    'teemingSelector' => $teemingselector,
    'affixGroups' => $affixGroups,
    'modal' => isset($modal) ? $modal : false
    ]])

<div class="form-group">
    {!! Form::label('affixes[]', __('Select affixes')) !!}
    {!! Form::select('affixes[]', $affixGroups->pluck('id', 'id'),
        !isset($dungeonroute) ? $defaultSelected : $dungeonroute->affixgroups->pluck(['affix_group_id']),
        ['id' => 'affixes', 'class' => 'form-control affixselect d-none', 'multiple'=>'multiple']) !!}
    {{--<select name="affixes[]" id="affixes" class="form-control affixselect hidden" multiple--}}
    {{--data-selected-text-format="count > 2">--}}
    {{--@foreach($affixGroups as $group)--}}
    {{--<option value="{{ $group->id }}">{{ $group->id }}</option>--}}
    {{--@endforeach--}}
    {{--</select>--}}

    <div id="affixes_list_custom" class="affix_list col-lg-12">
        @foreach($affixGroups as $affixGroup)
            <div
                class="row affix_list_row {{ $affixGroup->isTeeming() ? 'affix_row_teeming' : 'affix_row_no_teeming' }}"
                {{ $affixGroup->isTeeming() ? 'style="display: none;"' : '' }}
                data-id="{{ $affixGroup->id }}">
                @php( $count = 0 )
                @foreach($affixGroup->affixes as $affix)
                    <?php
                    $last = count($affixGroup->affixes) - 1 === $count;
                    $number = $last ? '2' : '3'
                    ?>
                    <div class="col col-md-{{ $number }} affix_row">
                        <div class="row no-gutters">
                            <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }}"
                                 data-toggle="tooltip"
                                 title="{{ $affix->description }}"
                                 style="height: 24px;">
                            </div>
                            <div class="col d-md-block d-none pl-1">
                                {{ $affix->name }}
                                @if($last)
                                    @isset($affixGroup->seasonal_index)
                                        {{ sprintf(__('(%s)'), $affixGroup->getSeasonalIndexAsLetter()) }}
                                    @endisset
                                @endif
                            </div>
                        </div>
                    </div>
                    @php( $count++ )
                @endforeach
                <span class="col col-md-1 text-right pl-0">
                    <span class="check" style="display: none;">
                        <i class="fas fa-check"></i>
                    </span>
                </span>
            </div>
        @endforeach
    </div>
</div>

@if(!empty($seasonalIndexLetters))
    <div class="form-group">
        {!! Form::label('seasonal_index', __('Awakened enemy set')) !!} <span class="form-required">*</span>
        <i class="fas fa-info-circle" data-toggle="tooltip" title="{{
    __('Awakened enemies (pillar bosses) for M+ levels 10 and higher come in two sets. Each set of affixes is marked either A or B.
    You may attach multiple affixes to your route whom can have both A and B sets. Choose here which set will be displayed on the map.
    You can always adjust your selection from the Route Settings menu later.')
     }}"></i>
        {!! Form::select('seasonal_index', $seasonalIndexLetters, isset($dungeonroute) ? $dungeonroute->seasonal_index : 0,
            ['id' => 'seasonal_index', 'class' => 'form-control selectpicker']) !!}
    </div>
@endif