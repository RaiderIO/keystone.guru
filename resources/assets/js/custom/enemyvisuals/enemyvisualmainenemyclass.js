class EnemyVisualMainEnemyClass extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'melee';
        // Set the icon initially to draw the current npc
        this._updateIconName();

        // Register to see if this enemy has any changes to its MDT connected states
        this.enemyvisual.enemy.register('mdt_connected', this, this._refresh.bind(this));
    }

    _getValidIconNames() {
        return [
            'boss',
            'melee',
            'caster',
            'healer',
            'ranged',
            'enchanted',
            'tide',
            'void'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemyClass, 'this is not an EnemyVisualMainEnemyClass!', this);

        let data = super._getTemplateData();
        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_class';

        return data;
    }

    /**
     * Updates the iconName property based on the enemy's current NPC.
     * @private
     */
    _updateIconName() {
        let npc = this.enemyvisual.enemy.npc;
        if (npc !== null) {
            if (npc.classification_id >= 3) {
                this.iconName = 'boss';
            } else {
                // Enchanted Emissary
                if (npc.id === 155432) {
                    this.iconName = 'enchanted';
                } else if (npc.id === 155433) {
                    this.iconName = 'void';
                } else if (npc.id === 155434) {
                    this.iconName = 'tide';
                } else {
                    this.iconName = npc.class.name.toLowerCase();
                }
            }
        }
    }

    /**
     * The NPC on the enemy has been refreshed; rebuild the visual to match.
     */
    _refreshNpc() {
        console.assert(this instanceof EnemyVisualMainEnemyClass, 'this is not an EnemyVisualMainEnemyClass!', this);

        this._refresh();
    }

    /**
     * Refreshes the visual.
     * @private
     */
    _refresh() {
        console.assert(this instanceof EnemyVisualMainEnemyClass, 'this is not an EnemyVisualMainEnemyClass!', this);
        // Update the icon to a new icon as necessary
        this._updateIconName();
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    // getSize() {
    //     console.assert(this instanceof EnemyVisualMainEnemyClass, 'this is not an EnemyVisualMainEnemyClass!', this);
    //
    //     return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    // }

    cleanup() {
        super.cleanup();

        // No longer interested in this
        this.enemyvisual.enemy.unregister('mdt_connected', this);
    }
}