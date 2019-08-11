class EnemyVisualModifierRaidMarker extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.raid_marker_name;
    }

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

    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, 'this is not an EnemyVisualModifierRaidMarker!', this);

        return {
            classes: this.iconName === '' || this.iconName === null ? '' : this.iconName + '_enemy_icon',
            left: (width / 2) + margin - 13,
            top: (height / 2) + margin - 13
        };
    }
}