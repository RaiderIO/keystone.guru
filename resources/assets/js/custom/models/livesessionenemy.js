class LiveSessionEnemy extends Enemy {
    constructor(map, layer) {
        super(map, layer);

        this.killed = false;
        this.obsolete = false;
        this.overpulledKillZoneId = null;
        this.inCombat = false;
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
     * @returns {boolean}
     */
    isInCombat() {
        return this.inCombat;
    }

    /**
     * @param value {boolean}
     */
    setInCombat(value) {
        console.assert(this instanceof LiveSessionEnemy, 'this is not a LiveSessionEnemy', this);
        if (this.inCombat !== value) {
            this.inCombat = value;
            this.signal('incombat:changed');
        }
    }

    /**
     * Precedence: overpulled (orange plus) → killed (green check) → in combat (crosshairs) → obsolete
     * (red cross). A detected kill is either on-route (killed) or off-route (overpulled), never both, so
     * their relative order doesn't matter in practice. In-combat is checked before obsolete because
     * "obsolete" only means "this planned enemy can be skipped" — nothing stops a player from re-engaging
     * it anyway, and while it's actively being fought that live state is more useful to show than the
     * stale skip suggestion.
     * @returns {{iconClass: string, colorClass: string}|null}
     */
    getStateOverlay() {
        if (this.overpulledKillZoneId !== null) {
            return {iconClass: 'fa-plus-circle', colorClass: 'text-warning'};
        } else if (this.killed) {
            return {iconClass: 'fa-check-circle', colorClass: 'text-success'};
        } else if (this.inCombat) {
            return {iconClass: 'fa-crosshairs', colorClass: 'text-danger'};
        } else if (this.obsolete) {
            return {iconClass: 'fa-times-circle', colorClass: 'text-danger'};
        }
        return null;
    }

    toString() {
        return 'LiveSessionEnemy-' + this.id;
    }
}
