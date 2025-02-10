class SearchFilterClasses extends SearchFilterSelect {
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

    getFilterHeaderText() {
        let value = this.getValue();

        let classNames = [];

        let characterClasses = getState().getMapContext().getStaticCharacterClasses();

        for (let index in value) {
            let classId = parseInt(value[index]);

            let characterClass = null;
            for (let characterClassesIndex in characterClasses) {
                let characterClassSpecializationCandidate = characterClasses[characterClassesIndex];
                if (characterClassSpecializationCandidate.class_id === classId) {
                    characterClass = characterClassSpecializationCandidate;
                    break;
                }
            }

            if (characterClass === null) {
                console.error(`Unable to find character class for ID ${classId}`, characterClasses);
                continue;
            }

            classNames.push(lang.get(characterClass.name))
        }

        return lang.get('messages.filter_input_select_classes_header')
            .replace(':classes', classNames.join(', '));
    }
}
