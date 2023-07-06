<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-35712BBJWC"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    @auth
    <?php // https://developers.google.com/analytics/devguides/collection/gtagjs/display-features ?>
    @if( Auth::user()->analytics_cookie_opt_out )
        gtag('set', 'allow_ad_personalization_signals', false);
    @endif
    @endauth
    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());

    gtag('config', 'G-35712BBJWC');
</script>
