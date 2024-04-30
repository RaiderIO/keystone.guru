class SearchFilterEnemyForces extends SearchFilter {

    activate() {
        super.activate();

        $(this.selector).change(this.onChange);
    }

    getValue() {
        return $(this.selector).is(':checked') ? 1 : 0;
    }

    setValue(value) {
        if (parseInt(value) === 1) {
            $(this.selector).attr('checked', 'checked');
        } else {
            $(this.selector).removeAttr('checked');
        }
    }

    getFilterHeaderText() {
        let result = '';

        if (this.getValue() === 0) {
            result += lang.get('messages.filter_enemy_forces_header_incomplete');
        } else {
            result += lang.get('messages.filter_enemy_forces_header_complete');
        }

        return result;
    }
}
