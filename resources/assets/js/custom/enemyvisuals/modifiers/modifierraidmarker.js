class EnemyVisualModifierRaidMarker extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        this.enemyvisual.enemy.register('enemy:set_raid_marker', this, this._refreshRaidMarker.bind(this));
        this.enemyvisual.register('enemyvisual:builtvisual', this, this._visualBuilt.bind(this));
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
        result['modifier_' + this.index + '_classes'] = this.iconName === '' ? '' : this.iconName + '_enemy_icon';
        return result;
    }

    _refreshRaidMarker() {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, this, 'this is not an EnemyVisualModifierRaidMarker!');

        this.setIcon(this.enemyvisual.enemy.raid_marker_name);
    }

    _visualBuilt() {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, this, 'this is not an EnemyVisualModifierRaidMarker!');
        let element = this.enemyvisual.layer._icon;
        console.assert(element instanceof Element, this, 'element is not an Element! (Leaflet changed their internal structure?)');

        let $element = $(element);
        let us = $element.find('.modifier_' + this.index);
        if (this.iconName !== '') {
            us.show();
        } else {
            us.hide();
        }
    }

    cleanup() {
        console.assert(this instanceof EnemyVisualModifierRaidMarker, this, 'this is not an EnemyVisualModifierRaidMarker!');

        this.enemyvisual.enemy.unregister('enemy:set_raid_marker', this);
    }
}