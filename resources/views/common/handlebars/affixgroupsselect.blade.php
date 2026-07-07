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
 * @var SeasonServiceInterface           $seasonService
 * @var DungeonRoute                     $model
 * @var Collection<int, AffixGroup>|null $affixgroups
 */

if (!isset($affixgroups)) {
    $affixgroups = $seasonService->getCurrentSeason()->affixGroups()->with('affixes')->get();
}

$id ??= 'Affixes';
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
                            class: affix.image_name,
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
            // The span.text wrapper that bootstrap-select puts around our data-content is made
            // display: block in custom.css so the affix rows lay out correctly from the first
            // render; that also keeps the menu height measurement correct so no dead space remains.

            // Fix the layout so there's no longer a huge gap at the bottom
            $('.affixselect.bootstrap-select .dropdown-menu.show').css('min-height', 'unset');
            $('.affixselect.bootstrap-select .inner.show').css('min-height', 'unset');

            if (typeof $affixSelect.attr('readonly') !== 'undefined') {
                $affixSelect.find('option').attr('disabled', true);
            }
        });

    }
</script>
