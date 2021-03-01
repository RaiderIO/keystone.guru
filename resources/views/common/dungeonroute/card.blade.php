<?php
/** @var $dungeonroute \App\Models\DungeonRoute */
?>
<div class="col">
    <div class="row">
        <div class="col">
            <div class="owl-carousel owl-theme">
                @if( $dungeonroute->has_thumbnail )
                    @foreach($dungeonroute->dungeon->floors as $floor)
                        <img src="{{ url(sprintf('/images/route_thumbnails/%s_%s.png', $dungeonroute->public_key, $loop->index)) }}" loading="lazy" style="width: 128px"/>
                    @endforeach
                @else
                    <img src="{{ url(sprintf(
                            '/images/dungeons/%s/%s_3-2.jpg',
                            $dungeonroute->dungeon->expansion->shortname,
                            $dungeonroute->dungeon->key
                            )) }}" loading="lazy" style="width: 128px"/>
                @endif
            </div>
        </div>
        <div class="col">
            .. details
        </div>
    </div>
</div>
