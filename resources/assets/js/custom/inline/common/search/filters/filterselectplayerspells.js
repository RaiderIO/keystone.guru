class SearchFilterPlayerSpells extends SearchFilterSelect {
    constructor(selector, onChange) {
        super(selector, onChange);
    }

    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        $(this.selector).off('change').on('change', function () {
            self.onChange();

            refreshSelectPickers();
        });
    }

    /**
     *
     * @returns {*[]}
     * @protected
     */
    _getSpellNames() {
        let value = this.getValue();

        let spellNames = [];

        let spells = getState().getMapContext().getSpells();

        for (let index in value) {
            let spellId = parseInt(value[index]);

            let spell = null;
            for (let spellIndex in spells) {
                let spellCandidate = spells[spellIndex];
                if (spellCandidate.id === spellId) {
                    spell = spellCandidate;
                    break;
                }
            }

            if (spell === null) {
                console.error(`Unable to find spell for ID ${spellId}`, spells);
                continue;
            }

            spellNames.push(lang.get(spell.name))
        }

        return spellNames;
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_select_player_spells_header')
            .replace(':spells', this._getSpellNames().join(', '));
    }
}
