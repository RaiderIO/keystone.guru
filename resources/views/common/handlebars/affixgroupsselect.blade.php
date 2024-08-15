<?php

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use App\Models\AffixGroup\AffixGroup;

?>
@inject('seasonService', SeasonServiceInterface::class)
<?php
/**
 * This is the template for the Affix Selection when using it in a dropdown
 *
 * @var SeasonServiceInterface      $seasonService
 * @var DungeonRoute                $model
 * @var Collection<AffixGroup>|null $affixgroups
 */

if (!isset($affixgroups)) {
    $affixgroups = $seasonService->getCurrentSeason()->affixGroups()->with('affixes')->get();
}

$id ??= 'affixes';
?>
<script>
    let _affixGroups{{ $id }} = {!! $affixgroups !!};

    $(function () {
        handlebarsLoadAffixGroupSelect{{ $id }}();
    });

    /**
     * Converts a received setup from a dungeon route (setup property) to a parsed handlebars template.
     * @returns {*}
     */
    function handlebarsLoadAffixGroupSelect{{ $id }}() {
        let affixSelectSelector = '#{{ $id }}';
        // @TODO make one template with multiple options, rather than calling this template N amount of times?
        for (let i in _affixGroups{{ $id }}) {
            if (_affixGroups{{ $id }}.hasOwnProperty(i)) {
                let affixGroup = _affixGroups{{ $id }}[i];
                let template = Handlebars.templates['affixgroup_select_option_template'];

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

            if (typeof $affixSelect.attr('readonly') !== 'undefined') {
                $affixSelect.find('option').attr('disabled', true);
            }
        });

    }
</script>
