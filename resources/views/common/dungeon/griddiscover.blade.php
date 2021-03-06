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
            <a href="{{ route('dungeonroutes.discoverdungeon', ['dungeon' => $dungeon->getSlug()]) }}">
                <img class="card-img-top"
                     src="images/dungeons/{{$dungeon->expansion->shortname}}/{{ $dungeon->key }}.jpg"
                     style="width: 100%; height: 100%" alt="{{ __($dungeon->name) }}"/>
                <h5 class="card-img-overlay text-center text-white">
                    {{ $dungeon->name }}
                </h5>
            </a>
            @if($names)
                <div class="card-body">
                    <!-- Normal big screen view -->
                    <div class="d-xl-inline d-none">
                        <p class="card-text text-center">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('Popular') }}
                            </a>
                            &middot;
                            <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('This week') }}
                            </a>
                            &middot;
                            <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('Next week') }}
                            </a>
                            &middot;
                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('New') }}
                            </a>
                        </p>
                    </div>

                    <!-- Mobile view -->
                    <div class="row no-gutters card-text text-center d-xl-none">
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.popular', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('Popular') }}
                            </a>
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.thisweek', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('This week') }}
                            </a>
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.nextweek', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('Next week') }}
                            </a>
                        </div>
                        <div class="col-xl">
                            <a href="{{ route('dungeonroutes.discoverdungeon.new', ['dungeon' => $dungeon->getSlug()]) }}">
                                {{ __('New') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <?php
    }
    } ?>
</div>
<?php } ?>

