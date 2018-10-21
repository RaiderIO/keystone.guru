class EnemyVisualMainEnemyForces extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'unset';
    }

    _getValidIconNames() {
        return [
            'aggressive',
            'neutral',
            'unfriendly',
            'friendly',
            'unset',
            'flagged',
            'boss'];
    }

    _getTemplateData() {
        return {
            // Set the main html
            main_visual_html: this.enemyvisual.enemy.getEnemyForces()
        };
    }

    getSize() {
        return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    }
}