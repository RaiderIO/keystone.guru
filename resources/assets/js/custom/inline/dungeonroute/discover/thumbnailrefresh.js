class ThumbnailRefresh {

    constructor(selector = '.thumbnail_refresh') {
        this.selector = selector;
    }

    /**
     * Refreshes the click handler on the 'refresh thumbnail' button
     */
    refreshHandlers() {
        $(this.selector).unbind('click').bind('click', function () {
            let $this = $(this);

            $.ajax({
                type: 'POST',
                url: `/ajax/admin/thumbnail/${$this.data('publickey')}/refresh`,
                success: function (json) {
                    showSuccessNotification(lang.get('js.dungeonroute_refresh_thumbnail_success'));
                }
            });
        });
    }
}
