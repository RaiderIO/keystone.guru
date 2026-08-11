/**
 * Hover tooltips for spell links, rendered from the spell descriptions we import from the game
 * client's DB2 data (#3951) rather than from Wowhead's tooltip script.
 *
 * Links opt in by carrying their tooltip payload in data-spell-tooltip; spells we could not render a
 * description for keep their data-wowhead attribute instead, so those still get Wowhead's tooltip.
 *
 * The description arrives as a sprintf-style format plus one entry per number rather than as a
 * finished sentence, so a key level selector can put different damage numbers in without asking the
 * server for the sentence again (#3971).
 *
 * Bound on the document rather than on the links themselves - spell links appear in Handlebars
 * templates rendered long after page load, such as the compendium tables.
 */
(function () {
    const SELECTOR = '[data-spell-tooltip]';
    const VIEWPORT_MARGIN = 8;

    let $tooltip = null;

    /**
     * @param $link {jQuery}
     * @returns {Object|null}
     */
    function getPayload($link) {
        try {
            return JSON.parse($link.attr('data-spell-tooltip'));
        } catch (exception) {
            return null;
        }
    }

    /**
     * Put the numbers back into the format. Both come from our own database, and the format only ever
     * reaches a replace() - never anything that could execute - with every value inserted as text.
     *
     * @param payload {Object}
     * @returns {string}
     */
    function renderDescription(payload) {
        const values = payload.values ?? [];

        return String(payload.format ?? '')
            .replace(/%(\d+)\$s/g, (match, index) => values[parseInt(index, 10) - 1]?.text ?? '')
            .replace(/%%/g, '%');
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
     * @param $facts {jQuery}
     * @param label {string}
     * @param value {string}
     */
    function appendFact($facts, label, value) {
        let $fact = $('<div class="spell_tooltip_fact"/>').appendTo($facts);

        $('<span class="spell_tooltip_fact_label"/>').text(label).appendTo($fact);
        $('<span class="spell_tooltip_fact_value"/>').text(value).appendTo($fact);
    }

    /**
     * Fills the tooltip. Everything is set as text - a description comes from an external data source
     * and must never be interpreted as HTML.
     *
     * @param $link {jQuery}
     * @param payload {Object}
     * @returns {jQuery}
     */
    function fillTooltip($link, payload) {
        let $result = getTooltip().empty();

        let $header = $('<div class="spell_tooltip_header"/>').appendTo($result);
        let iconUrl = $link.find('img').attr('src');

        if (typeof iconUrl === 'string') {
            $('<img class="spell_tooltip_icon" alt="" width="24" height="24"/>').attr('src', iconUrl).appendTo($header);
        }

        $('<div class="spell_tooltip_name"/>').text(payload.name ?? $link.text().trim()).appendTo($header);

        // The description keeps the game's own paragraph breaks
        for (let paragraph of renderDescription(payload).split('\n')) {
            if (paragraph.trim().length > 0) {
                $('<p class="spell_tooltip_description"/>').text(paragraph).appendTo($result);
            }
        }

        let $facts = $('<div class="spell_tooltip_facts"/>').appendTo($result);

        if (payload.castTime) {
            appendFact($facts, lang.get('js.spell_cast_time_label'), `${payload.castTime}s`);
        }

        if (payload.duration) {
            appendFact($facts, lang.get('js.spell_duration_label'), `${payload.duration}s`);
        }

        if (payload.schools) {
            appendFact($facts, lang.get('js.spell_schools_label'), payload.schools);
        }

        if (payload.dispelType) {
            appendFact($facts, lang.get('js.spell_dispel_type_label'), payload.dispelType);
        }

        if (payload.mechanic) {
            appendFact($facts, lang.get('js.spell_mechanic_label'), payload.mechanic);
        }

        if ($facts.children().length === 0) {
            $facts.remove();
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
        let payload = getPayload($link);

        if (payload === null) {
            return;
        }

        positionTooltip($link, fillTooltip($link, payload));
    }

    $(function () {
        $(document)
            .on('mouseenter focusin', SELECTOR, showTooltip)
            .on('mouseleave focusout', SELECTOR, hideTooltip);

        $(window).on('scroll resize', hideTooltip);
    });
})();
