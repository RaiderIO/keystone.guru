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

        return {
            // Set the main icon
            main_visual_classes: 'enemy_icon ' + (this.iconName + '_enemy_icon '),
            selection_classes:
            // If we're in kill zone mode..
                (this.enemyvisual.enemy.isSelectable() ?
                    // Adjust the size of the icon based on whether we're going big or small
                    'selected_enemy_icon_' + (this.iconName === 'boss' ? 'big' : 'small')
                    : '')
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