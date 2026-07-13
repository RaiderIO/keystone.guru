/**
 * @typedef {Object} CommonDungeonrouteCreateDungeondifficultySelectOptions
 * @property {string} dungeonSelectSelector
 * @property {string} dungeonDifficultySelectSelector
 * @property {string} dungeonDifficultySelectContainerSelector
 * @property {Array<Number>} speedrunDungeonIds
 * @property {Object} difficultyByDungeon
 */

/**
 * @property {CommonDungeonrouteCreateDungeondifficultySelectOptions} options
 */
class CommonDungeonrouteCreateDungeondifficultyselect extends InlineCode {

    activate() {
        super.activate();

        let self = this;
        let $dungeonSelect = $(this.options.dungeonSelectSelector);

        let dungeonSelectionChanged = function () {
            let $dungeonDifficultySelect = $(self.options.dungeonDifficultySelectSelector);
            let $dungeonDifficultySelectContainer = $(self.options.dungeonDifficultySelectContainerSelector);

            let selectedDungeonId = parseInt($dungeonSelect.val());
            if (self.options.speedrunDungeonIds.includes(selectedDungeonId)) {
                let enabledDifficultyForDungeon = self.options.difficultyByDungeon[selectedDungeonId];
                $dungeonDifficultySelect.find('option').remove();

                for (let difficultyId in enabledDifficultyForDungeon) {
                    if (enabledDifficultyForDungeon[difficultyId]) {
                        $dungeonDifficultySelect.append(jQuery('<option>', {
                            value: difficultyId,
                            text: lang.get(`dungeons.difficulty.${difficultyId}`)
                        }));
                    }
                }

                refreshSelectPickers();
                $dungeonDifficultySelectContainer.show();
            } else {
                $dungeonDifficultySelectContainer.hide();
            }
        };

        $dungeonSelect.bind('change', dungeonSelectionChanged);
        dungeonSelectionChanged();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonDungeonrouteCreateDungeondifficultyselect};
}
