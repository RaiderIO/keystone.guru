class EnemyVisualMainEnemyForces extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'enemy_forces';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return ['enemy_forces'];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemyForces, 'this is not an EnemyVisualMainEnemyForces!', this);

        let data = super._getTemplateData();

        let enemyForces = this.enemyvisual.enemy.getEnemyForces();

        let size = this.enemyvisual.mainVisual.getSize();
        let width = size.iconSize[0];

        let margin = c.map.enemy.calculateMargin(width);
        width -= margin;

        // More characters to display..
        if (enemyForces >= 10) {
            width -= 7;
        }
        // Dangerous = less space
        else if( this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.dangerous ) {
            width -= 6;
        }

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_forces text-white text-center';
        // Slightly hacky fix to get the enemy forces to show up properly (font was changed away from Leaflet default to site default for all others)
        data.main_visual_html = `<div style="font: 12px 'Helvetica Neue', Arial, Helvetica, sans-serif; font-size: ${width}px; line-height: ${width}px;">${enemyForces}</div>`;

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    // getSize() {
    //     console.assert(this instanceof EnemyVisualMainEnemyForces, 'this is not an EnemyVisualMainEnemyForces!', this);
    //
    //     return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    // }
}