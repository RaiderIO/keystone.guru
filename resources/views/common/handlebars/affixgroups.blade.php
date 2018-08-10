<script id="affixgroups_single_template" type="text/x-handlebars-template">
    <?php // Is only one but keeps the underlying code much simpler to keep that data structure the same ?>
    @{{#affixgroups}}
    <div class="affix_list_row">
        @{{#affixes}}
        <div class="affix_row float-left">
            <img src="@{{icon_url}}"
                 class="select_icon affix_icon pr-1"
                 data-toggle="tooltip"
                 title="@{{title}}"/>
        </div>
        @{{/affixes}}
    </div>
    @{{/affixgroups}}
</script>
<script id="affixgroups_complex_template" type="text/x-handlebars-template">
    <span class="target_tooltip" data-toggle="tooltip" data-html="true">
        @{{count}} {{ __('selected') }}
    </span>
    <?php // Wrapper so we can put all this in the tooltip of the above span. I'm not cramming that in a tiny attribute manually ?>
    <div class="affix_list_row_container">
        @{{#affixgroups}}
        <div class="row affix_list_row" style="width: 140px">
            @{{#affixes}}
            <div class="affix_row col-md-4">
                <img src="@{{icon_url}}"
                     class="select_icon affix_icon"/>
            </div>
            @{{/affixes}}
        </div>
        @{{/affixgroups}}
    </div>
</script>
<script>
    /**
     * Converts a received setup from a dungeon route (setup property) to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsAffixGroupsParse(data) {
        let groupSetupHtml = '';
        if (data.length === 1) {
            groupSetupHtml = $("#affixgroups_single_template").html();
        } else {
            groupSetupHtml = $("#affixgroups_complex_template").html();
        }

        let template = handlebars.compile(groupSetupHtml);

        let handlebarsData = {
            count: data.length,
            affixgroups: []
        };

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
                            icon_url: affix.iconfile.icon_url,
                            title: affix.name,
                            name: affix.name
                        });
                    }
                }

                handlebarsData.affixgroups.push({affixes: affixes});
            }
        }

        let result = template(handlebarsData);
        // Start with an empty div since .html() takes the inner html, I don't want to lose the outer most div doing this
        let $result = $("<div>").append($(result));

        // Only for complex affixes
        if( data.length > 1 ){
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