<script type="text/javascript">
    /** Tracks which nitropay ads are anchors, if an anchor is found, don't perform the below code since you can't report those ads */
    var nitropayIsAnchor = {};
    var nitropayAdLoadedEvents = {};

    window["nitroAds"] = window["nitroAds"] || {
        createAd: function () {
            window.nitroAds.queue.push(["createAd", arguments]);
        },
        queue: []
    };
</script>
<script async src="https://s.nitropay.com/ads-677.js"></script>