<?php
/** This is the display of affixes in the routes listing */
?>
<script id="routeattributes_row_template" type="text/x-handlebars-template">
    <div class="row no-gutters">
        @{{#attributes}}
        <div class="float-left">
            <div class="select_icon route_attribute-@{{ name }} mr-2" style="height: 24px;" data-toggle="tooltip"
                 title="@{{ description }}">
                &nbsp;
            </div>
        </div>
        @{{/attributes}}
    </div>
</script>
<script type="text/javascript">
    /**
     * Converts a received list of route attributes from a dungeon route to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsRouteAttributesParse(data) {
        let groupSetupHtml = $('#routeattributes_row_template').html();

        let template = handlebars.compile(groupSetupHtml);

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