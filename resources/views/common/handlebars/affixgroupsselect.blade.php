<?php
/** This is the template for the Affix Selection when using it in a dropdown */

/** @var \App\Models\DungeonRoute $model */
if(!isset($affixgroups) ){
    $affixgroups = \App\Models\AffixGroup::active()->with('affixes')->get();
}

?><script id="affixgroup_select_option_template" type="text/x-handlebars-template">
    <div class="row affix_row_container no-gutters">
        @{{#affixes}}
            <div class="col affix_row">
                <div class="select_icon affix_icon_@{{ class }}" style="height: 24px;"> </div>
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
        // @TODO make one template with multiple options, rather than calling this template N amount of times?
        for( let i in _affixGroups ){
            if( _affixGroups.hasOwnProperty(i) ){
                let affixGroup = _affixGroups[i];

                let optionTemplate = $("#affixgroup_select_option_template").html();
                let template = Handlebars.compile(optionTemplate);

                let affixes = [];
                for( let j in affixGroup.affixes ){
                    if( affixGroup.affixes.hasOwnProperty(j) ){
                        let affix = affixGroup.affixes[j];

                        affixes.push({
                            class: affix.name.toLowerCase(),
                            name: affix.name
                        });
                    }
                }

                let handlebarsData = {
                    affixes: affixes,
                };

                let html = template(handlebarsData);
                let selector = affixSelectSelector + ' option[value=' + affixGroup.id + ']';
                $(selector).data('content', html);
            }
        }

        refreshSelectPickers();

        // Fix the select, it wraps the entire thing in a SPAN which completely destroys ability to do any form of layout on it
        // So remove the span
        $(".bootstrap-select.affixselect .text").each(function(index, el){
            let $el = $(el);
            let $ours = $el.children();
            $el.parent().append($ours);
            $el.remove();
        });
    }
</script>
