class EnemyVisualModifierClassification extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.npc.classification_id !== 1 ? 'elite' : '';
    }

    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'elite',
        ];
    }

    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierClassification, 'this is not an EnemyVisualModifierClassification!', this);

        return {
            classes: 'modifier_external ' + (this.iconName === '' || this.iconName === null ? '' : 'classification_icon_' + this.iconName),
            left: width,
            top: 0
        };
    }
}