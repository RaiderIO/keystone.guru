class SearchHandlerDungeonRoute extends SearchHandler {
    constructor(targetContainerSelector, loadMoreSelector, options) {
        super(options);

        let self = this;

        this.targetContainerSelector = targetContainerSelector;
        this.loadMoreSelector = loadMoreSelector;

        this.offset = 0;
        this.limit = typeof options.limit !== 'undefined' ? options.limit : 10;
        this.hasMore = true;
        this.loading = false;
        /** {SearchParams} */
        this.previousSearchParams = null;

        $(window).on('resize scroll', function () {
            let inViewport = $(self.loadMoreSelector).isInViewport();

            if (!self.loading && inViewport && self.hasMore) {
                self.searchMore();
            }
        });
    }


    /**
     *
     * @protected
     */
    getSearchUrl() {
        console.assert(this instanceof SearchHandlerDungeonRoute, 'this is not a SearchHandlerDungeonRoute', this);
        return `/ajax/search`;
    }

    /**
     * @param searchParams {SearchParams}
     * @param options {{}}
     */
    search(searchParams, options = {}) {
        console.assert(this instanceof SearchHandlerDungeonRoute, 'this is not a SearchHandlerDungeonRoute', this);
        let self = this;

        let extendedOptions = $.extend({}, options, {
            success: function (html, textStatus, xhr) {
                self.applySearchResultToContainer(searchParams, html);

                // Only if we actually get results back
                self.hasMore = xhr.status !== 204;
                if (self.hasMore) {
                    // Increase the offset so that we load new rows whenever we fetch more
                    self.offset += self.limit;
                }
            },
        });

        // New searches cause the "search more" offset to reset to 0
        if (typeof extendedOptions.resetOffset === 'undefined' || extendedOptions.resetOffset) {
            this.hasMore = true;
            this.offset = 0;
        }

        searchParams.addQueryParameters({
            offset: this.offset,
            limit: this.limit
        });

        super.search(searchParams, extendedOptions);

        this.previousSearchParams = searchParams;
    }

    searchMore() {
        console.assert(this instanceof SearchHandlerDungeonRoute, 'this is not a SearchHandlerDungeonRoute', this);
        this.search(this.previousSearchParams ?? new SearchParams([]), $.extend({}, {
            resetOffset: false
        }, this.options));
    }

    applySearchResultToContainer(searchParams, html) {
        console.assert(this instanceof SearchHandlerDungeonRoute, 'this is not a SearchHandlerDungeonRoute', this);

        let $targetContainer = $(this.targetContainerSelector);
        if (searchParams.params.offset === 0) {
            $targetContainer.empty();
        }

        // Wrap the result in a container so that we can selectively refresh things
        let containerClass = `search_result_${searchParams.params.offset}`;
        let $container = $('<div/>').addClass(containerClass).append(html);

        $targetContainer.append($container);
        // Do init of html below here

        // For some reason doing this immediately will cause the carousel to not load properly, maybe because the image is not rendered yet?
        // This delay causes it to be rendered OK
        (new CarouselHandler()).refreshCarousel(`.${containerClass}`);
        (new ThumbnailRefresh()).refreshHandlers();

        // Init the affix popovers
        $(`.${containerClass} [data-toggle="popover"]`).popover();
        refreshTooltips();
    }
}
