<?php
/** @var boolean $isMobile */
/** @var string $id */
/** @var string $type */
/** @var bool $map */

$id   = 'playwire-' . $id;
$type ??= 'responsive';
$map  ??= false;
$demo = config('app.env') !== 'production' ? 'true' : 'false';

// A bit of a hacky solution but it'll work
$GLOBALS['playwireBTFCount'] = 0;
?>
@if( $type === 'responsive' )

@elseif( $type === 'header' )
    @if( $isMobile )
        <!--  [Mobile]leaderboard_atf -->
        <div data-pw-mobi="leaderboard_atf" id="pwMobiLbAtf"></div>
        <script type="text/javascript">
            window.ramp.que.push(function () {
                window.ramp.addTag("pwMobiLbAtf");
            })
        </script>
    @else
        <!--  [Desktop/Tablet]leaderboard_atf -->
        <div data-pw-desk="leaderboard_atf" id="pwDeskLbAtf"></div>
        <script type="text/javascript">
            window.ramp.que.push(function () {
                window.ramp.addTag("pwDeskLbAtf");
            })
        </script>
    @endif

@elseif( $type === 'header_middle' || $type === 'footer' )
    @php($GLOBALS['playwireBTFCount']++)

    @if( $isMobile )
        <!--  [Mobile]leaderboard_atf -->
        <!--  [Mobile]leaderboard_btf -->
        <div data-pw-mobi="leaderboard_btf" id="pwMobiLbBtf{{ $GLOBALS['playwireBTFCount'] }}"></div>
        <script type="text/javascript">
            window.ramp.que.push(function () {
                window.ramp.addTag("pwMobiLbBtf{{ $GLOBALS['playwireBTFCount'] }}");
            })
        </script>
    @else
        <!--  [Desktop/Tablet]leaderboard_btf -->
        <div data-pw-desk="leaderboard_btf" id="pwDeskLbBtf{{ $GLOBALS['playwireBTFCount'] }}"></div>
        <script type="text/javascript">
            window.ramp.que.push(function () {
                window.ramp.addTag("pwDeskLbBtf{{ $GLOBALS['playwireBTFCount'] }}");
            })
        </script>
    @endif
@elseif($map && !$isMobile)
    @if( $type === 'footer_map_right' )
        <!--  [Desktop/Tablet]med_rect_atf -->
        <div data-pw-desk="med_rect_atf" id="pwDeskMedRectAtf"></div>
        <script type="text/javascript">
            window.ramp.que.push(function () {
                window.ramp.addTag("pwDeskMedRectAtf");
            })
        </script>
    @elseif( $type === 'sidebar_map_right' )
        <!--  [Desktop/Tablet]sky_atf -->
        <div data-pw-desk="sky_atf" id="pwDeskSkyAtf"></div>
        <script type="text/javascript">
            window.ramp.que.push(function () {
                window.ramp.addTag("pwDeskSkyAtf");
            })
        </script>
    @endif
@endif
