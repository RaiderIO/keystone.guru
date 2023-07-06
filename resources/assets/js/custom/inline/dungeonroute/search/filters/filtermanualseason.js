class SearchFilterManualSeason extends SearchFilterManual {
    constructor(onChange) {
        super({
            onChange: onChange
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_season_header').replace(':value', this.getValue());
    }
}
