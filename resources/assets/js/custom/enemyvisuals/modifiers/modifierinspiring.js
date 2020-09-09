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
            left: width,
            top: height
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierInspiring, 'this is not an EnemyVisualModifierAwakened!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external ' + this.iconName,
        });
    }
}