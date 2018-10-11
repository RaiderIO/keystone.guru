<?php
/** @var \App\Models\DungeonRoute $model */
if(!isset($affixgroups) ){
    $affixgroups = \App\Models\AffixGroup::with('affixes')->get();
}

?><script id="affixgroup_select_option_template" type="text/x-handlebars-template">
    <div class="row affix_row_container">
        @{{#affixes}}
            <div class="col-3 affix_row">
                <img src="@{{ icon_url }}" class="select_icon affix_icon"/>
            </div>
        @{{/affixes}}
    </div>
</script>
<script>
    let _affixGroups = {!! $affixgroups !!};

    $(function(){
        handlebarsLoadAffixGroupSelect("#affixes");
    });

    /**
     * Converts a received setup from a dungeon route (setup property) to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsLoadAffixGroupSelect(affixSelectSelector) {

        for( let i in _affixGroups ){
            if( _affixGroups.hasOwnProperty(i) ){
                let affixGroup = _affixGroups[i];

                let optionTemplate = $("#affixgroup_select_option_template").html();
                let template = handlebars.compile(optionTemplate);

                let affixes = [];
                for( let j in affixGroup.affixes ){
                    if( affixGroup.affixes.hasOwnProperty(j) ){
                        let affix = affixGroup.affixes[j];

                        affixes.push({
                            icon_url: affix.iconfile.icon_url
                        });
                    }
                }

                let handlebarsData = {
                    affixes: affixes,
                    // faction_icon_url: data.faction.iconfile.icon_url,
                    // faction_title: data.faction.name,
                    // classes: []
                };

                let html = template(handlebarsData);
                let selector = affixSelectSelector + ' option[value=' + affixGroup.id + ']';
                $(selector).attr('data-content', html);
            }
        }
        $('.selectpicker').selectpicker('refresh');



        // for (let i in data.classes) {
        //     if( data.classes.hasOwnProperty(i) ){
        //         let playerClass = data.classes[i];
        //         handlebarsData.classes.push({
        //             icon_url: playerClass.iconfile.icon_url,
        //             title: playerClass.name
        //         })
        //     }
        // }
    }
</script>
