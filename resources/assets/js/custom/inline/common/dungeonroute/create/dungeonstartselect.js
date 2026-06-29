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

        $select.find('option').remove();

        if (dungeonStarts.length > 1) {
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
            refreshSelectPickers();
            $container.hide();
        }
    }
}
