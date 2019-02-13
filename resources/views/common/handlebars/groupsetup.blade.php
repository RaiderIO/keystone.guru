<?php //See group_setup_template.handlebars for handlebars ?>
<script>
    /**
     * Converts a received setup from a dungeon route (setup property) to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsGroupSetupParse(data) {
        let template = Handlebars.templates['group_setup_template'];

        let handlebarsData = {
            css_class: 'faction_icon_' + data.faction.name.toLowerCase(),
            faction_title: data.faction.name,
            classes: []
        };

        // Only show icons when the author has set specializations
        for (let i in data.specializations) {
            if (data.specializations.hasOwnProperty(i)) {
                let playerRaceName = data.races.hasOwnProperty(i) ? data.races[i].name : '';
                let playerClassName = data.classes.hasOwnProperty(i) ? data.classes[i].name : '';
                let playerSpecializationName = data.specializations.hasOwnProperty(i) ? data.specializations[i].name : '';

                handlebarsData.classes.push({
                    css_class: 'spec_icon_' +
                        playerClassName.toLowerCase().replace(/ /g, '') + '-' +
                        playerSpecializationName.toLowerCase().replace(/ /g, ''),
                    title: (playerRaceName + ' ' + playerSpecializationName + ' ' + playerClassName).trim()
                })
            }
        }

        return template(handlebarsData);
    }
</script>

