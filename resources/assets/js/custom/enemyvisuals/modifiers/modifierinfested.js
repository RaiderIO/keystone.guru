class EnemyVisualModifierInfested extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);

        this.iconName = 'infested';
    }

    _getValidIconNames() {
        return [
            '', // we are allowed to unset
            'infested',
            'infested_enabled',
            'infested_disabled'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualModifierInfested, this, 'this is not an EnemyVisualModifierInfested!');

        let classes = 'affix_icon_modifier ';
        if( this.iconName !== '' ){
            classes += 'affix_icon_' + this.iconName;
            if( this.iconName === 'infested_enabled' ){
                classes += ' modifier_infested_enabled'
            } else if( this.iconName === 'infested_disabled' ){
                classes += ' modifier_infested_disabled'
            }
        }
                classes += ' modifier_infested_enabled';

        let result = [];
        result['modifier_' + this.index + '_classes'] = classes;
        result['modifier_' + this.index + '_html'] = '<div class="modifier_infested_enabled">&nbsp;</div>';
        return result;
    }
}