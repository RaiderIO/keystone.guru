<?php
/** @var $expansionService \App\Service\Expansion\ExpansionService */
/** @var $expansion \App\Models\Expansion */
/** @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection */
$dungeons = isset($dungeons) ? $dungeons : $expansion->dungeons()->active()->get();
$expansion = $expansionService->getCurrentExpansion();
$colCount = 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names = isset($names) ? $names : true;
$links = isset($links) ? $links : collect();
?>


<?php for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters mt-2">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $index = $i * $colCount + $j;
    if( $dungeons->has($index) ){
        $dungeon = $dungeons->get($index);
        $link = $links->where('dungeon', $dungeon->key)->first();
        ?>
        <div class="col-md-{{ 12 / $colCount }}">
            @if($names)
                <div class="text-center font-weight-bold"> {{ __($dungeon->name) }} </div>
            @endif

            @isset($link)
                <a href="{{ __($link['link']) }}">
                    @endisset

                    <img src="images/dungeons/{{$dungeon->expansion->shortname}}/{{ $dungeon->key }}.jpg" style="width: 100%" alt="{{ __($dungeon->name) }}"/>

                    @isset($link)
                </a>
            @endisset
        </div>
        <?php
        }
    } ?>
</div>
<?php } ?>

