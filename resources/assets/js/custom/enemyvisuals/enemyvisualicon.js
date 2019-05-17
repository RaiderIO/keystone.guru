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

    _getTemplateData() {
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