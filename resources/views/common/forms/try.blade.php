@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
$region = \App\Models\GameServerRegion::getUserOrDefaultRegion();
/** @var \App\Service\Season\SeasonService $seasonService */
$currentAffixGroup = $seasonService->getCurrentSeason()->getCurrentAffixGroup();
?>
{{ Form::open(['route' => 'dungeonroute.try.post']) }}
<div class="container">
    <h3>
        {{ __('Try') }} {{ config('app.name') }}
    </h3>

    <div class="form-group">
        {{ sprintf(__('This week\'s affixes in %s'), $region->name) }}
        <div class="row no-gutters">
            <?php
            $affixIndex = 0;
            foreach($currentAffixGroup->affixes as $affix) {
                $lastColumn = count($currentAffixGroup->affixes) - 1 === $affixIndex;
            ?>
            <div class="col">
                <div class="row no-gutters mt-2">
                    <div class="col-auto select_icon class_icon affix_icon_{{ strtolower($affix->name) }}"
                         data-toggle="tooltip"
                         title="{{ $affix->description }}"
                         style="height: 24px;">
                    </div>
                    <div class="col d-lg-block d-none pl-1">
                        {{ $affix->name }}
                        @if($lastColumn && $currentAffixGroup->season->presets > 0 )
                            {{ __(sprintf('preset %s', $currentAffixGroup->season->getPresetAt($startDate))) }}
                        @endif
                    </div>
                </div>
            </div>
            <?php
            $affixIndex++;
            }
            ?>
        </div>
    </div>

    <div class="form-group">
        {!! Form::label('dungeon_id', __('Dungeon') . '<span class="form-required">*</span>', [], false) !!}
        {!! Form::select('dungeon_id', \App\Models\Dungeon::active()->orderBy('name')->pluck('name', 'id'), null, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('teeming', __('Teeming')) !!}
        {!! Form::checkbox('teeming', 1, $currentAffixGroup->isTeeming(), ['class' => 'form-control left_checkbox']) !!}
    </div>

    <div class="form-group">
        {!! Form::submit(__('Try it!'), ['class' => 'btn btn-primary col-md-auto']) !!}
        <div class="col-md">

        </div>
    </div>
</div>

{!! Form::close() !!}
