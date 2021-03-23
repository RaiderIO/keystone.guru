class SearchHandler {
    constructor() {

    }

    /**
     *
     * @protected
     */
    getSearchUrl() {
        return `/ajax/search`;
    }

    /**
     *
     * @param $targetContainer {jQuery}
     * @param searchParams {SearchParams}
     * @param options {{}}
     */
    search($targetContainer, searchParams = null, options = {}) {
        console.assert(this instanceof SearchHandler, 'this was not a SearchHandler', this);
        console.assert(searchParams instanceof SearchParams, 'searchParams was not null or a SearchParams', searchParams);

        $.ajax({
            type: 'GET',
            url: this.getSearchUrl(),
            dataType: 'html',
            data: searchParams.params,
            beforeSend: function () {
                $('#route_list_overlay').show();
                // $('#save_pull_settings_saving').show();

                if( options.hasOwnProperty('beforeSend') ) {
                    options.beforeSend();
                }
            },
            success: function (html) {
                if (searchParams.offset === 0) {
                    $targetContainer.empty();
                }

                // Wrap the result in a container so that we can selectively refresh things
                let containerClass = `search_result_${searchParams.offset}`;
                let $container = $('<div/>').addClass(containerClass).append(html);

                $targetContainer.append($container);
                // Do init of html below here

                // For some reason doing this immediately will cause the carousel to not load properly, maybe because the image is not rendered yet?
                // This delay causes it to be rendered OK
                (new CarouselHandler()).refreshCarousel(`.${containerClass}`);

                // Init the affix popovers
                $(`.${containerClass} [data-toggle="popover"]`).popover();
                refreshTooltips();

                if( options.hasOwnProperty('success') ) {
                    options.success();
                }
            },
            complete: function () {
                $('#route_list_overlay').hide();
                // $('#save_pull_settings').show();
                // $('#save_pull_settings_saving').hide();

                if( options.hasOwnProperty('complete') ) {
                    options.complete();
                }
            }
        });
    }
}