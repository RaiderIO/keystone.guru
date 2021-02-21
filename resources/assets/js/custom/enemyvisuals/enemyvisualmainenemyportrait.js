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

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_portrait text-white text-center';

        let npcId = this.enemyvisual.enemy.npc === null ? 'unknown' : this.enemyvisual.enemy.npc.id;
        // #040C1F is the same blue as the background color of portraits
        data.main_visual_html = `<div style="width:100%; height: 100%; background-image: url('/images/enemyportraits/${npcId}.png'); background-size: contain; background-color: #040C1F; border-radius: 100%;">&nbsp;</div>`;

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