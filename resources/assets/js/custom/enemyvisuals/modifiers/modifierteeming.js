class EnemyVisualModifierTeeming extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.teeming === 'visible' ? 'teeming' : '';
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return 'teeming';
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'teeming',
        ];
    }

    /**
     * @inheritDoc
     */
    _getVisibleAtZoomLevel(){
        return c.map.enemy.teeming_display_zoom;
    }

    /**
     * @inheritDoc
     */
    _getLocation(width, height, margin) {
        // Bottom left corner
        return {
            left: -8, // 16px wide; divided by 2
            top: height
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierTeeming, 'this is not an EnemyVisualModifierTeeming!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external ' + this.iconName,
        });
    }
}