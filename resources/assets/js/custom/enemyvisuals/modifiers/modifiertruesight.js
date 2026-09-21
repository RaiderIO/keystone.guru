class EnemyVisualModifierTruesight extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.truesight ? 'truesight' : '';
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return 'truesight';
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'truesight',
        ];
    }

    /**
     * @inheritDoc
     */
    _getVisibleAtZoomLevel() {
        return c.map.enemy.truesight_display_zoom;
    }

    /**
     * @inheritDoc
     */
    _getLocation(width, height, margin) {
        // Top left corner
        return {
            left: -8, // 16px wide; divided by 2
            top: 0
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierTruesight, 'this is not an EnemyVisualModifierTruesight!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external ' + this.iconName,
        });
    }
}

// Exported for unit tests only. Guarded so it is inert in the browser, where `module` is undefined
// and this file is concatenated into the bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EnemyVisualModifierTruesight,
    };
}
