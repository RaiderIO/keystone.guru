class SearchHandler {
    constructor() {

    }

    /**
     *
     * @param searchParams {SearchParams}
     * @param $targetContainer {jQuery}
     */
    search(searchParams, $targetContainer) {
        console.assert(this instanceof SearchHandler, 'this was not a SearchHandler', this);
        console.assert(searchParams instanceof SearchParams, 'searchParams was not a SearchParams', searchParams);

        $.ajax({
            type: 'GET',
            url: `/ajax/search`,
            dataType: 'html',
            data: searchParams.toObject(),
            beforeSend: function () {
                $('#route_list_overlay').show();
                // $('#save_pull_settings_saving').show();
            },
            success: function (html) {
                if (searchParams.offset === 0) {
                    $targetContainer.empty();
                }

                // Wrap the result in a container so that we can selectively refresh things
                let containerClass = `search_result_${searchParams.offset}`;
                let $container = $('<div/>').addClass(containerClass).append(html);

                $targetContainer.append($container);

                // Do init of html
                // @TODO make settings come from same source as discover.js?
                $(`.${containerClass} .owl-carousel`).owlCarousel({
                    nav: false,
                    loop: true,
                    dots: false,
                    lazyLoad: true,
                    lazyLoadEager: 1,
                    items: 1
                });

                // Init the affix popovers
                $(`.${containerClass} [data-toggle="popover"]`).popover();
            },
            complete: function () {
                $('#route_list_overlay').hide();
                // $('#save_pull_settings').show();
                // $('#save_pull_settings_saving').hide();
            }
        });
    }
}