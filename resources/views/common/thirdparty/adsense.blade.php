<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
    (adsbygoogle = window.adsbygoogle || []).push({
        google_ad_client: "ca-pub-2985471802502246",
        enable_page_level_ads: true
        @auth
        <?php // https://support.google.com/admanager/answer/7678538 ?>
        @if(Auth::user()->adsense_no_personalized_ads)
        , requestNonPersonalizedAds: 1
        @endif
        @endauth
    });
</script>