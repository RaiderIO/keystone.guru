<?php
/** @var $rating int */
/** @var $count int */
?>
<span title="{{ sprintf(__('views/common.dungeonroute.rating.nr_of_votes'), $count) }}" data-toggle="tooltip">
    <?php for($i = 1; $i <= 5; $i++) { ?>
    @if( $rating === ($i * 2) - 1 )
        <i class="fas fa-star-half-alt"></i>
    @elseif( $rating >= $i * 2 )
        <i class="fas fa-star"></i>
    @else
        <i class="far fa-star"></i>
    @endif
    <?php } ?>
</span>