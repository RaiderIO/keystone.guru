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
        console.assert(this instanceof EnemyVisualMainEnemyClass, 'this is not an EnemyVisualMainEnemyClass!', this);

        let data = super._getTemplateData();
        // Just append a single class
        data.main_visual_outer_classes += ' badge badge-primary badge-pill badge_enemy_forces';
        data.main_visual_html = this.enemyvisual.enemy.getEnemyForces();

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc(){
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    // getSize() {
    //     console.assert(this instanceof EnemyVisualMainEnemyForces, this, 'this is not an EnemyVisualMainEnemyForces!');
    //
    //     return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    // }
}