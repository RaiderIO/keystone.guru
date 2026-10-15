/**
 * @typedef {Object} CommonDungeonrouteCreateDungeonstartSelectOptions
 * @property {string}      dungeonSelectId
 * @property {string}      dungeonStartSelectId
 * @property {string}      dungeonStartContainerId
 * @property {Object}      dungeonStartsByDungeonId
 * @property {number|null} selectedDungeonStartId
 */

/**
 * @property {CommonDungeonrouteCreateDungeonstartSelectOptions} options
 */
class CommonDungeonrouteCreateDungeonstartselect extends InlineCode {
    activate() {
        super.activate();

        let self = this;
        $(this.options.dungeonSelectId).bind('change', function () {
            self._dungeonSelectionChanged();
        });

        this._dungeonSelectionChanged();
    }

    _dungeonSelectionChanged() {
        let $select    = $(this.options.dungeonStartSelectId);
        let $container = $(this.options.dungeonStartContainerId);

        let selectedDungeonId = parseInt($(this.options.dungeonSelectId).val());
        let dungeonStarts     = this.options.dungeonStartsByDungeonId[selectedDungeonId] || [];

        if (dungeonStarts.length > 1) {
            $select.find('option').remove();

            for (let i = 0; i < dungeonStarts.length; i++) {
                let dungeonStart = dungeonStarts[i];
                $select.append(jQuery('<option>', {
                    value:    dungeonStart.id,
                    text:     dungeonStart.text,
                    selected: dungeonStart.id === this.options.selectedDungeonStartId
                }));
            }

            refreshSelectPickers();
            $container.show();
        } else {
            // refreshSelectPickers() is a page-wide sync: calling it with nothing to clear would initialize
            // Tom Select on other not-yet-activated .selectpicker elements on the page too early.
            if ($select.find('option').length > 0) {
                $select.find('option').remove();
                refreshSelectPickers();
            }
            $container.hide();
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonDungeonrouteCreateDungeonstartselect};
}
