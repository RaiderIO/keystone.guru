class EnemyVisualMain extends EnemyVisualIcon {
    constructor(enemyvisual) {
        super(enemyvisual);

        let self = this;

        // Listen to changes in the NPC to update the icon and re-draw the visual
        this.enemyvisual.enemy.register('enemy:set_npc', this, function(){
            self._refreshNpc();
        });
        this._sizeCache = [];
    }

    _getTemplateData(width, height, margin) {
        let data = super._getTemplateData(width, height, margin);

        let mainVisualOuterClasses = [];
        let mainVisualInnerClasses = ['enemy_icon', this.iconName];

        // Handle Teeming display
        let npc = this.enemyvisual.enemy.npc;
        if (npc !== null) {
            mainVisualOuterClasses.push(npc.aggressiveness);

            mainVisualInnerClasses.push(npc.dangerous ? 'dangerous' : '');
        }

        // Any additional classes to add for when the enemy is selectable
        let selectionClasses = [];
        // if (this.enemyvisual.enemy.isSelectable()) {
        //     selectionClasses.push('selected_enemy_icon');
        // }

        return $.extend(data, {
            // Set the main icon
            main_visual_outer_classes: mainVisualOuterClasses.join(' '),
            main_visual_inner_classes: mainVisualInnerClasses.join(' '),
            selection_classes: selectionClasses.join(' ')
        });
    }

    /**
     * Must be overridden by implementing classes
     * @protected
     */
    _refreshNpc() {

    }

    getSize() {
        console.assert(this instanceof EnemyVisualMain, 'this is not an EnemyVisualMain!', this);

        let zoomLevelOffset = getState().getMapZoomLevel() * 2;

        // Don't do expensive calculations if we don't need to
        if( this._sizeCache.hasOwnProperty(zoomLevelOffset) ) {
            return this._sizeCache[zoomLevelOffset];
        }

        let health = this.enemyvisual.enemy.npc === null ? 0 : this.enemyvisual.enemy.npc.base_health;
        if (this.enemyvisual.enemy.npc === null) {
            console.warn('Enemy has no NPC!', this.enemyvisual.enemy);
        } else {
            // Special catch for all dungeon enemies
            if (this.enemyvisual.enemy.npc.dungeon_id === -1) {
                health = (this.enemyvisual.map.options.npcsMinHealth + this.enemyvisual.map.options.npcsMaxHealth) / 2;
            }
        }
        let calculatedSize = c.map.enemy.calculateSize(
            health,
            this.enemyvisual.map.options.npcsMinHealth,
            this.enemyvisual.map.options.npcsMaxHealth
        );

        // Smaller MDT icons to make it easier to link them
        if (this.enemyvisual.enemy.is_mdt) {
            calculatedSize /= 2;
        }


        this._sizeCache[zoomLevelOffset] = {
            iconSize: [calculatedSize + zoomLevelOffset, calculatedSize + zoomLevelOffset]
        };
        return this.getSize();
    }

    cleanup() {
        super.cleanup();
        console.assert(this instanceof EnemyVisualMain, 'this is not an EnemyVisualMain!', this);

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
    }
}