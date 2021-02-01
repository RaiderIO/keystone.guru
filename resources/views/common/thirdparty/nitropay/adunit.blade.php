<?php
$type = isset($type) ? $type : 'responsive';
$demo = config('app.env') !== 'production' ? 'true' : 'false';

$defaultReportAdPosition = [
    'responsive' => 'top-right',
    'header' => 'bottom-right',
    'footer' => 'top-right',
];

$reportAdPosition = isset($reportAdPosition) ? $reportAdPosition : $defaultReportAdPosition[$type];
$random = rand(0, 1000000);

$isMobile = (new \Jenssegers\Agent\Agent())->isMobile();

// If we're on mobile, anchor ads do not have a report button so don't render it
$hasAdControls = !($isMobile && ($type === 'header' || $type === 'footer'));
?>
<script type="text/javascript">
    /** Tracks which nitropay ads are anchors, if an anchor is found, don't perform the below code since you can't report those ads */
    var nitropayIsAnchor = {};

    if (window.nitroAds && window.nitroAds.loaded) {
        // nitroAds was already loaded
    } else {
        // wait for loaded event
        document.addEventListener('nitroAds.loaded', event => {
            let adId = '{{ $random }}';
            if (!nitropayIsAnchor.hasOwnProperty(adId) || (!nitropayIsAnchor[adId])) {
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
            }
        });
    }
</script>

<div>
@if($hasAdControls && strpos($reportAdPosition, 'top') !== false)
    @include('common.thirdparty.nitropay.adcontrols', ['random' => $random])
@endif

@if( $type === 'responsive' )
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
        @if( $isMobile )
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
                nitropayIsAnchor['{{ $random }}'] = true;
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
                            "{{ isset($map) && $map ? '728' : '970'}}",
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

        @if( $isMobile )
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
                nitropayIsAnchor['{{ $random }}'] = true;
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
    @endif
</div>

@if($hasAdControls && strpos($reportAdPosition, 'bottom') !== false)
    @include('common.thirdparty.nitropay.adcontrols', ['random' => $random])
@endif