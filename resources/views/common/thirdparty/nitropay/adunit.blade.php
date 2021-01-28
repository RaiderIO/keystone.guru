<?php
$type = isset($type) ? $type : 'responsive';
$demo = config('app.env') !== 'production' ? 'true' : 'false';
?>

@if( $type === 'responsive' || $type === 'responsive_small' )
    @php($random = rand(0, 1000000))
    <!-- Responsive ad unit -->
    <div id="nitropay-responsive-unit-{{ $random }}"></div>

    <script type="text/javascript">
        window['nitroAds'].createAd('nitropay-responsive-unit-{{ $random }}', {
            "refreshLimit": 10,
            "refreshTime": 90,
            "renderVisibleOnly": true,
            "refreshVisibleOnly": true,
            "demo": {{$demo}},
            "report": {
                "enabled": true,
                "wording": "Report Ad",
                "position": "top-right"
            }
        });
    </script>
@elseif( $type === 'header' )
    <!-- Top header ad unit -->
    <div id="nitropay-header-unit"></div>

    @if( (new \Jenssegers\Agent\Agent())->isMobile() )
        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-header-unit', {
                "refreshLimit": 10,
                "refreshTime": 90,
                "format": "anchor",
                "anchor": "top",
                "demo": {{$demo}},
                "report": {
                    "enabled": true,
                    "wording": "Report Ad",
                    "position": "bottom-right"
                }
            });
        </script>
    @else
        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-header-unit', {
                "refreshLimit": 10,
                "refreshTime": 90,
                "renderVisibleOnly": true,
                "refreshVisibleOnly": true,
                "demo": {{$demo}},
                "sizes": [
                    [
                        "970",
                        "90"
                    ]
                ],
                "report": {
                    "enabled": true,
                    "wording": "Report Ad",
                    "position": "bottom-right"
                }
            });
        </script>
    @endif
@elseif( $type === 'footer' )
    <!-- Footer ad unit -->
    <div id="nitropay-footer-unit"></div>

    @if( (new \Jenssegers\Agent\Agent())->isMobile() )
        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-footer-unit', {
                "refreshLimit": 10,
                "refreshTime": 90,
                "demo": {{$demo}},
                "format": "anchor",
                "anchor": "bottom",
                "report": {
                    "enabled": true,
                    "wording": "Report Ad",
                    "position": "top-right"
                },
                "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
            });
        </script>
    @else
        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-footer-unit', {
                "refreshLimit": 10,
                "refreshTime": 90,
                "renderVisibleOnly": true,
                "refreshVisibleOnly": true,
                "demo": {{$demo}},
                "sizes": [
                    [
                        "970",
                        "250"
                    ]
                ],
                "report": {
                    "enabled": true,
                    "wording": "Report Ad",
                    "position": "top-right"
                },
                "mediaQuery": "(min-width: 1025px), (min-width: 768px) and (max-width: 1024px)"
            });
        </script>
    @endif
@elseif( $type === 'map' )
    <!-- Map ad unit desktop -->
    <div id="nitropay-map-unit"></div>

    <script type="text/javascript">
        window['nitroAds'].createAd('nitropay-map-unit', {
            "refreshLimit": 10,
            "refreshTime": 90,
            "renderVisibleOnly": true,
            "refreshVisibleOnly": true,
            "demo": {{$demo}},
            "sizes": [
                [
                    "160",
                    "600"
                ]
            ],
            "report": {
                "enabled": true,
                "wording": "Report Ad",
                "position": "bottom-right"
            },
            "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
        });
    </script>
@elseif( $type === 'mapsmall' )
    <!-- Map desktop vertical banner -->
    <div id="nitropay-map-small-unit"></div>

    <script type="text/javascript">
        window['nitroAds'].createAd('nitropay-map-small-unit', {
            "refreshLimit": 10,
            "refreshTime": 90,
            "renderVisibleOnly": true,
            "refreshVisibleOnly": true,
            "demo": {{$demo}},
            "sizes": [
                [
                    "300",
                    "250"
                ]
            ],
            "report": {
                "enabled": true,
                "wording": "Report Ad",
                "position": "bottom-right"
            },
            "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
        });
    </script>
@elseif( $type === 'mapsmall_horizontal' )
    <!-- Map footer mobile -->
    <div id="nitropay-map-small-horizontal-unit"></div>

    <script type="text/javascript">
        window['nitroAds'].createAd('nitropay-map-small-horizontal-unit', {
            "refreshLimit": 10,
            "refreshTime": 90,
            "renderVisibleOnly": true,
            "refreshVisibleOnly": true,
            "demo": {{$demo}},
            "sizes": [
                [
                    "320",
                    "50"
                ]
            ],
            "report": {
                "enabled": true,
                "wording": "Report Ad",
                "position": "bottom-right"
            }
        });
    </script>
@endif