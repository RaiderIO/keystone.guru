<?php
/** @var $expansionService \App\Service\Expansion\ExpansionService */
/** @var $expansion \App\Models\Expansion */
$expansion = $expansionService->getCurrentExpansion();
$rowCount = 4;
$colCount = $expansion->dungeons()->active()->count() / $rowCount;

$names = isset($names) ? $names : true;
$links = isset($links) ? $links : collect();
?>


<?php for( $i = 0; $i < $rowCount; $i++ ) { ?>
<div class="row no-gutters mt-2">
    <?php for( $j = 0; $j < $colCount; $j++ ) {
    $dungeon = $expansion->dungeons->get($i * $colCount + $j);
    $link = $links->where('dungeon', $dungeon->key)->first();
    ?>
    <div class="col-md-4">
        @if($names)
            <div class="text-center font-weight-bold"> {{ __($dungeon->name) }} </div>
        @endif

        @isset($link)
            <a href="{{ __($link['link']) }}">
        @endisset

        <img src="images/dungeons/{{ $dungeon->key }}.jpg" style="width: 100%" alt="{{ __($dungeon->name) }}"/>

        @isset($link)
            </a>
        @endisset
    </div>
    <?php } ?>
</div>
<?php } ?>

