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
 * The card itself, its positioning and its event handling live in hovertooltip.js, shared with the
 * NPC tooltip (#4096).
 */
(function () {
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
            .replace(/%%/g, '%')
            // A value we could not work out renders as nothing, leaving the spaces around it behind
            .replace(/(?<=\S)[ \t]{2,}/g, ' ')
            .trim();
    }

    /**
     * Fills the tooltip. Everything is set as text - a description comes from an external data source
     * and must never be interpreted as HTML.
     *
     * @param $result {jQuery}
     * @param $link {jQuery}
     * @param payload {Object}
     */
    function fillTooltip($result, $link, payload) {
        let $header = $('<div class="hover_tooltip_header"/>').appendTo($result);
        let iconUrl = $link.find('img').attr('src');

        if (typeof iconUrl === 'string') {
            $('<img class="hover_tooltip_icon" alt="" width="24" height="24"/>').attr('src', iconUrl).appendTo($header);
        }

        $('<div class="hover_tooltip_name"/>').text(payload.name ?? $link.text().trim()).appendTo($header);

        // The description keeps the game's own paragraph breaks
        for (let paragraph of renderDescription(payload).split('\n')) {
            if (paragraph.trim().length > 0) {
                $('<p class="hover_tooltip_description"/>').text(paragraph).appendTo($result);
            }
        }

        let $facts = $('<div class="hover_tooltip_facts"/>').appendTo($result);

        if (payload.castTime) {
            HoverTooltip.appendFact($facts, lang.get('js.spell_cast_time_label'), `${payload.castTime}s`);
        }

        if (payload.duration) {
            HoverTooltip.appendFact($facts, lang.get('js.spell_duration_label'), `${payload.duration}s`);
        }

        if (payload.schools) {
            HoverTooltip.appendFact($facts, lang.get('js.spell_schools_label'), payload.schools);
        }

        if (payload.dispelType) {
            HoverTooltip.appendFact($facts, lang.get('js.spell_dispel_type_label'), payload.dispelType);
        }

        if (payload.mechanic) {
            HoverTooltip.appendFact($facts, lang.get('js.spell_mechanic_label'), payload.mechanic);
        }

        if ($facts.children().length === 0) {
            $facts.remove();
        }
    }

    HoverTooltip.register('[data-spell-tooltip]', fillTooltip);
})();
