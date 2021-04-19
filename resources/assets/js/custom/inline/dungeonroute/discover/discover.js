class DungeonrouteDiscoverDiscover extends InlineCode {

    /**
     */
    activate() {
        super.activate();
        (new CarouselHandler()).refreshCarousel();

        $('[data-toggle="popover"]').popover();

        $('.refresh_thumbnail').bind('click', function () {
            let $this = $(this);

            let $refresh = $this.find('.refresh');
            let $loader = $this.find('.loader');

            $.ajax({
                type: 'POST',
                url: `ajax/admin/thumbnail/${$this.data('publickey')}/refresh`,
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

    cleanup() {
    }
}