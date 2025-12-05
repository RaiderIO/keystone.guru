class SearchFilterManualSeason extends SearchFilterManual {
    getFilterHeaderText() {
        return lang.get('js.filter_input_season_header').replace(':value', this.getValue());
    }
}
