// ---------------------------------------------------------------------------
// $.fn.starRating (#3593)
//
// Self-owned replacement for the unmaintained `jquery-bar-rating` npm plugin,
// which supplied exactly one widget: the 1-10 star rating built from the
// <select id="rating_select"> in the "conclude live session" modal. This is a
// tiny reimplementation of just the API surface the app actually used:
//
//     $('#rating_select').starRating({ onSelect: fn });
//
// It hides the <select>, renders one clickable star per <option>, reflects the
// currently-selected option, highlights on hover, and on click writes the value
// back to the <select> and calls onSelect(value) (a string, as jquery-bar-rating
// did). The star visuals live in resources/assets/css/lib/star-rating.css.
// ---------------------------------------------------------------------------

const $ = require('jquery');

/**
 * Turns each matched <select> into a clickable star-rating widget.
 *
 * @param {{onSelect?: function(string): void}} [options]
 * @returns {jQuery}
 */
$.fn.starRating = function (options) {
    const settings = $.extend({onSelect: function () {}}, options);

    return this.each(function () {
        const select = this;

        // Only <select> elements, and never initialise the same one twice.
        if (select.tagName !== 'SELECT' || select.dataset.starRating === 'true') {
            return;
        }
        select.dataset.starRating = 'true';

        const $select = $(select);
        const $root = $('<span>', {'class': 'star-rating'});
        const $widget = $('<span>', {'class': 'star-rating__widget', role: 'radiogroup'});

        const stars = $select.find('option').toArray().map(function (option, index) {
            return $('<a>', {
                href: '#',
                'class': 'star-rating__star',
                role: 'radio',
                tabindex: -1,
                'data-rating-index': index,
                'aria-label': (option.textContent || '').trim() || option.value,
            })[0];
        });

        $widget.append(stars);
        $root.append($widget);
        $select.hide().after($root);

        // Index of the currently-selected option. The browser reports 0 when the
        // blade renders no `selected` option (the user has no rating yet), which
        // preserves jquery-bar-rating's original "first star lit" behaviour.
        let currentIndex = select.selectedIndex;

        /**
         * Repaints the stars: hovering shows a transient "--active" preview up to
         * the hovered star, otherwise the persisted "--selected" state is shown.
         *
         * @param {number} upToIndex
         * @param {boolean} hovering
         */
        function paint(upToIndex, hovering) {
            stars.forEach(function (star, index) {
                const filled = index <= upToIndex;
                star.classList.toggle('star-rating__star--active', hovering && filled);
                star.classList.toggle('star-rating__star--selected', !hovering && filled);
                star.setAttribute('aria-checked', String(index === currentIndex));
                star.setAttribute('tabindex', index === Math.max(currentIndex, 0) ? '0' : '-1');
            });
        }

        /**
         * Persists a rating: writes it back to the <select> and notifies onSelect.
         *
         * @param {number} index
         */
        function commit(index) {
            currentIndex = index;
            select.selectedIndex = index;
            paint(currentIndex, false);
            settings.onSelect.call(select, select.value);
        }

        $widget.on('mouseenter', 'a.star-rating__star', function () {
            paint(Number(this.dataset.ratingIndex), true);
        });
        $widget.on('mouseleave', function () {
            paint(currentIndex, false);
        });
        $widget.on('click', 'a.star-rating__star', function (e) {
            e.preventDefault();
            commit(Number(this.dataset.ratingIndex));
        });
        // Cheap keyboard support: Enter/Space selects the focused star.
        $widget.on('keydown', 'a.star-rating__star', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                commit(Number(this.dataset.ratingIndex));
            }
        });

        paint(currentIndex, false);
    });
};
