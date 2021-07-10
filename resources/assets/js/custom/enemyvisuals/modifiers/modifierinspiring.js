class EnemyVisualModifierInspiring extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = 'inspiring';
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return 'inspiring';
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'inspiring',
        ];
    }

    /**
     * @inheritDoc
     */
    _getVisibleAtZoomLevel(){
        return c.map.enemy.inspiring_display_zoom;
    }

    /**
     * @inheritDoc
     */
    _getLocation(width, height, margin) {
        // Bottom left
        return {
            left: width - 6, // width of 16px / 2, then 2 px more because it looks better
            top: height - 10, // height of 16px / 2, then 2 px more because it looks better
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierInspiring, 'this is not an EnemyVisualModifierInspiring!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external modifier_' + this.iconName,
        });
    }
}