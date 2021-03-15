<?php
/** @var $expansionService \App\Service\Expansion\ExpansionService */
/** @var $expansion \App\Models\Expansion */
/** @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection */
$dungeons = isset($dungeons) ? $dungeons : $expansion->dungeons;
$colCount = 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names = isset($names) ? $names : true;
$links = isset($links) ? $links : collect();
?>


<?php for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $index = $i * $colCount + $j;
    if( $dungeons->has($index) ){
    $dungeon = $dungeons->get($index);
    $link = $links->where('dungeon', $dungeon->key)->first();
    ?>
    <div class="col-lg-{{ 12 / $colCount }} col-{{ 12 / ($colCount / 2) }} p-2">
        <div class="card-img-caption">
            @if($names)
                <h5 class="card-text text-white">
                    {{ $dungeon->name }}
                </h5>
            @endif

            @isset($link)
                <a href="{{ __($link['link']) }}">
                    @endisset

                    <img class="card-img-top"
                         src="images/dungeons/{{$dungeon->expansion->shortname}}/{{ $dungeon->key }}.jpg"
                         style="width: 100%" alt="{{ __($dungeon->name) }}"/>

                    @isset($link)
                </a>
            @endisset
        </div>
    </div>
    <?php
    }
    } ?>
</div>
<?php } ?>

