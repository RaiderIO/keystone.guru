<?php

use App\Models\Season;

/**
 * @var int         $levelMin
 * @var int         $levelMax
 * @var int|null    $minAnchorKeyLevelWidth
 * @var Season|null $season
 */
$min                    = $season?->key_level_min ?? config('keystoneguru.keystone.levels.default_min');
$max                    = $season?->key_level_max ?? config('keystoneguru.keystone.levels.default_max');
$levelMin               = $levelMin ?? $min;
$levelMax               = $levelMax ?? $max;
$minAnchorKeyLevelWidth = $minAnchorKeyLevelWidth ?? 1;
?>
<div class="row no-gutters h-100 px-2">
    <div class="col progress h-100" style="border-radius: 0">
        @if($levelMin > $min)
            <div class="progress-bar text-left pl-1" role="progressbar" aria-valuenow="{{ $min }}"
                 aria-valuemin="{{ $min }}" aria-valuemax="{{ $levelMin }}"
                 style="width: {{ (($levelMin - $min) / ($max - $min)) * 100 }}%">
                    <?php // Make sure there's space to render the min values - there is none if there's just one key level ?>
                @if($levelMin > $min + $minAnchorKeyLevelWidth)
                    +{{ $min }}
                @endif
            </div>
        @endif
        <div class="progress-bar text-center bg-success px-1" role="progressbar" aria-valuenow="{{ $levelMin }}"
             aria-valuemin="{{ $levelMin }}" aria-valuemax="{{ $levelMax }}"
             data-toggle="tooltip"
             title="{{ $levelMax === $levelMin ? sprintf('+%d', $levelMax) : sprintf('+%d - +%d', $levelMin, $levelMax) }}"
             style="width: {{ (($levelMax - $levelMin) / ($max - $min)) * 100 }}%">
            <?php // Make sure there's space to render the values ?>
            @if( $levelMax - $levelMin >= 4)
                <div class="row no-gutters">
                    <div class="col text-left">
                        +{{ $levelMin }}
                    </div>
                    <div class="col text-right">
                        +{{ $levelMax }}
                    </div>
                </div>
            @endif
        </div>
        @if($levelMax < $max)
            <div class="progress-bar text-right pr-1" role="progressbar" aria-valuenow="{{ $levelMax }}"
                 aria-valuemin="{{ $levelMax }}" aria-valuemax="{{ $max }}"
                 style="width: {{ (($max - $levelMax) / ($max - $min)) * 100 }}%">
                    <?php // Make sure there's space to render the max values - there is none if there's just one key level ?>
                @if($levelMax < $max - $minAnchorKeyLevelWidth)
                    +{{ $max }}
                @endif
            </div>
        @endif
    </div>
</div>
