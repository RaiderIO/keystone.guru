class EnemyVisualMainAggressiveness extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'unset';
        // Set the icon initially to draw the current npc
        this._updateIcon();
        // Listen to changes in the NPC to update the icon and re-draw the visual
        this.enemyvisual.enemy.register('enemy:set_npc', this, this._refreshNpc.bind(this));
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
            'mdt'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        return {
            // Set the main icon
            main_visual_classes: (this.iconName + '_enemy_icon ') +
                // If we're in kill zone mode..
                (this.enemyvisual.enemy.isSelectable() ?
                    // Adjust the size of the icon based on whether we're going big or small
                    'killzone_enemy_icon_' + (this.iconName === 'boss' ? 'big' : 'small')
                    : '')
        };
    }

    /**
     * Updates the iconName property based on the enemy's current NPC.
     * @private
     */
    _updateIcon() {
        if( this.enemyvisual.enemy.is_mdt ){
            this.iconName = 'mdt';
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

        // Update the icon to a new icon as necessary
        this._updateIcon();
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    getSize() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
    }
}