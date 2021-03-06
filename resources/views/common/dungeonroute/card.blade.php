<?php
/** @var $dungeonroute \App\Models\DungeonRoute */

$showAffixes = isset($showAffixes) ? $showAffixes : true;
?>
<div class="card m-1 card_dungeonroute">
    <div class="row no-gutters">
        <div class="col-auto owl-carousel-container">
            <div class="owl-carousel owl-theme">
                @if( $dungeonroute->has_thumbnail )
                    @foreach($dungeonroute->dungeon->floors as $floor)
                        <img
                            src="{{ url(sprintf('/images/route_thumbnails/%s_%s.png', $dungeonroute->public_key, $loop->index + 1)) }}"
                            loading="lazy"/>
                    @endforeach
                @else
                    <img src="{{ url(sprintf(
                            '/images/dungeons/%s/%s_3-2.jpg',
                            $dungeonroute->dungeon->expansion->shortname,
                            $dungeonroute->dungeon->key
                            )) }}" loading="lazy"/>
                @endif
            </div>
        </div>
        <div class="col">
            <div class="card-body p-2">
                <div class="row no-gutters">
                    <div class="col">
                        <h4 class="card-title mb-1">
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
                                <div class="btn btn-secondary" data-container="body"
                                     data-toggle="popover" data-placement="bottom" data-html="true"
                                     data-content="{{ $affixes }}">
                                    <img class="select_icon" src="{{ url('/images/affixes/reaping.jpg') }}"/>
                                </div>
                            @elseif($isTyrannical)
                                <div class="btn btn-secondary" data-container="body"
                                     data-toggle="popover" data-placement="bottom" data-html="true"
                                     data-content="{{ $affixes }}">
                                    <img class="select_icon" src="{{ url('/images/affixes/tyrannical.jpg') }}"/>
                                </div>
                            @elseif($isFortified)
                                <div class="btn btn-secondary" data-container="body"
                                     data-toggle="popover" data-placement="bottom" data-html="true"
                                     data-content="{{ $affixes }}">
                                    <img class="select_icon" src="{{ url('/images/affixes/fortified.jpg') }}"/>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer px-2 py-1">
                <small class="text-muted">
                    {{ __('By') }}
                    <a href="{{ route('profile.view', ['user' => $dungeonroute->author->id]) }}">
                        {{ $dungeonroute->author->name }}
                    </a>
                    <span class="d-lg-inline d-none">
                        -
                        {{ sprintf(__('Last updated %s'), $dungeonroute->updated_at->diffForHumans() ) }}
                    </span>
                </small>
            </div>
        </div>
    </div>
</div>

