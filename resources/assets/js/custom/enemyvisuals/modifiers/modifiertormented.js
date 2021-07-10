class EnemyVisualModifierTormented extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = 'tormented';
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return 'tormented';
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'tormented',
        ];
    }

    /**
     * @inheritDoc
     */
    _getVisibleAtZoomLevel(){
        return c.map.enemy.tormented_display_zoom;
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
        console.assert(this instanceof EnemyVisualModifierTormented, 'this is not an EnemyVisualModifierTormented!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external modifier_' + this.iconName,
        });
    }
}