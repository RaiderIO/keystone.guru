<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Season\Dtos\SeasonWeeklyAffixGroup;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

?>
@inject('seasonService', SeasonServiceInterface::class)
<?php
/**
 * This is the template for the week selection, featuring the affix of that week, when using it in a dropdown
 *
 * @var SeasonServiceInterface             $seasonService
 * @var DungeonRoute                       $model
 * @var Collection<SeasonWeeklyAffixGroup> $seasonWeeklyAffixGroups
 */

$id ??= 'affixes';
?>
<script>``
    let _seasonWeeklyAffixGroups{{ $id }} = {!! $seasonWeeklyAffixGroups !!};

    // Load it immediately so that the search headers can take advantage of the data that is set in the option elements
    handlebarsLoadWeekAffixGroupSelect{{ $id }}();

    /**
     * @returns {*}
     */
    function handlebarsLoadWeekAffixGroupSelect{{ $id }}() {
        let affixWeekSelectSelector = '#{{ $id }}';
        let handlebarsDefaultVariables = $.extend({}, getHandlebarsDefaultVariables());
        // @TODO make one template with multiple options, rather than calling this template N amount of times?
        for (let i in _seasonWeeklyAffixGroups{{ $id }}) {
            if (_seasonWeeklyAffixGroups{{ $id }}.hasOwnProperty(i)) {
                let week = parseInt(i) + 1;

                let seasonWeeklyAffixGroup = _seasonWeeklyAffixGroups{{ $id }}[i];
                let affixGroup = seasonWeeklyAffixGroup.affixGroup;

                let template = Handlebars.templates['weekly_affix_group_select_option_template'];

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

                // Use Intl.DateTimeFormat to format the date in DD/MM/YYYY
                const formattedDate = new Intl.DateTimeFormat('default', {
                    day: '2-digit',
                    month: '2-digit',
                    year: '2-digit'
                }).format(new Date(seasonWeeklyAffixGroup.date));

                let handlebarsData = {
                    week: seasonWeeklyAffixGroup.week,
                    date: formattedDate,
                    affixes: affixes,
                };

                let html = template($.extend(handlebarsDefaultVariables, handlebarsData));
                let selector = affixWeekSelectSelector + ' option[value=' + week + ']';
                $(selector).attr('data-content', html).attr('data-date', formattedDate);
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
