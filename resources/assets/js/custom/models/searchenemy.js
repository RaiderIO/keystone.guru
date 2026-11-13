class SearchEnemy extends Enemy {
    constructor(map, layer) {
        super(map, layer);

        this.included = false;
        this.excluded = false;
    }

    /**
     * @returns {boolean}
     */
    isIncluded() {
        return this.included;
    }

    /**
     * @param value {boolean}
     */
    setIncluded(value) {
        console.assert(this instanceof SearchEnemy, 'this is not a SearchEnemy', this);
        if (this.included !== value) {
            this.included = value;
            this.signal('included:changed');
        }
    }

    /**
     * @returns {boolean}
     */
    isExcluded() {
        return this.excluded;
    }

    /**
     * @param value {boolean}
     */
    setExcluded(value) {
        console.assert(this instanceof SearchEnemy, 'this is not a SearchEnemy', this);
        if (this.excluded !== value) {
            this.excluded = value;
            this.signal('excluded:changed');
        }
    }

    /**
     * Precedence: excluded (red cross) → included (orange plus).
     * @returns {{iconClass: string, colorClass: string}|null}
     */
    getStateOverlay() {
        if (this.excluded) {
            return {iconClass: 'fa-times-circle', colorClass: 'text-danger'};
        } else if (this.included) {
            return {iconClass: 'fa-plus-circle', colorClass: 'text-warning'};
        }
        return null;
    }

    toString() {
        return 'SearchEnemy-' + this.id;
    }
}
