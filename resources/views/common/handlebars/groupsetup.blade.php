<script id="group_setup_template" type="text/x-handlebars-template">
    <img src="@{{faction_icon_url}}" class="select_icon faction_icon"/>
    |
    @{{#classes}}
    <img src="@{{class_icon_url}}" class="select_icon class_icon"/>
    @{{/classes}}
</script>
<script>
    /**
     * Converts a received setup from a dungeon route (setup property) to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsGroupSetupParse(data){
        let groupSetupHtml = $("#group_setup_template").html();

        let template = handlebars.compile(groupSetupHtml);

        let handlebarsData = {
            faction_icon_url: data.faction.iconfile.icon_url,
            classes: []
        };

        for (let i in data.classes) {
            if( data.classes.hasOwnProperty(i) ){
                let playerClass = data.classes[i];
                handlebarsData.classes.push({
                    class_icon_url: playerClass.iconfile.icon_url
                })
            }
        }

        return template(handlebarsData);
    }
</script>