<?php
/** @var $dungeonroute \App\Models\DungeonRoute */

$showAffixes = isset($showAffixes) ? $showAffixes : true;
?>


<div class="card m-2">
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
            <div class="card-block p-2">
                <h4 class="card-title mb-1">
                    <a href="{{ route('dungeonroute.view', ['dungeonroute' => $dungeonroute->public_key]) }}" target="_blank">
                        {{ $dungeonroute->title }}
                    </a>
                </h4>
                @if( $showAffixes )
                    <?php
                    $count = $dungeonroute->affixgroups->count();
                    ?>
                    @if($count === 1)
                        @include('common.affixgroup.affixgroup', ['affixGroup' => $dungeonroute->affixes()->first()])
                    @elseif( $count === config('keystoneguru.season_iteration_affix_group_count'))
                        {{ __('Any affix') }}
                    @else
                        {{ sprintf(__('%d affixes'), $dungeonroute->affixgroups->count()) }}
                    @endif
                @endif
            </div>
            <div class="card-footer py-1">
                <small class="text-muted">
                    <a href="{{ route('profile.view', ['user' => $dungeonroute->author->id]) }}">
                        {{ sprintf(__('By %s'), $dungeonroute->author->name) }}
                    </a>
                    -
                    {{ sprintf(__('Last updated %s'), $dungeonroute->updated_at->diffForHumans() ) }}
                </small>
            </div>
        </div>
    </div>
</div>

