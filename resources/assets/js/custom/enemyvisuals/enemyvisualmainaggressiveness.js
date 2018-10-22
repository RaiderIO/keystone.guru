class EnemyVisualMainAggressiveness extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'unset';
        this.enemyvisual.enemy.register('enemy:set_npc', this, this._refreshNpc.bind(this));
        if( this.enemyvisual.enemy.npc !== null ){
            this._refreshNpc();
        }
    }

    _getValidIconNames() {
        return [
            'aggressive',
            'neutral',
            'unfriendly',
            'friendly',
            'unset',
            'flagged',
            'boss'
        ];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        return {
            // Set the main icon
            main_visual_classes: (this.iconName + '_enemy_icon ') +
                // If we're in kill zone mode..
                (this.enemyvisual.enemy.isKillZoneSelectable() ?
                    // Adjust the size of the icon based on whether we're going big or small
                    'killzone_enemy_icon_' + (this.iconName === 'boss' ? 'big' : 'small')
                    : '')
        };
    }

    /**
     * The NPC on the enemy has been refreshed; rebuild the visual to match.
     */
    _refreshNpc(){
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        let npc = this.enemyvisual.enemy.npc;
        // console.log(signalEvent, npc);

        // May be null if not set at all (yet)
        if (npc !== null) {
            if (npc.enemy_forces === -1) {
                this.setIcon('flagged');
            }
            // @TODO Hard coded 3 = boss
            else if (npc.classification_id === 3) {
                this.setIcon('boss');
            } else {
                this.setIcon(npc.aggressiveness);
            }
        } else {
            this.setIcon('unset');
        }
    }

    getSize() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        return this.iconName === 'boss' ? _bigIcon : _smallIcon;
    }

    cleanup() {
        console.assert(this instanceof EnemyVisualMainAggressiveness, this, 'this is not an EnemyVisualMainAggressiveness!');

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
    }
}