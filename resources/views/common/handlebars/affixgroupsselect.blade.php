@inject('seasonService', 'App\Service\Season\SeasonService')
<?php
/** @var $seasonService \App\Service\Season\SeasonService */
/** This is the template for the Affix Selection when using it in a dropdown */

/** @var \App\Models\DungeonRoute $model */
if (!isset($affixgroups)) {
    $affixgroups = $seasonService->getCurrentSeason()->affixgroups()->with('affixes')->get();
}

$id = $id ?? 'affixes';
?>
<script>
    let _affixGroups = {!! $affixgroups !!};

    $(function () {
        handlebarsLoadAffixGroupSelect('#{{ $id }}');
    });

    /**
     * Converts a received setup from a dungeon route (setup property) to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsLoadAffixGroupSelect(affixSelectSelector) {
        // @TODO make one template with multiple options, rather than calling this template N amount of times?
        for (let i in _affixGroups) {
            if (_affixGroups.hasOwnProperty(i)) {
                let affixGroup = _affixGroups[i];
                let template = Handlebars.templates['affixgroup_select_option_template'];

                let affixes = [];
                for (let j in affixGroup.affixes) {
                    if (affixGroup.affixes.hasOwnProperty(j)) {
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
                $(selector).attr('data-content', html);
            }
        }

        refreshSelectPickers();

        let $affixSelect = $(affixSelectSelector);
        $affixSelect.on('shown.bs.select', function () {
            // Fix the select, it wraps the entire thing in a SPAN which completely destroys ability to do any form of layout on it
            // So remove the span
            $('.affixselect.bootstrap-select .text').each(function (index, el) {
                let $el = $(el);
                let $ours = $el.children();
                $el.parent().append($ours);
                $el.remove();
            });

            if( typeof $affixSelect.attr('readonly') !== 'undefined' ) {
                $affixSelect.find('option').attr('disabled', true);
            }
        });

    }
</script>
