/**
 * Hover tooltips for spell links, rendered from the spell descriptions we import from the game
 * client's DB2 data (#3951) rather than from Wowhead's tooltip script.
 *
 * Links opt in by carrying the description in data-spell-description; spells we could not render a
 * description for keep their data-wowhead attribute instead, so those still get Wowhead's tooltip.
 *
 * Bound on the document rather than on the links themselves - spell links appear in Handlebars
 * templates that are rendered long after page load, such as the map's enemy details.
 */
(function () {
    const SELECTOR = '[data-spell-description]';
    const VIEWPORT_MARGIN = 8;

    let $tooltip = null;

    /**
     * Read straight off the attribute rather than through jQuery's data(), which would try to
     * interpret a description that happens to start with a bracket or brace as JSON.
     *
     * @param $link {jQuery}
     * @returns {string}
     */
    function getDescription($link) {
        return $link.attr('data-spell-description') ?? '';
    }

    /**
     * @returns {jQuery}
     */
    function getTooltip() {
        if ($tooltip === null) {
            $tooltip = $('<div class="spell_tooltip" role="tooltip" aria-hidden="true"/>').appendTo('body');
        }

        return $tooltip;
    }

    /**
     * Fills the tooltip with the spell of the hovered link. Everything is set as text - the
     * description comes from an external data source and must never be interpreted as HTML.
     *
     * @param $link {jQuery}
     */
    function fillTooltip($link) {
        let $result = getTooltip().empty();

        let $header = $('<div class="spell_tooltip_header"/>').appendTo($result);
        let iconUrl = $link.find('img').attr('src');

        if (typeof iconUrl === 'string') {
            $('<img class="spell_tooltip_icon" alt="" width="24" height="24"/>').attr('src', iconUrl).appendTo($header);
        }

        $('<div class="spell_tooltip_name"/>').text($link.attr('data-spell-name') ?? $link.text().trim()).appendTo($header);

        // The description keeps the game's own paragraph breaks
        for (let paragraph of getDescription($link).split('\n')) {
            if (paragraph.trim().length > 0) {
                $('<p class="spell_tooltip_description"/>').text(paragraph).appendTo($result);
            }
        }

        return $result;
    }

    /**
     * Puts the tooltip under the link, moving it back into view when the link sits at an edge.
     *
     * @param $link {jQuery}
     * @param $result {jQuery}
     */
    function positionTooltip($link, $result) {
        let linkRect = $link[0].getBoundingClientRect();
        let left = linkRect.left;
        let top = linkRect.bottom + VIEWPORT_MARGIN;

        // Shown before it is measured, so that it has a width to measure at all
        $result.addClass('spell_tooltip_visible').attr('aria-hidden', 'false');

        let tooltipRect = $result[0].getBoundingClientRect();

        if (left + tooltipRect.width > window.innerWidth - VIEWPORT_MARGIN) {
            left = Math.max(VIEWPORT_MARGIN, window.innerWidth - tooltipRect.width - VIEWPORT_MARGIN);
        }

        // No room below the link - flip it above instead
        if (top + tooltipRect.height > window.innerHeight - VIEWPORT_MARGIN) {
            top = Math.max(VIEWPORT_MARGIN, linkRect.top - tooltipRect.height - VIEWPORT_MARGIN);
        }

        $result.css({left: `${left + window.scrollX}px`, top: `${top + window.scrollY}px`});
    }

    function hideTooltip() {
        if ($tooltip !== null) {
            $tooltip.removeClass('spell_tooltip_visible').attr('aria-hidden', 'true');
        }
    }

    function showTooltip() {
        let $link = $(this);

        if (getDescription($link).trim().length === 0) {
            return;
        }

        positionTooltip($link, fillTooltip($link));
    }

    $(function () {
        $(document)
            .on('mouseenter focusin', SELECTOR, showTooltip)
            .on('mouseleave focusout', SELECTOR, hideTooltip);

        $(window).on('scroll resize', hideTooltip);
    });
})();
