<?php
/** This is the template for the Affix Selection when using it in a dropdown */

/** @var \App\Models\DungeonRoute $model */
if(!isset($affixgroups) ){
    $affixgroups = \App\Models\AffixGroup::with('affixes')->get();
}

?><script id="affixgroup_select_option_template" type="text/x-handlebars-template">
    <div class="row affix_row_container no-gutters">
        @{{#affixes}}
            <div class="col affix_row">
                <div class="select_icon affix_icon_@{{ class }}" style="height: 24px;">
                    &nbsp;
                </div>
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
                            class: affix.name.toLowerCase()
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
        $(".bootstrap-select.affixselect .text")

        // Fix the select, it wraps the entire thing in a SPAN which completely destroys ability to do any form of layout on it
        // So remove the span
        $(".bootstrap-select.affixselect .text").each(function(index, el){
            let $el = $(el);
            let $ours = $el.children();
            $el.parent().append($ours);
            $el.remove();
        });



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
