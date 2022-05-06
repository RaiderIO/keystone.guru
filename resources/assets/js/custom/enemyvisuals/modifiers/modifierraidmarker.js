class EnemyVisualModifierRaidMarker extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.raid_marker_name;
    }

    /**
     * @inheritDoc
     */
    _getName() {
        return 'raidmarker';
    }

    /**
     * @inheritDoc
     */
    _getValidIconNames() {
        return [
            '', // we are allowed to unset
            'star',
            'circle',
            'diamond',
            'triangle',
            'moon',
            'square',
            'cross',
            'skull'
        ];
    }

    /**
     * @inheritDoc
     */
    _getLocation(width, height, margin) {
        // Center
        return {
            left: (width / 2) - 13,
            top: (height / 2) - 13
        }
    }

    /**
     * @inheritDoc
     */
    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, 'this is not an EnemyVisualModifierRaidMarker!', this);

        return $.extend({}, super._getTemplateData(width, height, margin), this._getLocation(width, height, margin), {
            classes: this.iconName === '' || this.iconName === null ? '' : `raid_marker_enemy_icon ${this.iconName}_enemy_icon`,
        });
    }
}
