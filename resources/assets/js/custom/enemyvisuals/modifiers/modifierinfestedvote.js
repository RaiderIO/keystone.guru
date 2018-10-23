class EnemyVisualModifierInfestedVote extends EnemyVisualModifier {
    constructor(enemyvisual, index, yes) {
        super(enemyvisual, index);

        this.iconName = yes ? 'yes' : 'no';
    }

    _getValidIconNames() {
        return [
            'no',
            'yes'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualModifierInfestedVote, this, 'this is not an EnemyVisualModifierInfestedVote!');

        let result = [];
        result['modifier_' + this.index + '_html'] = this.iconName === 'yes' ? '+' : '-';
        result['modifier_' + this.index + '_classes'] = 'text-center modifier_infested_vote';
        return result;
    }
}