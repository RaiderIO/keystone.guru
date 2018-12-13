<?php

?>
<script id="biglistfeatures_template" type="text/x-handlebars-template">
    <div class="row no-gutters mt-1">
        <div class="col-xl-5 d-none d-md-flex">{{ __('Affixes:') }}</div>
        <div class="col-xl-7">
            @{{{affixes}}}
        </div>
    </div>

    @{{#if showAttributes}}
    <div class="row no-gutters mt-1 d-none d-md-flex">
        <div class="col-xl-5">{{ __('Attributes:') }}</div>
        <div class="col-xl-7">
            @{{{attributes}}}
        </div>
    </div>
    @{{/if}}
    <?php /*Hidden on smaller screens*/ ?>
    <div class="row no-gutters mt-1 d-none d-lg-flex">
        <div class="col-xl-5">{{ __('Setup:') }}</div>
        <div class="col-xl-7">
            @{{{setup}}}
        </div>
    </div>
</script>
<script>
    /**
     * Converts a received row to a collection of features.
     * @returns {*}
     */
    function handlebarsBiglistFeaturesParse(row) {
        let featureTemplate = $('#biglistfeatures_template').html();
        let template = handlebars.compile(featureTemplate);

        let handlebarsData = {
            affixes: handlebarsAffixGroupsParse(row.affixes),
            showAttributes: row.routeattributes.length > 0,
            attributes: handlebarsRouteAttributesParse(row.routeattributes),
            setup: handlebarsGroupSetupParse(row.setup),
        };

        return template(handlebarsData);
    }
</script>
