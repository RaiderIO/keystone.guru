class EnemyVisualMainEnemySkippable extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = this.enemyvisual.enemy.skippable ? 'skippable' : 'not_skippable';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return ['skippable', 'not_skippable'];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemySkippable, 'this is not an EnemyVisualMainEnemySkippable!', this);

        let data = super._getTemplateData();

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_skippable text-center';
        // Slightly hacky fix to get the enemy forces to show up properly (font was changed away from Leaflet default to site default for all others)
        let template = Handlebars.templates['map_enemy_visual_enemy_skippable_template'];

        let displayText = this._getDisplayText();
        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            id: this.enemyvisual.enemy.id,
            displayText: displayText,
            skippable: this.enemyvisual.enemy.skippable ? 1 : 0,
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
        return this.enemyvisual.enemy.skippable ? 'Y' : 'N';
    }

    /**
     *
     */
    refreshSize() {
        super.refreshSize();

        let width = this._getTextWidth();
        $(`#map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_skippable`)
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

    /**
     * @returns {string}
     */
    getName() {
        return 'EnemyVisualMainEnemySkippable';
    }

    cleanup() {
        super.cleanup();

    }
}
