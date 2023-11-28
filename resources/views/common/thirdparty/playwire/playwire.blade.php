<script>
    window.ramp = window.ramp || {};
    window.ramp.que = window.ramp.que || [];
</script>
<script async src="{{
    sprintf('//cdn.intergient.com/%s/%s/ramp_config.js',
        config('keystoneguru.playwire.param_1'),
        config('keystoneguru.playwire.param_2')
    ) }}"></script>
<script>
    window._pwGA4PageviewId = ''.concat(Date.now());
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () {
        dataLayer.push(arguments);
    };
    gtag('js', new Date());
    gtag('config', 'G-69YEVZW7MB', { 'send_page_view': false });
    gtag(
        'event',
        'ramp_js',
        {
            'send_to': 'G-69YEVZW7MB',
            'pageview_id': window._pwGA4PageviewId
        }
    );
</script>
