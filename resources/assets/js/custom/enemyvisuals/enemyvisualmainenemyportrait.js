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

        let enemyPortraitUrl = this.enemyvisual.enemy.npc === null ?
            `${this.enemyvisual.map.options.assetsBaseUrl}/images/enemyportraits/unknown.png` :
            `${this.enemyvisual.map.options.assetsBaseUrl}/${this.enemyvisual.enemy.npc.enemy_portrait_url}`;
        let template = Handlebars.templates['map_enemy_visual_enemy_portrait_template'];

        let isObsoleteOrOverpulled = this.enemyvisual.enemy.isObsolete() || this.enemyvisual.enemy.getOverpulledKillZoneId() !== null;
        let mainVisualData = $.extend({}, getHandlebarsDefaultVariables(), {
            id: this.enemyvisual.enemy.id,
            // Hide the portrait when obsolete or overpulled
            enemy_portrait_url: isObsoleteOrOverpulled ? null : enemyPortraitUrl,
            // Expensive calculation - only do it when we're going to use it
            width: isObsoleteOrOverpulled ? this._getTextWidth(3) : 0,
            obsolete: this.enemyvisual.enemy.isObsolete(),
            overpulled: this.enemyvisual.enemy.getOverpulledKillZoneId() !== null
        });

        data.main_visual_html = template(mainVisualData);

        return data;
    }

    /**
     * @inheritDoc
     */
    getCanvasContent() {
        let isObsoleteOrOverpulled = this.enemyvisual.enemy.isObsolete() || this.enemyvisual.enemy.getOverpulledKillZoneId() !== null;
        let enemyPortraitUrl = this.enemyvisual.enemy.npc === null ?
            `${this.enemyvisual.map.options.assetsBaseUrl}/images/enemyportraits/unknown.png` :
            `${this.enemyvisual.map.options.assetsBaseUrl}/${this.enemyvisual.enemy.npc.enemy_portrait_url}`;

        let text = null;
        if (this.enemyvisual.enemy.isObsolete()) {
            text = {value: null, classes: 'obsolete text-danger fa fa-times-circle', fontSize: this._getTextWidth(3)};
        } else if (this.enemyvisual.enemy.getOverpulledKillZoneId() !== null) {
            text = {value: null, classes: 'overpulled text-success fa fa-plus-circle', fontSize: this._getTextWidth(3)};
        }

        return {
            classes: 'enemy_icon_npc_enemy_portrait_inner',
            imageUrl: isObsoleteOrOverpulled ? null : enemyPortraitUrl,
            text: text,
        };
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
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyVisualMainEnemyPortrait,
    };
}
