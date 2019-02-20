<?php
/** See biglistfeatures.handlebars */
?>
<script>
    /**
     * Converts a received row to a collection of features.
     * @returns {*}
     */
    function handlebarsBiglistFeaturesParse(row) {
        let template = Handlebars.templates['biglistfeatures_template'];

        let handlebarsData = $.extend({
            affixes: handlebarsAffixGroupsParse(row.affixes),
            showAttributes: row.routeattributes.length > 0,
            attributes: handlebarsRouteAttributesParse(row.routeattributes),
            setup: handlebarsGroupSetupParse(row.setup),
        }, getHandlebarsTranslations());

        return template(handlebarsData);
    }
</script>
