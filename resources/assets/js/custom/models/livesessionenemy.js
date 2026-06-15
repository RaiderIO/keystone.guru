class LiveSessionEnemy extends Enemy {
    constructor(map, layer) {
        super(map, layer);

        this.killed = false;
        this.obsolete = false;
        this.overpulledKillZoneId = null;
    }

    /**
     * @returns {boolean}
     */
    isKilled() {
        return this.killed;
    }

    /**
     * @param value {boolean}
     */
    setKilled(value) {
        console.assert(this instanceof LiveSessionEnemy, 'this is not a LiveSessionEnemy', this);
        if (this.killed !== value) {
            this.killed = value;
            this.signal('killed:changed');
        }
    }

    /**
     * @returns {boolean}
     */
    isObsolete() {
        return this.obsolete;
    }

    /**
     * @param value {boolean}
     */
    setObsolete(value) {
        console.assert(this instanceof LiveSessionEnemy, 'this is not a LiveSessionEnemy', this);
        if (this.obsolete !== value) {
            this.obsolete = value;
            this.signal('obsolete:changed');
        }
    }

    /**
     * @returns {Number|null}
     */
    getOverpulledKillZoneId() {
        return this.overpulledKillZoneId;
    }

    /**
     * @param killZoneId {Number|null}
     */
    setOverpulledKillZoneId(killZoneId) {
        console.assert(this instanceof LiveSessionEnemy, 'this is not a LiveSessionEnemy', this);
        if (this.overpulledKillZoneId !== killZoneId) {
            this.overpulledKillZoneId = killZoneId;
            this.signal('overpulled:changed');
        }
    }

    /**
     * Precedence: killed (green check) → obsolete (red cross) → overpulled (orange plus).
     * @returns {{iconClass: string, colorClass: string}|null}
     */
    getStateOverlay() {
        if (this.killed) {
            return {iconClass: 'fa-check-circle', colorClass: 'text-success'};
        } else if (this.obsolete) {
            return {iconClass: 'fa-times-circle', colorClass: 'text-danger'};
        } else if (this.overpulledKillZoneId !== null) {
            return {iconClass: 'fa-plus-circle', colorClass: 'text-warning'};
        }
        return null;
    }

    toString() {
        return 'LiveSessionEnemy-' + this.id;
    }
}
