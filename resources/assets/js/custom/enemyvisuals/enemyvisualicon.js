class EnemyVisualIcon extends Signalable {
    constructor(enemyvisual) {
        super();
        console.assert(enemyvisual instanceof EnemyVisual, 'enemyvisual was not an EnemyVisual', enemyvisual);
        this.enemyvisual = enemyvisual;
        this.iconName = '';
    }

    _getValidIconNames() {
        return [];
    }

    /**
     * Gets the data that's used to fill the enemy visual template.
     * @param width
     * @param height
     * @param margin
     * @returns {{id: *}}
     * @protected
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualIcon, 'this was not an EnemyVisualIcon', this);
        return {id: this.enemyvisual.enemy.id};
    }

    /**
     * Return true if the visual should be forced rebuilt always.
     * @returns {boolean}
     */
    shouldAlwaysRebuild() {
        // If the enemy we're displaying is marked as obsolete, we display text to indicate that it is so
        // This text needs to scale with zoom level, thus if it's marked as obsolete we should always rebuild the visual
        return this.enemyvisual.enemy.isObsolete() || this.enemyvisual.enemy.getOverpulledKillZoneId() !== null;
    }

    /**
     * Checks if this visual should be refreshed if the number style changed.
     * @returns {boolean}
     */
    shouldRefreshOnNumberStyleChanged() {
        return false;
    }

    /**
     * Called whenever the size of this visual should be refreshed
     */
    refreshSize() {

    }

    /**
     *
     * @param name
     */
    setIcon(name) {
        let validIconNames = this._getValidIconNames();
        // May enter * to match everything
        if (validIconNames.length > 0 && validIconNames[0] !== '*') {
            console.assert(this._getValidIconNames().indexOf(name) >= 0, 'Invalid icon name passed -> ' + name, this);
        }
        this.iconName = name;
        this.enemyvisual.buildVisual();
    }

    cleanup() {

    }
}