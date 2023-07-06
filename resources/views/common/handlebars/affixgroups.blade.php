<?php
/** This is the display of affixes in the routes listing */
/** See affixgroups_single_template.handlebars and affixgroups_complex_template.handlebars */
?>
<script>
    /**
     * Converts a received affix group list from a dungeon route to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsAffixGroupsParse(data) {
        let template = null;
        if (data.length === 1) {
            template = Handlebars.templates['affixgroups_single_template'];
        } else {
            template = Handlebars.templates['affixgroups_complex_template'];
        }
        let handlebarsData = $.extend({
            count: data.length,
            affixgroups: []
        }, getHandlebarsDefaultVariables());

        // Data contains affix groups
        for (let i in data) {
            if (data.hasOwnProperty(i)) {
                let serverAffixGroup = data[i];

                let affixes = [];
                // Affix group contains affixes
                for (let j in serverAffixGroup.affixes) {
                    if (serverAffixGroup.affixes.hasOwnProperty(j)) {
                        let affix = serverAffixGroup.affixes[j];
                        // Push an affix to the list
                        affixes.push({
                            title: lang.get(affix.name),
                            name: lang.get(affix.name),
                            class: affix.key.toLowerCase()
                        });
                    }
                }

                handlebarsData.affixgroups.push({
                    affixes: affixes,
                    // Make it 1-indexed
                    seasonal_index: typeof serverAffixGroup.seasonal_index === 'number' ? serverAffixGroup.seasonal_index + 1 : null
                });
            }
        }

        let result = template(handlebarsData);
        // Start with an empty div since .html() takes the inner html, I don't want to lose the outer most div doing this
        let $result = $("<div>").append($(result));

        // Only for complex affixes
        if (data.length > 1) {
            let $rowContainer = $($result.find('.affix_list_row_container'));
            let $targetTooltip = $($result.find('.target_tooltip'));

            // Put the contents of the row container in the tooltip
            $targetTooltip.attr('title', $rowContainer.html());
            // Delete the container, it was only a placeholder
            $rowContainer.remove();

            $targetTooltip.tooltip();
        }

        return $result.html();
    }
</script>
