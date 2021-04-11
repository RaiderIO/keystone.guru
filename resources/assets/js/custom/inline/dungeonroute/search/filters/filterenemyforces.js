class SearchFilterEnemyForces extends SearchFilter {
    constructor(selector, onChange) {
        super({
            name: 'enemy_forces',
            selector: selector,
            onChange: onChange
        });
    }

    activate() {
        super.activate();

        $(this.options.selector).change(this.options.onChange);
    }

    getValue() {
        return $(this.options.selector).is(':checked') ? 1 : 0;
    }

    setValue(value) {
        if( value ) {
            $(this.options.selector).attr('checked', 'checked');
        } else {
            $(this.options.selector).removeAttr('checked');
        }
    }

    getFilterHeaderText() {
        let result = 'Enemy forces: ';

        if (this.getValue() === 0) {
            result += 'incomplete';
        } else {
            result += 'complete';
        }

        return result;
    }
}