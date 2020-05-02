class EnemyVisualModifierTeeming extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.teeming === 'visible' ? 'teeming' : '';
    }

    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'teeming',
        ];
    }

    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierTeeming, 'this is not an EnemyVisualModifierTeeming!', this);

        // Bottom left corner
        return {
            classes: 'modifier_external ' + this.iconName,
            left: -8, // 16px wide; divided by 2
            top: height
        };
    }
}