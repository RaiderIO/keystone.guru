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

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_forces text-center';
        // Slightly hacky fix to get the enemy forces to show up properly (font was changed away from Leaflet default to site default for all others)
        let template = Handlebars.templates['map_enemy_visual_enemy_forces_template'];

        let displayText = this._getDisplayText();
        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            id: this.enemyvisual.enemy.id,
            displayText: displayText,
            width: this._getTextWidth(displayText.length),
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
     *
     * @returns {String}
     * @private
     */
    _getDisplayText() {
        return getState().getMapNumberStyle() === NUMBER_STYLE_ENEMY_FORCES ?
            `${this.enemyvisual.enemy.getEnemyForces()}` :
            `${getFormattedPercentage(this.enemyvisual.enemy.getEnemyForces(), this.enemyvisual.map.enemyForcesManager.getEnemyForcesRequired())}`;
    }

    /**
     *
     */
    refreshSize() {
        super.refreshSize();

        let width = this._getTextWidth();
        $(`#map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_forces`)
            .css('font-size', `${width}px`)
        ;
    }

    /**
     *
     * @returns {*}
     */
    shouldRefreshOnNumberStyleChanged() {
        return true;
    }

    cleanup() {
        super.cleanup();

    }
}