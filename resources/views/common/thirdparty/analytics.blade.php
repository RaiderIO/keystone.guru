<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-127106035-1"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    @auth
    <?php // https://stackoverflow.com/questions/10668292/is-there-a-setting-on-google-analytics-to-suppress-use-of-cookies-for-users-who ?>
        @if( Auth::user()->analytics_cookie_opt_out )
        window['ga-disable-UA-127106035-1'] = true;
    @endif
    @endauth
    function gtag() {
        dataLayer.push(arguments);
    }

    gtag('js', new Date());

    gtag('config', 'UA-127106035-1');
</script>
