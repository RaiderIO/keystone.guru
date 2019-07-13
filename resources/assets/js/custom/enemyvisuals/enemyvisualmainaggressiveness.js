class EnemyVisualMainAggressiveness extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'unset';
        // Set the icon initially to draw the current npc
        this._updateIconName();

        // Register to see if this enemy has any changes to its MDT connected states
        this.enemyvisual.enemy.register('mdt_connected', this, this._refresh.bind(this));
    }

    _getValidIconNames() {
        return [
            'aggressive',
            'neutral',
            'unfriendly',
            'friendly',
            'unset',
            'flagged',
            'boss',
            'mdt',
            'mdt_mismatched',
            'mdt_ok'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        let mainVisualClasses = ['enemy_icon', this.iconName + '_enemy_icon'];

        // Handle Teeming display
        if (this.enemyvisual.enemy.teeming === 'visible' || this.enemyvisual.enemy.teeming === 'hidden') {
            mainVisualClasses.push('teeming');
        }
        // Handle beguiling display
        if (this.enemyvisual.enemy.isBeguiling()) {
            mainVisualClasses.push('beguiling');
        }

        // Any additional classes to add for when the enemy is selectable
        let selectionClasses = [];
        if (this.enemyvisual.enemy.isSelectable()) {
            selectionClasses.push('selected_enemy_icon_' + (this.iconName === 'boss' ? 'big' : 'small'));
        }

        return {
            // Set the main icon
            main_visual_classes: mainVisualClasses.join(' '),
            selection_classes: selectionClasses.join(' ')
        };
    }

    /**
     * Updates the iconName property based on the enemy's current NPC.
     * @private
     */
    _updateIconName() {
        if (this.enemyvisual.enemy.is_mdt) {
            this.iconName = 'mdt';
            if (this.enemyvisual.enemy.npc === null) {
                this.iconName += '_mismatched';
            } else if (this.enemyvisual.enemy.getConnectedEnemy() !== null) {
                this.iconName += this.enemyvisual.enemy.isMismatched() ? '_mismatched' : '_ok';
            }
        } else {
            let npc = this.enemyvisual.enemy.npc;

            // May be null if not set at all (yet)
            if (npc !== null) {
                if (npc.enemy_forces === -1) {
                    this.iconName = 'flagged';
                }
                // @TODO Hard coded 3 = boss
                else if (npc.classification_id === 3) {
                    this.iconName = 'boss';
                } else {
                    this.iconName = npc.aggressiveness;
                }
            } else {
                this.iconName = 'unset';
            }
        }
    }

    /**
     * The NPC on the enemy has been refreshed; rebuild the visual to match.
     */
    _refreshNpc() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        this._refresh();
    }

    /**
     * Refreshes the visual.
     * @private
     */
    _refresh() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');
        // Update the icon to a new icon as necessary
        this._updateIconName();
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    getSize() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    }

    cleanup() {
        super.cleanup();

        // No longer interested in this
        this.enemyvisual.enemy.unregister('mdt_connected', this);
    }
}