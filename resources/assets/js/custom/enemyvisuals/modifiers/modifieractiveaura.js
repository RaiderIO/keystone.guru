class EnemyVisualModifierActiveAura extends EnemyVisualModifier {
    constructor(enemyvisual, index, auraId) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.aura = getState().getMapContext().findAuraById(parseInt(auraId));

        this.iconName = convertToSlug(this.aura.name);
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return this.iconName;
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return ['*'];
    }

    /**
     * @inheritDoc
     */
    _getVisibleAtZoomLevel() {
        return c.map.enemy.active_aura_display_zoom;
    }

    /**
     * @inheritDoc
     */
    _getLocation(width, height, margin) {
        // Top left corner
        return {
            // - 5 because index is usually reserved for the corners, we start at 5
            left: -8 + ((this.index - 5) * 18), // 16px wide; divided by 2
            top: height + 2 // 2 px padding
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierActiveAura, 'this is not an EnemyVisualModifierActiveAura!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external ' + this.iconName,
            html: `<img src="${this.aura.icon_url}" width="16px"/>`
        });
    }
}