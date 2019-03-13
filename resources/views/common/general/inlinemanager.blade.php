<script>
    /** Instance that handles all inline code for specific pages */
    let _inlineManager;

    document.addEventListener("DOMContentLoaded", function () {
        _inlineManager = new InlineManager();
    });

    /**
     * Checks if the current user is on a mobile device or not.
     * @TODO This should go somewhere else?
     **/
    function isMobile() {
        return {{ (new \Jenssegers\Agent\Agent())->isMobile() ? 'true' : 'false' }};
    }
</script>