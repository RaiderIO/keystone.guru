/**
 * Hover tooltips for NPC links (#4096), the counterpart of the spell tooltip sitting next to them on
 * the same compendium pages.
 *
 * Links opt in by carrying their payload in data-npc-tooltip, which Npc::$tooltip_data builds - the
 * server leaves out anything that would render as an empty row, so everything that arrives here is
 * worth showing.
 *
 * The card itself, its positioning and its event handling live in hovertooltip.js, shared with the
 * spell tooltip.
 */
(function () {
    /**
     * @param $result {jQuery}
     * @param $link {jQuery}
     * @param payload {Object}
     */
    function fillTooltip($result, $link, payload) {
        let $header = $('<div class="hover_tooltip_header"/>').appendTo($result);

        if (payload.portraitUrl) {
            $('<img class="hover_tooltip_portrait" alt="" width="36" height="36"/>')
                .attr('src', payload.portraitUrl)
                .appendTo($header);
        }

        $('<div class="hover_tooltip_name"/>').text(payload.name ?? $link.text().trim()).appendTo($header);

        let $badges = $('<div class="hover_tooltip_badges"/>').appendTo($result);

        if (payload.classification) {
            $('<span class="hover_tooltip_badge"/>')
                .addClass(payload.isBoss ? 'text-bg-danger' : 'text-bg-secondary')
                .text(payload.classification)
                .appendTo($badges);
        }

        for (let flag of payload.flags ?? []) {
            $('<span class="hover_tooltip_badge text-bg-warning"/>').text(flag).appendTo($badges);
        }

        if ($badges.children().length === 0) {
            $badges.remove();
        }

        let $facts = $('<div class="hover_tooltip_facts"/>').appendTo($result);

        if (payload.level) {
            HoverTooltip.appendFact($facts, lang.get('js.npc_level_label'), `${payload.level}`);
        }

        if (payload.health) {
            HoverTooltip.appendFact($facts, lang.get('js.npc_health_label'), payload.health.toLocaleString());
        }

        if (payload.type) {
            HoverTooltip.appendFact($facts, lang.get('js.npc_type_label'), payload.type);
        }

        if (payload.aggressiveness) {
            HoverTooltip.appendFact($facts, lang.get('js.npc_aggressiveness_label'), payload.aggressiveness);
        }

        if ($facts.children().length === 0) {
            $facts.remove();
        }

        // Crowd control we have seen land on this NPC. An absent one was never observed rather than
        // resisted, so nothing is listed as absent here (#4028)
        if ((payload.characteristics ?? []).length > 0) {
            let $characteristics = $('<div class="hover_tooltip_characteristics"/>').appendTo($result);

            $('<div class="hover_tooltip_characteristics_label"/>')
                .text(lang.get('js.npc_characteristics_label'))
                .appendTo($characteristics);

            let $icons = $('<div class="hover_tooltip_characteristics_list"/>').appendTo($characteristics);

            // Names spelled out rather than icons alone: the card cannot be hovered itself (it sits
            // under the cursor with pointer-events off), so an icon's own title would never show
            for (let characteristic of payload.characteristics) {
                let $characteristic = $('<span class="hover_tooltip_characteristic"/>').appendTo($icons);

                $('<img alt="" width="18" height="18"/>')
                    .attr('src', characteristic.iconUrl)
                    .appendTo($characteristic);

                $('<span/>').text(characteristic.name).appendTo($characteristic);
            }
        }
    }

    HoverTooltip.register('[data-npc-tooltip]', fillTooltip);
})();
