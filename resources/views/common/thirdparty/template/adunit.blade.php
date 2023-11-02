<?php
/** @var boolean $isMobile */
/** @var string $id */
/** @var string $type */
/** @var bool $map */

$id   = 'myprovider-' . $id;
$type = $type ?? 'responsive';
$map  = $map ?? false;
$demo = config('app.env') !== 'production' ? 'true' : 'false';
?>

@if( $type === 'responsive' )

@elseif( $type === 'header' )
    @if( $isMobile )

    @else

    @endif
@elseif( $type === 'footer' )
    @if( $isMobile )

    @else

    @endif
@elseif($map && !$isMobile)
    @if( $type === 'footer_map_right' )

    @elseif( $type === 'sidebar_map_right' )

    @endif
@endif
