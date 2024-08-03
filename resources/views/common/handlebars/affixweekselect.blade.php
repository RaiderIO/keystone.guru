<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use App\Models\AffixGroup\AffixGroup;

?>
@inject('seasonService', SeasonServiceInterface::class)
<?php
/**
 * This is the template for the week selection, featuring the affix of that week, when using it in a dropdown
 *
 * @var SeasonServiceInterface $seasonService
 * @var DungeonRoute           $model
 * @var Collection<AffixGroup> $affixGroupsPerWeek
 */

$id ??= 'affixes';
?>
<script>
    let _affixGroupsPerWeek{{ $id }} = {!! $affixGroupsPerWeek !!};

    $(function () {
        handlebarsLoadAffixWeekSelect{{ $id }}();
    });

    /**
     * @returns {*}
     */
    function handlebarsLoadAffixWeekSelect{{ $id }}() {
        let affixWeekSelectSelector = '#{{ $id }}';
        let handlebarsDefaultVariables = $.extend({}, getHandlebarsDefaultVariables());
        // @TODO make one template with multiple options, rather than calling this template N amount of times?
        for (let i in _affixGroupsPerWeek{{ $id }}) {
            if (_affixGroupsPerWeek{{ $id }}.hasOwnProperty(i)) {
                let week = parseInt(i) + 1;

                let affixGroup = _affixGroupsPerWeek{{ $id }}[i];
                let template = Handlebars.templates['affix_week_select_option_template'];

                let affixes = [];
                for (let j in affixGroup.affixes) {
                    if (affixGroup.affixes.hasOwnProperty(j)) {
                        let affix = affixGroup.affixes[j];

                        affixes.push({
                            class: affix.key.toLowerCase(),
                            name: affix.name
                        });
                    }
                }

                let handlebarsData = {
                    week: week,
                    affixes: affixes,
                };

                let html = template($.extend(handlebarsDefaultVariables, handlebarsData));
                let selector = affixWeekSelectSelector + ' option[value=' + week + ']';
                $(selector).attr('data-content', html);
            }
        }

        refreshSelectPickers();

        let $affixSelect = $(affixWeekSelectSelector);
        $affixSelect.on('shown.bs.select', function () {
            // Fix the select, it wraps the entire thing in a SPAN which completely destroys ability to do any form of layout on it
            // So remove the span
            $('.affixselect.bootstrap-select .text').each(function (index, el) {
                let $el = $(el);
                let $ours = $el.children();
                $el.parent().append($ours);
                $el.remove();
            });

            if (typeof $affixSelect.attr('readonly') !== 'undefined') {
                $affixSelect.find('option').attr('disabled', true);
            }
        });

    }
</script>
