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
     *
     * @param name
     */
    setIcon(name) {
        console.assert(this._getValidIconNames().indexOf(name) >= 0, 'Invalid icon name passed -> ' + name, this);
        this.iconName = name;
        this.enemyvisual.buildVisual();
    }

    cleanup() {

    }
}