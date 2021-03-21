<?php
/** @var $dungeonroute \App\Models\DungeonRoute */

$showAffixes = isset($showAffixes) ? $showAffixes : true;
$showDungeonImage = isset($showDungeonImage) ? $showDungeonImage : false;
$enemyForcesPercentage = (int)(($dungeonroute->enemy_forces / $dungeonroute->dungeon->enemy_forces_required) * 100);
$enemyForcesWarning = $dungeonroute->enemy_forces < $dungeonroute->dungeon->enemy_forces_required || $enemyForcesPercentage >= 105;

$owlClass = $dungeonroute->has_thumbnail && $dungeonroute->dungeon->floors->count() > 1 ? 'multiple' : 'single';
?>

<div class="row no-gutters m-xl-1 mx-0 my-3 card_dungeonroute">
    <div class="col-xl-auto">
        <div class="owl-carousel-container {{ $owlClass }}">
            <div class="owl-carousel owl-theme {{ $owlClass }}">
                @if( $dungeonroute->has_thumbnail )
                    @foreach($dungeonroute->dungeon->floors as $floor)
                        <img class="thumbnail w-100"
                             src="{{ url(sprintf('/images/route_thumbnails/%s_%s.png', $dungeonroute->public_key, $loop->index + 1)) }}"/>
                    @endforeach
                @else
                    <img class="dungeon w-100" src="{{ url(sprintf(
                            '/images/dungeons/%s/%s_3-2.jpg',
                            $dungeonroute->dungeon->expansion->shortname,
                            $dungeonroute->dungeon->key
                            )) }}"/>
                @endif
            </div>
        </div>
    </div>
    <div class="col">
        <div class="d-flex flex-column h-100 bg-card"
             @if($showDungeonImage)
             style="background-image: url('{{
                    url(sprintf('/images/dungeons/%s/%s_transparent.png', $dungeonroute->dungeon->expansion->shortname, $dungeonroute->dungeon->key))
                    }}'); background-size: cover;"
            @endif
        >
            <div class="row no-gutters p-2 header">
                <div class="col">
                    <h4 class="mb-0">
                        <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]) }}"
                           target="_blank">
                            {{ $dungeonroute->title }}
                        </a>
                    </h4>
                </div>
                <div class="col-auto">
                    @if( $showAffixes )
                        <?php
                        $isTyrannical = $dungeonroute->isTyrannical();
                        $isFortified = $dungeonroute->isFortified();
                        ob_start();
                        ?>
                        @foreach($dungeonroute->affixes as $affixGroup)
                            <div class="row no-gutters">
                                @include('common.affixgroup.affixgroup', ['affixGroup' => $affixGroup, 'showText' => false])
                            </div>
                        @endforeach
                        <?php $affixes = ob_get_clean(); ?>
                        @if($isTyrannical && $isFortified)
                            <div data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <img class="select_icon"
                                     src="{{ url('/images/affixes/keystone.jpg') }}"/>
                            </div>
                        @elseif($isTyrannical)
                            <div data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <img class="select_icon"
                                     src="{{ url('/images/affixes/tyrannical.jpg') }}"/>
                            </div>
                        @elseif($isFortified)
                            <div data-container="body" data-toggle="popover" data-placement="bottom"
                                 data-html="true"
                                 data-content="{{ $affixes }}" style="cursor: pointer;">
                                <img class="select_icon"
                                     src="{{ url('/images/affixes/fortified.jpg') }}"/>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            <div class="row no-gutters px-2 pb-2 pt-1 px-md-3 flex-fill d-flex description">
                <div class="col">
                    {{
                        empty($dungeonroute->description) ? __('No description') : $dungeonroute->description
                    }}
                </div>
            </div>
            <div class="row no-gutters p-2 enemy_forces">
                <div class="col-auto">
                    @if( $enemyForcesWarning )
                        <span class="text-warning"> <i class="fas fa-exclamation-triangle"></i> </span>
                    @else
                        <span class="text-success"> <i class="fas fa-check-circle"></i> </span>
                    @endif
                    {{ sprintf(
                        __('%s/%s (%s%%)'),
                        $dungeonroute->enemy_forces,
                        $dungeonroute->dungeon->enemy_forces_required,
                        $enemyForcesPercentage
                        ) }}
                </div>
                <div class="col">
                    @include('common.dungeonroute.level', ['levelMin' => $dungeonroute->level_min, 'levelMax' => $dungeonroute->level_max])
                </div>
            </div>
            <div class="row no-gutters footer">
                <div class="col bg-card-footer px-2 py-1">
                    <small class="text-muted">
                        {{ __('By') }}
                        <a href="{{ route('profile.view', ['user' => $dungeonroute->author->id]) }}">
                            {{ $dungeonroute->author->name }}
                        </a>
                        @if( $dungeonroute->avg_rating > 1 )
                            -
                            @include('common.dungeonroute.rating', ['rating' => (int) $dungeonroute->avg_rating])
                        @endif
                        <span class="d-lg-inline d-none">
                                    -
                                    {{ sprintf(__('Last updated %s'), $dungeonroute->updated_at->diffForHumans() ) }}
                                </span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

{{--<div class="card m-1 card_dungeonroute">--}}
{{--    <div class="row no-gutters">--}}
{{--        <div class="col-auto owl-carousel-container">--}}
{{--            <div class="owl-carousel owl-theme {{ $dungeonroute->has_thumbnail && $dungeonroute->dungeon->floors->count() > 1 ? 'multiple' : 'single' }}">--}}
{{--                @if( $dungeonroute->has_thumbnail )--}}
{{--                    @foreach($dungeonroute->dungeon->floors as $floor)--}}
{{--                        <img--}}
{{--                            src="{{ url(sprintf('/images/route_thumbnails/%s_%s.png', $dungeonroute->public_key, $loop->index + 1)) }}"--}}
{{--                            loading="lazy"/>--}}
{{--                    @endforeach--}}
{{--                @else--}}
{{--                    <img src="{{ url(sprintf(--}}
{{--                            '/images/dungeons/%s/%s_3-2.jpg',--}}
{{--                            $dungeonroute->dungeon->expansion->shortname,--}}
{{--                            $dungeonroute->dungeon->key--}}
{{--                            )) }}" loading="lazy"/>--}}
{{--                @endif--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="col">--}}
{{--            <div class="card-body {{ $showDungeonImage ? 'p-0' : 'p-2' }}">--}}
{{--                @if($showDungeonImage)--}}
{{--                    <div class="dungeon_background"--}}
{{--                         style="background-image: url('{{ url(sprintf('/images/dungeons/%s/%s.jpg', $dungeonroute->dungeon->expansion->shortname, $dungeonroute->dungeon->key)) }}')"--}}
{{--                    >--}}

{{--                    </div>--}}
{{--                    <div class="dungeon_background_cover p-2">--}}
{{--                            @endif--}}
{{--                            <div class="row no-gutters">--}}
{{--                                <div class="col">--}}
{{--                                    <h4 class="card-title mb-1">--}}
{{--                                        <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]) }}"--}}
{{--                                           target="_blank">--}}
{{--                                            {{ $dungeonroute->title }}--}}
{{--                                        </a>--}}
{{--                                    </h4>--}}
{{--                                </div>--}}
{{--                                <div class="col-auto">--}}
{{--                                    @if( $showAffixes )--}}
{{--                                        <?php--}}
{{--                                        $isTyrannical = $dungeonroute->isTyrannical();--}}
{{--                                        $isFortified = $dungeonroute->isFortified();--}}
{{--                                        ob_start();--}}
{{--                                        ?>--}}
{{--                                        @foreach($dungeonroute->affixes as $affixGroup)--}}
{{--                                            <div class="row no-gutters">--}}
{{--                                                @include('common.affixgroup.affixgroup', ['affixGroup' => $affixGroup, 'showText' => false])--}}
{{--                                            </div>--}}
{{--                                        @endforeach--}}
{{--                                        <?php $affixes = ob_get_clean(); ?>--}}
{{--                                        @if($isTyrannical && $isFortified)--}}
{{--                                            <div data-container="body" data-toggle="popover" data-placement="bottom"--}}
{{--                                                 data-html="true"--}}
{{--                                                 data-content="{{ $affixes }}" style="cursor: pointer;">--}}
{{--                                                <img class="select_icon"--}}
{{--                                                     src="{{ url('/images/affixes/keystone.jpg') }}"/>--}}
{{--                                            </div>--}}
{{--                                        @elseif($isTyrannical)--}}
{{--                                            <div data-container="body" data-toggle="popover" data-placement="bottom"--}}
{{--                                                 data-html="true"--}}
{{--                                                 data-content="{{ $affixes }}" style="cursor: pointer;">--}}
{{--                                                <img class="select_icon"--}}
{{--                                                     src="{{ url('/images/affixes/tyrannical.jpg') }}"/>--}}
{{--                                            </div>--}}
{{--                                        @elseif($isFortified)--}}
{{--                                            <div data-container="body" data-toggle="popover" data-placement="bottom"--}}
{{--                                                 data-html="true"--}}
{{--                                                 data-content="{{ $affixes }}" style="cursor: pointer;">--}}
{{--                                                <img class="select_icon"--}}
{{--                                                     src="{{ url('/images/affixes/fortified.jpg') }}"/>--}}
{{--                                            </div>--}}
{{--                                        @endif--}}
{{--                                    @endif--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                            <div class="row no-gutters">--}}
{{--                                <div class="col">--}}
{{--                                    @if( $enemyForcesWarning )--}}
{{--                                        <span class="text-warning"> <i class="fas fa-exclamation-triangle"></i> </span>--}}
{{--                                    @else--}}
{{--                                        <span class="text-success"> <i class="fas fa-check-circle"></i> </span>--}}
{{--                                    @endif--}}
{{--                                    {{ sprintf(--}}
{{--                                        __('%s/%s (%s%%)'),--}}
{{--                                        $dungeonroute->enemy_forces,--}}
{{--                                        $dungeonroute->dungeon->enemy_forces_required,--}}
{{--                                        $enemyForcesPercentage--}}
{{--                                        ) }}--}}
{{--                                </div>--}}
{{--                            </div>--}}

{{--                            @if($showDungeonImage)--}}
{{--                    </div>--}}
{{--                @endif--}}
{{--            </div>--}}
{{--            <div class="card-footer px-2 py-1">--}}
{{--                <small class="text-muted">--}}
{{--                    {{ __('By') }}--}}
{{--                    <a href="{{ route('profile.view', ['user' => $dungeonroute->author->id]) }}">--}}
{{--                        {{ $dungeonroute->author->name }}--}}
{{--                    </a>--}}
{{--                    @if( $dungeonroute->avg_rating > 1 )--}}
{{--                        ---}}
{{--                        @include('common.dungeonroute.rating', ['rating' => (int) $dungeonroute->avg_rating])--}}
{{--                    @endif--}}
{{--                    <span class="d-lg-inline d-none">--}}
{{--                        ---}}
{{--                        {{ sprintf(__('Last updated %s'), $dungeonroute->updated_at->diffForHumans() ) }}--}}
{{--                    </span>--}}
{{--                </small>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}

