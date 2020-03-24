class EnemyVisualMainEnemyPortrait extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'enemy_portrait';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return [];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemyPortrait, 'this is not an EnemyVisualMainEnemyPortrait!', this);

        let data = super._getTemplateData();

        let size = this.enemyvisual.mainVisual.getSize();
        let width = size.iconSize[0];

        let margin = c.map.enemy.calculateMargin(width);
        width -= margin;

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_portrait text-white text-center';

        let npcId = this.enemyvisual.enemy.npc === null ? 'unknown' : this.enemyvisual.enemy.npc.id;
        data.main_visual_html = `<div style="width:100%; height: 100%; background-image: url('/images/enemyportraits/${npcId}.png'); background-size: contain; border-radius: ${width}px;">&nbsp;</div>`;

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        // Re-draw the visual
        this.setIcon(this.iconName);
    }
}