/**
 * The shell every hover tooltip on the site shares: one floating element, one set of delegated
 * handlers, one bit of positioning maths (#4096).
 *
 * Spells got the first one of these (#3951) and NPCs the second, and they only differ in what goes
 * inside the card - keeping the card itself in one place is what makes the two feel like one system
 * rather than two lookalikes that drift apart.
 *
 * A caller registers a selector plus a function that fills the card:
 *
 *     HoverTooltip.register('[data-npc-tooltip]', function ($tooltip, $link, payload) { ... });
 *
 * Handlers are bound on the document rather than on the links themselves - tooltip-carrying links
 * appear in Handlebars templates rendered long after page load, such as the compendium tables.
 */
const HoverTooltip = (function () {
    const VIEWPORT_MARGIN = 8;

    let $tooltip = null;

    /**
     * @returns {jQuery}
     */
    function getTooltip() {
        if ($tooltip === null) {
            $tooltip = $('<div class="hover_tooltip" role="tooltip" aria-hidden="true"/>').appendTo('body');
        }

        return $tooltip;
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
        $result.addClass('hover_tooltip_visible').attr('aria-hidden', 'false');

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
            $tooltip.removeClass('hover_tooltip_visible').attr('aria-hidden', 'true');
        }
    }

    // Scrolling or resizing moves the link out from under a tooltip that is already placed - bound
    // once here rather than once per registration
    $(function () {
        $(window).on('scroll resize', hideTooltip);
    });

    return {
        /**
         * A label/value pair in the tooltip's facts register, as used by both kinds of tooltip.
         *
         * @param $facts {jQuery}
         * @param label {string}
         * @param value {string}
         */
        appendFact: function ($facts, label, value) {
            let $fact = $('<div class="hover_tooltip_fact"/>').appendTo($facts);

            $('<span class="hover_tooltip_fact_label"/>').text(label).appendTo($fact);
            $('<span class="hover_tooltip_fact_value"/>').text(value).appendTo($fact);
        },

        /**
         * Shows the tooltip for every element matching the selector, filled by the given function.
         *
         * The payload travels in the attribute the selector is built from; an attribute we cannot
         * parse shows nothing at all rather than an empty card.
         *
         * @param selector {string} An attribute selector, such as '[data-npc-tooltip]'
         * @param fill {function($tooltip: jQuery, $link: jQuery, payload: Object)}
         */
        register: function (selector, fill) {
            const attribute = selector.replace(/^\[|]$/g, '');

            $(function () {
                $(document)
                    .on('mouseenter focusin', selector, function () {
                        let $link = $(this);
                        let payload;

                        try {
                            payload = JSON.parse($link.attr(attribute));
                        } catch (exception) {
                            return;
                        }

                        if (payload === null || typeof payload !== 'object') {
                            return;
                        }

                        // The card is shared between every kind of tooltip, so any variant class the
                        // previous one added has to come off again
                        let $result = getTooltip().empty().attr('class', 'hover_tooltip');

                        fill($result, $link, payload);

                        positionTooltip($link, $result);
                    })
                    .on('mouseleave focusout', selector, hideTooltip);
            });
        },
    };
})();
