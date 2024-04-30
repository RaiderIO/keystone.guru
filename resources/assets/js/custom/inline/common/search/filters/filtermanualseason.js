class SearchFilterManualSeason extends SearchFilterManual {
    getFilterHeaderText() {
        return lang.get('messages.filter_input_season_header').replace(':value', this.getValue());
    }
}
