<?php
/** @var $expansionService \App\Service\Expansion\ExpansionService */
/** @var $expansion \App\Models\Expansion */
/** @var $dungeons \App\Models\Dungeon[]|\Illuminate\Support\Collection */
$colCount = 4;
$rowCount = (int)ceil($dungeons->count() / $colCount);

$names = isset($names) ? $names : true;
$links = isset($links) ? $links : collect();

for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $index = $i * $colCount + $j;
    if( $dungeons->has($index) ){
    $dungeon = $dungeons->get($index);
    $link = $links->where('dungeon', $dungeon->key)->first();
    ?>
    <div class="p-2 col-md-{{ 12 / $colCount }}">
        <div class="card">
            <a href="{{ route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon]) }}">
                <img class="card-img-top"
                     src="images/dungeons/{{$dungeon->expansion->shortname}}/{{ $dungeon->key }}.jpg"
                     style="width: 100%" alt="{{ __($dungeon->name) }}"/>
            </a>
            @if($names)
                <div class="card-body">
                    <p class="card-text text-center">
                        <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon]) }}">
                            {{ __('Popular') }}
                        </a>
                        &middot;
                        <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon]) }}">
                            {{ __('This week') }}
                        </a>
                        &middot;
                        <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon]) }}">
                            {{ __('Next week') }}
                        </a>
                        &middot;
                        <a href="{{ route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon]) }}">
                            {{ __('New') }}
                        </a>
                    </p>
                </div>
            @endif
        </div>
    </div>
    <?php
    }
    } ?>
</div>
<?php } ?>

