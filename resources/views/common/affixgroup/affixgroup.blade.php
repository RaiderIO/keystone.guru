<?php
/** @var $affixgroup \App\Models\AffixGroup\AffixGroup */
$media = $media ?? 'lg';
$showText = $showText ?? true;
$class = $class ?? '';
$dungeon = $dungeon ?? null;

$count = $affixgroup->affixes->count();
$cols = $cols ?? $count;

$chunkCount = ceil($count / $cols);
$chunks = $affixgroup->affixes->chunk($chunkCount);
?>
@foreach($chunks as $chunk)
    <div class="row no-gutters px-1 affix_group_row {{ $class }}">
        <?php
        /** @var \Illuminate\Support\Collection $chunk */
        $affixIndex = 0;
        foreach($chunk as $affix) {
        ?>
        <div class="col">
            @include('common.affixgroup.affix', ['showText' => $showText, 'media' => $media, 'affix' => $affix])
        </div>
        <?php
        $affixIndex++;
        }
        ?>
        @if($affixIndex === $affixgroup->affixes->count() && $dungeon instanceof \App\Models\Dungeon)
            <div class="col">
                <h5 class="font-weight-bold pl-1 mt-2">
                    @include('common.dungeonroute.tier', ['affixgroup' => $affixgroup, 'dungeon' => $dungeon])
                </h5>
            </div>
        @endif
    </div>
@endforeach
