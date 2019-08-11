class EnemyVisualIcon extends Signalable {
    constructor(enemyvisual) {
        super();
        console.assert(enemyvisual instanceof EnemyVisual, enemyvisual, 'enemyvisual was not an EnemyVisual');
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
        console.assert(this instanceof EnemyVisualIcon, this, 'this was not an EnemyVisualIcon');
        return {id: this.enemyvisual.enemy.id};
    }

    /**
     *
     * @param name
     */
    setIcon(name) {
        console.assert(this._getValidIconNames().indexOf(name) >= 0, this, 'Invalid icon name passed -> ' + name);
        this.iconName = name;
        this.enemyvisual._buildVisual();
    }

    cleanup() {

    }
}