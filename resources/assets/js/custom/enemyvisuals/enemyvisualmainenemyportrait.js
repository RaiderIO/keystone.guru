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

        let enemy = this.enemyvisual.enemy;
        let overlay = enemy.getStateOverlay();
        let enemyPortraitUrl = enemy.npc === null ?
            `${this.enemyvisual.map.options.assetsBaseUrl}/images/enemyportraits/unknown.png` :
            `${this.enemyvisual.map.options.assetsBaseUrl}/${enemy.npc.enemy_portrait_url}`;
        let template = Handlebars.templates['map_enemy_visual_enemy_portrait_template'];

        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            id: enemy.id,
            // Hide the portrait when an overlay is active
            enemy_portrait_url: overlay !== null ? null : enemyPortraitUrl,
            // Expensive calculation - only do it when we're going to use it
            width: overlay !== null ? this._getTextWidth(3) : 0,
            state_icon: overlay !== null ? overlay.iconClass : null,
            state_color: overlay !== null ? overlay.colorClass : null,
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
        $(`#map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_portrait .enemy_state`)
            .css('font-size', `${width}px`);
    }
}
