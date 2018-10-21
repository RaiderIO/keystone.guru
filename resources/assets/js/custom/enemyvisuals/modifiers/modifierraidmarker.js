class EnemyVisualModifierRaidMarker extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
    }

    _getValidIconNames() {
        return [
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
        let result = [];
        result['modifier_' + this.index + '_classes'] = this.iconName + '_enemy_icon';
        return result;
    }
}