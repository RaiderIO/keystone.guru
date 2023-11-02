<?php
/** @var boolean $isMobile */
/** @var string $id */
/** @var string $type */
/** @var bool $map */

$id   = 'nitropay-' . $id;
$type = $type ?? 'responsive';
$map  = $map ?? false;
$demo = config('app.env') !== 'production' ? 'true' : 'false';

$defaultReportAdPosition = [
    'responsive'       => 'top-right',
    'header'           => 'bottom-right',
    'footer'           => 'top-right',
    'footer_map_right' => 'top-right',
    'sidebar_map_right' => 'top-right',
];

$reportAdPosition = $reportAdPosition ?? $defaultReportAdPosition[$type];

// If we're on mobile, anchor ads do not have a report button so don't render it
$hasAdControls = !($isMobile && ($type === 'header' || $type === 'footer'));

if ($isMobile) {
    $id .= '_mobile';
}
?>
<script type="text/javascript">
    nitropayAdRenderedEvents['{{ $id }}'] = (event, count = 0) => {
        let adId = '{{ $id }}';

        console.log(`Loaded ${adId}`);

        // Fail-safe just in case
        if (count > 5) {
            // console.log(`Broke out of too much checking for ${adId}`);
            return;
        }

        if (!nitropayIsAnchor.hasOwnProperty(adId) || !nitropayIsAnchor[adId]) {
            // Move the report ad button to a more convenient place
            let reportLink = document.getElementById(adId).getElementsByClassName('report-link')[0];

            if (typeof reportLink !== 'undefined') {
                // Show the remove ads button
                document.getElementById(`${adId}-remove-ads`).setAttribute('style', '');

                reportLink.setAttribute('style', '');
                reportLink.setAttribute('class', 'report-link nitropay');

                let aReportLink = reportLink.children[0];
                aReportLink.style['color'] = 'unset';
                aReportLink.style['margin-top'] = '0';

                let target = document.getElementById(`${adId}-report-ad`);
                // Clear any previously set report links
                target.innerHTML = '';
                target.appendChild(reportLink);

                @if($map)
                // Add a css class to the pulls sidebar so that we know the ads have loaded and its height can be adjusted accordingly
                // The height will stay normal if an adblocker is enabled as a result
                let pullsSidebar = document.getElementById(`pulls_sidebar`);
                if( pullsSidebar !== null ) {
                    let existingClasses = pullsSidebar.getAttribute('class');
                    if (!existingClasses.includes('ad_loaded')) {
                        pullsSidebar.setAttribute('class', `${existingClasses} ad_loaded`);
                    }
                } else {
                    console.log('Cannot find pulls sidebar! Is it enabled?');
                }
                @endif
            } else {
                setTimeout(nitropayAdRenderedEvents[adId], 200, event, ++count);
            }
        }
    };

    if (window.nitroAds && window.nitroAds.loaded) {
        // nitroAds was already loaded
    } else {
        // wait for loaded event
        document.addEventListener('nitroAds.rendered', nitropayAdRenderedEvents['{{ $id }}']);
    }
</script>

<div>
    @if($hasAdControls && strpos($reportAdPosition, 'top') !== false)
        @include('common.thirdparty.nitropay.adcontrols', ['id' => $id])
    @endif

    @if( $type === 'responsive' )
        <!-- Responsive ad unit -->
        <div id="{{ $id }}" class="ad_block_me"></div>

        <script type="text/javascript">
            window['nitroAds'].createAd('{{ $id }}', {
                "refreshLimit": 20,
                "refreshTime": 60,
                "renderVisibleOnly": true,
                "refreshVisibleOnly": true,
                "demo": {{$demo}},
                "report": {
                    "enabled": true,
                    "wording": "Report Ad",
                    "position": "{{ $reportAdPosition }}"
                }
            });
        </script>
    @elseif( $type === 'header' || $type === 'header_middle' )
        <!-- Top header ad unit -->
        <div id="{{ $id }}" class="ad_block_me"
             @if(!$isMobile)
                 style="min-height: 90px;"
                @endif
        ></div>
        @if( $isMobile )
            <script type="text/javascript">
                window['nitroAds'].createAd('{{ $id }}', {
                    "refreshLimit": 20,
                    "refreshTime": 60,
                    "format": "anchor",
                    "anchor": "top",
                    "demo": {{$demo}},
                    "report": {
                        "enabled": true,
                        "wording": "Report Ad",
                        "position": "{{ $reportAdPosition }}"
                    },
                    "mediaQuery": "(min-width: 320px) and (max-width: 767px)"
                });
                nitropayIsAnchor['{{ $id }}'] = true;
            </script>
        @else
            <script type="text/javascript">
                window['nitroAds'].createAd('{{ $id }}', {
                    "refreshLimit": 20,
                    "refreshTime": 60,
                    "renderVisibleOnly": true,
                    "refreshVisibleOnly": true,
                    "demo": {{$demo}},
                    "sizes": [
                        [
                            "{{ isset($map) && $map ? 728 : 970}}",
                            "90"
                        ]
                    ],
                    "report": {
                        "enabled": true,
                        "wording": "Report Ad",
                        "position": "{{ $id }}"
                    },
                    "mediaQuery": "(min-width: 1025px), (min-width: 768px) and (max-width: 1024px)"
                });
            </script>
        @endif
    @elseif( $type === 'footer' )
        <!-- Footer ad unit -->
        @php($height = isset($map) && $map ? 90 : 250)
        <div id="{{ $id }}" class="ad_block_me"
             @if(!$isMobile)
                 style="min-height: {{ $height }}px;"
                @endif
        ></div>

        @if( $isMobile )
            <script type="text/javascript">
                window['nitroAds'].createAd('{{ $id }}', {
                    "refreshLimit": 20,
                    "refreshTime": 60,
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
                nitropayIsAnchor['{{ $id }}'] = true;
            </script>
        @else
            <script type="text/javascript">
                window['nitroAds'].createAd('{{ $id }}', {
                    "refreshLimit": 20,
                    "refreshTime": 60,

                    "refreshVisibleOnly": true,
                    "demo": {{$demo}},
                    "sizes": [
                        [
                            "{{ isset($map) && $map ? 728 : 970 }}",
                            "{{ $height }}"
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
    @elseif($map && !$isMobile)
        @if( $type === 'footer_map_right' )
            <!-- Footer ad unit -->
            <div id="{{ $id }}" class="ad_block_me"></div>

            <script type="text/javascript">
                nitropayAdSizes['{{ $id }}'] = window.innerHeight > 1000 ? ['336', '280'] : ['300', '250'];
                window['nitroAds'].createAd('{{ $id }}', {
                    "refreshLimit": 20,
                    "refreshTime": 60,
                    "renderVisibleOnly": false,
                    "refreshVisibleOnly": true,
                    "demo": {{$demo}},
                    "sizes": [
                        nitropayAdSizes['{{ $id }}']
                    ],
                    "report": {
                        "enabled": true,
                        "wording": "Report Ad",
                        "position": "{{ $reportAdPosition }}"
                    },
                    "mediaQuery": "(min-width: 1025px)"
                });

                // Bit of a hack to make the bigger ad fit properly
                if (window.innerHeight > 1000) {
                    var adContainer = document.getElementsByClassName('map_ad_unit_footer_right')[0];
                    adContainer.style = 'width: 336px !important';
                }
            </script>
        @elseif( $type === 'sidebar_map_right' )
            <!-- Footer ad unit -->
            <div id="{{ $id }}" class="ad_block_me"></div>

            <script type="text/javascript">
                nitropayAdSizes['{{ $id }}'] = window.innerWidth > 1920 ? ['300', '600'] : ['160', '600'];
                window['nitroAds'].createAd('{{ $id }}', {
                    "refreshLimit": 20,
                    "refreshTime": 60,
                    "renderVisibleOnly": false,
                    "refreshVisibleOnly": true,
                    "demo": {{$demo}},
                    "sizes": [
                        nitropayAdSizes['{{ $id }}']
                    ],
                    "report": {
                        "enabled": true,
                        "wording": "Report Ad",
                        "position": "{{ $reportAdPosition }}"
                    },
                    "mediaQuery": "(min-width: 1025px)"
                });

                // Bit of a hack to make the bigger ad fit properly
                if (window.innerWidth > 1920) {
                    var adContainer = document.getElementsByClassName('map_ad_unit_sidebar_right')[0];
                    adContainer.style = 'width: 300px !important';
                }
            </script>
        @endif
    @endif
</div>

@if($hasAdControls && strpos($reportAdPosition, 'bottom') !== false)
    @include('common.thirdparty.nitropay.adcontrols', ['id' => $id])
@endif
