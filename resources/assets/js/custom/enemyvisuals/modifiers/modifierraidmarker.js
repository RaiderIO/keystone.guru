class EnemyVisualModifierRaidMarker extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.raid_marker_name;
        // If it changed, let us know!
        this.enemyvisual.enemy.register('enemy:set_raid_marker', this, this._refreshRaidMarker.bind(this));
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

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, this, 'this is not an EnemyVisualModifierRaidMarker!');

        let result = [];
        result['modifier_' + this.index + '_classes'] = this.iconName === '' || this.iconName === null ? '' : this.iconName + '_enemy_icon';
        return result;
    }

    _refreshRaidMarker() {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, this, 'this is not an EnemyVisualModifierRaidMarker!');

        this.setIcon(this.enemyvisual.enemy.raid_marker_name);
    }

    cleanup() {
        super.cleanup();
        console.assert(this instanceof EnemyVisualModifierRaidMarker, this, 'this is not an EnemyVisualModifierRaidMarker!');

        this.enemyvisual.enemy.unregister('enemy:set_raid_marker', this);
    }
}