class SearchFilterSpecializations extends SearchFilterSelect {
    constructor(selector, onChange, options = {}) {
        super(selector, onChange, options);
    }

    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        if (!this.passThrough) {
            $(this.selector).off('change').on('change', function () {
                self.onChange();

                refreshSelectPickers();
            });
        }
    }

    /**
     *
     * @returns {*[]}
     * @protected
     */
    _getSpecializationNames() {
        let value = this.getValue();

        let specializationNames = [];

        let characterClassSpecializations = getState().getMapContext().getStaticCharacterClassSpecializations();

        for (let index in value) {
            let specializationId = parseInt(value[index]);

            let characterClassSpecialization = null;
            for (let characterClassSpecializationsIndex in characterClassSpecializations) {
                let characterClassSpecializationCandidate = characterClassSpecializations[characterClassSpecializationsIndex];
                if (characterClassSpecializationCandidate.specialization_id === specializationId) {
                    characterClassSpecialization = characterClassSpecializationCandidate;
                    break;
                }
            }

            if (characterClassSpecialization === null) {
                console.error(`Unable to find character class specialization for ID ${specializationId}`, characterClassSpecializations);
                continue;
            }

            specializationNames.push(lang.get(characterClassSpecialization.name))
        }

        return specializationNames;
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_select_specializations_header')
            .replace(':specializations', this._getSpecializationNames().join(', '));
    }
}
