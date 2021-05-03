class ThumbnailRefresh {

    /**
     * Refreshes the click handler on the 'refresh thumbnail' button
     */
    refreshHandlers() {
        $('.refresh_thumbnail').unbind('click').bind('click', function () {
            let $this = $(this);

            let $refresh = $this.find('.refresh');
            let $loader = $this.find('.loader');

            $.ajax({
                type: 'POST',
                url: `/ajax/admin/thumbnail/${$this.data('publickey')}/refresh`,
                beforeSend: function () {
                    $refresh.hide();
                    $loader.show();
                },
                success: function (json) {
                    showSuccessNotification(lang.get('messages.dungeonroute_refresh_thumbnail_success'));
                },
                complete: function () {
                    $refresh.show();
                    $loader.hide();
                }
            });
        });
    }
}