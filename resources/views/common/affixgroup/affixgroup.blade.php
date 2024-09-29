<?php

use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use Illuminate\Support\Collection;

/**
 * @var AffixGroup $affixgroup
 */

$media    ??= 'lg';
$showText ??= true;
$class    ??= '';
$dungeon  ??= null;
$center   ??= false;

$count = $affixgroup->affixes->count();
$cols  ??= $count;

$chunkCount = ceil($count / $cols);
$chunks     = $affixgroup->affixes->chunk($chunkCount);
?>
@foreach($chunks as $chunk)
    <div class="row no-gutters px-1 affix_group_row {{ $class }}">
            <?php
            /** @var Collection $chunk */
            $affixIndex = 0;
        foreach ($chunk as $affix) {
            ?>
        <div class="col">
            <div class="row no-gutters m-auto affix_group_{{ $affixgroup->id }}">
                <div class="col-auto {{ $center ? 'm-auto' : '' }}">
                    @include('common.affixgroup.affix', ['showText' => $showText, 'media' => $media, 'affix' => $affix])
                </div>

                @if($affixIndex === $count - 1 && $dungeon instanceof Dungeon)
                    <div class="col-auto">
                        <h5 class="font-weight-bold pl-2 mt-2">
                            @include('common.dungeonroute.tier', ['affixgroup' => $affixgroup, 'dungeon' => $dungeon])
                        </h5>
                    </div>
                @endif
            </div>
        </div>
            <?php
            ++$affixIndex;
        }
            ?>
    </div>
@endforeach
