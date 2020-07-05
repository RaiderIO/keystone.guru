class EnemyVisualModifierClassification extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.classification_id !== 1 ? 'elite' : '';
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return 'classification';
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'elite',
        ];
    }

    /**
     * @inheritDoc
     */
    _getVisibleAtZoomLevel() {
        return c.map.enemy.classification_display_zoom;
    }

    /**
     * @inheritDoc
     */
    _getLocation(width, height, margin) {
        // Top right
        return {
            left: width,
            top: 0
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierClassification, 'this is not an EnemyVisualModifierClassification!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: 'modifier_external ' + (this.iconName === '' || this.iconName === null ? '' : 'classification_icon_' + this.iconName),
        });
    }
}