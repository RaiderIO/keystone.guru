class EnemyVisualMainEnemyForces extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'unset';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return [];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemyForces, this, 'this is not an EnemyVisualMainEnemyForces!');

        return {
            // Set the main html
            main_visual_classes: 'badge badge-primary badge-pill badge_enemy_forces',
            main_visual_html: this.enemyvisual.enemy.getEnemyForces()
        };
    }

    getSize() {
        console.assert(this instanceof EnemyVisualMainEnemyForces, this, 'this is not an EnemyVisualMainEnemyForces!');

        return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    }
}