class EnemyVisualModifierAwakened extends EnemyVisualModifier {
    constructor(enemyvisual, index) {
        super(enemyvisual, index);
        // If it's loaded already, set it now
        this.iconName = this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.dungeon_id === -1 ? 'awakened' : '';
    }

    _getValidIconNames() {
        return [
            '', // we are allowed to have nothing
            'awakened',
        ];
    }

    _getTemplateData(width, height, margin) {
        console.assert(this instanceof EnemyVisualModifierAwakened, 'this is not an EnemyVisualModifierAwakened!', this);

        // Bottom left corner
        return {
            classes: 'modifier_external ' + this.iconName,
            left: width,
            top: height
        };
    }
}