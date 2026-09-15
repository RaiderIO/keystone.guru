class CarouselHandler {
    constructor() {

    }

    /**
     * Initialises the thumbnail carousels inside the given prefix, or in the whole page when no
     * prefix is given. Safe to call repeatedly - $.fn.thumbnailCarousel (#3595) skips carousels it
     * has already taken over, which is what the discover page relies on when it re-runs this over
     * each batch of infinite-scroll results.
     *
     * @param {string} prefix
     * @param {Object} settingsOverride
     */
    refreshCarousel(prefix = '', settingsOverride = {}) {
        // Only perform this when the page is actually fully loaded, so carousels that are part of
        // the initial server-rendered markup are matched.
        $(function () {
            $(`${prefix} .thumbnail-carousel__track`).thumbnailCarousel($.extend({}, {
                label: lang.get('js.thumbnail_carousel_label'),
                prevLabel: lang.get('js.thumbnail_carousel_previous'),
                nextLabel: lang.get('js.thumbnail_carousel_next'),
            }, settingsOverride));
        });
    }
}
