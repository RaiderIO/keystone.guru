class ThumbnailRefresh {

    /**
     * Refreshes the click handler on the 'refresh thumbnail' button
     */
    refreshHandlers() {
        $('.refresh_thumbnail').unbind('click').bind('click', function () {
            let $this = $(this);

            $.ajax({
                type: 'POST',
                url: `/ajax/admin/thumbnail/${$this.data('publickey')}/refresh`,
                success: function (json) {
                    showSuccessNotification(lang.get('messages.dungeonroute_refresh_thumbnail_success'));
                }
            });
        });
    }
}