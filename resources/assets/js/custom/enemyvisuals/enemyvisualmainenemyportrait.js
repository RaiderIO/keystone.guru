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
        let template = Handlebars.templates['map_enemy_visual_enemy_portrait_template'];

        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            id: this.enemyvisual.enemy.id,
            npcId: npcId,
            // Expensive calculation - only do it when we're going to use it
            width: this.enemyvisual.enemy.isObsolete() || this.enemyvisual.enemy.getOverpulledKillZoneId() !== null ? this._getTextWidth() : 0,
            obsolete: this.enemyvisual.enemy.isObsolete(),
            overpulled: this.enemyvisual.enemy.getOverpulledKillZoneId() !== null
        });

        data.main_visual_html = template(mainVisualData);

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    /**
     * @returns {string}
     */
    getName() {
        return 'EnemyVisualMainEnemyPortrait';
    }

    /**
     *
     */
    refreshSize() {
        super.refreshSize();

        let width = this._getTextWidth();
        $(`#map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_portrait.obsolete, #map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_portrait.overpulled`)
            .css('font-size', `${width}px`)
        ;
    }
}
