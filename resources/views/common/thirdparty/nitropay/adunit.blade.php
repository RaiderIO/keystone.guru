<?php
$type = isset($type) ? $type : 'responsive';
$demo = config('app.env') !== 'production' ? 'true' : 'false';

$defaultReportAdPosition = [
    'responsive'          => 'top-right',
    'responsive_small'    => 'top-right',
    'header'              => 'bottom-right',
    'footer'              => 'top-right',
    'map'                 => 'bottom-right',
    'mapsmall'            => 'bottom-right',
    'mapsmall_horizontal' => 'bottom-right',
];

$reportAdPosition = isset($reportAdPosition) ? $reportAdPosition : $defaultReportAdPosition[$type];
$random = rand(0, 1000000);
?>
<script type="text/javascript">
    if (window.nitroAds && window.nitroAds.loaded) {
        // nitroAds was already loaded
    } else {
        // wait for loaded event
        document.addEventListener('nitroAds.loaded', event => {
            // Show the remove ads button
            document.getElementById('nitropay-{{ $random }}-remove-ads').setAttribute('style', '');

            // Move the report ad button to a more convenient place
            let reportLink = document.getElementById('nitropay-{{ $random }}').getElementsByClassName('report-link')[0];
            reportLink.setAttribute('style', '');
            reportLink.setAttribute('class', 'report-link nitropay');

            let aReportLink = reportLink.children[0];
            aReportLink.style['color'] = 'unset';
            aReportLink.style['margin-top'] = '0';

            let target = document.getElementById('nitropay-{{ $random }}-report-ad');
            target.appendChild(reportLink);
        });
    }
</script>

<div>
@if(strpos($reportAdPosition, 'top') !== false)
    @include('common.thirdparty.nitropay.adcontrols', ['random' => $random])
@endif

@if( $type === 'responsive' || $type === 'responsive_small' )
    <!-- Responsive ad unit -->
        <div id="nitropay-{{ $random }}" class="ad_block_me"></div>

        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-{{ $random }}', {
                "refreshLimit": 10,
                "refreshTime": 30,
                
                "refreshVisibleOnly": true,
                "demo": {{$demo}},
                "report": {
                    "enabled": true,
                    "wording": "Report Ad",
                    "position": "{{ $reportAdPosition }}"
                }
            });
        </script>
@elseif( $type === 'header' )
    <!-- Top header ad unit -->
        <div id="nitropay-{{ $random }}" class="ad_block_me"></div>
        @if( (new \Jenssegers\Agent\Agent())->isMobile() )
            <script type="text/javascript">
                window['nitroAds'].createAd('nitropay-{{ $random }}', {
                    "refreshLimit": 10,
                    "refreshTime": 30,
                    "format": "anchor",
                    "anchor": "top",
                    "demo": {{$demo}},
                    "report": {
                        "enabled": true,
                        "wording": "Report Ad",
                        "position": "{{ $reportAdPosition }}"
                    }
                });
            </script>
        @else
            <script type="text/javascript">
                window['nitroAds'].createAd('nitropay-{{ $random }}', {
                    "refreshLimit": 10,
                    "refreshTime": 30,
                    
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
                        "position": "{{ $reportAdPosition }}"
                    }
                });
            </script>
        @endif
    @elseif( $type === 'footer' )
    <!-- Footer ad unit -->
        <div id="nitropay-{{ $random }}" class="ad_block_me"></div>

        @if( (new \Jenssegers\Agent\Agent())->isMobile() )
            <script type="text/javascript">
                window['nitroAds'].createAd('nitropay-{{ $random }}', {
                    "refreshLimit": 10,
                    "refreshTime": 30,
                    "demo": {{$demo}},
                    "format": "anchor",
                    "anchor": "bottom",
                    "report": {
                        "enabled": true,
                        "wording": "Report Ad",
                        "position": "{{ $reportAdPosition }}"
                    },
                    "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
                });
            </script>
        @else
            <script type="text/javascript">
                window['nitroAds'].createAd('nitropay-{{ $random }}', {
                    "refreshLimit": 10,
                    "refreshTime": 30,
                    
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
                        "position": "{{ $reportAdPosition }}"
                    },
                    "mediaQuery": "(min-width: 1025px), (min-width: 768px) and (max-width: 1024px)"
                });
            </script>
        @endif
    @elseif( $type === 'map' )
    <!-- Map ad unit desktop -->
        <div id="nitropay-{{ $random }}" class="ad_block_me"></div>

        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-{{ $random }}', {
                "refreshLimit": 10,
                "refreshTime": 30,
                
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
                    "position": "{{ $reportAdPosition }}"
                },
                "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
            });
        </script>
    @elseif( $type === 'mapsmall' )
    <!-- Map desktop vertical banner -->
        <div id="nitropay-{{ $random }}" class="ad_block_me"></div>

        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-{{ $random }}', {
                "refreshLimit": 10,
                "refreshTime": 30,
                
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
                    "position": "{{ $reportAdPosition }}"
                },
                "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
            });
        </script>
    @elseif( $type === 'mapsmall_horizontal' )
    <!-- Map footer mobile -->
        <div id="nitropay-{{ $random }}" class="ad_block_me"></div>

        <script type="text/javascript">
            window['nitroAds'].createAd('nitropay-{{ $random }}', {
                "refreshLimit": 10,
                "refreshTime": 30,
                
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
                    "position": "{{ $reportAdPosition }}"
                }
            });
        </script>
    @endif
</div>

@if(strpos($reportAdPosition, 'bottom') !== false)
    @include('common.thirdparty.nitropay.adcontrols', ['random' => $random])
@endif