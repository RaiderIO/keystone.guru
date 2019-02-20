<?php
/** This is the display of affixes in the routes listing */
/** See routeattributes_row_template.handlebars for handlebars template */
?>
<script type="text/javascript">
    /**
     * Converts a received list of route attributes from a dungeon route to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsRouteAttributesParse(data) {
        let template = Handlebars.templates['routeattributes_row_template'];

        let handlebarsData = {
            attributes: []
        };

        // Data contains affix groups
        for (let i in data) {
            if (data.hasOwnProperty(i)) {
                let serverRouteAttribute = data[i];

                handlebarsData.attributes.push({
                    name: serverRouteAttribute.name,
                    description: serverRouteAttribute.description
                });
            }
        }

        return template(handlebarsData);
    }
</script>