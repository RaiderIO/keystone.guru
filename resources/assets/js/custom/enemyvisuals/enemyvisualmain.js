class EnemyVisualMain extends EnemyVisualIcon {
    constructor(enemyvisual) {
        super(enemyvisual);

        let self = this;

        // Listen to changes in the NPC to update the icon and re-draw the visual
        this.enemyvisual.enemy.register('enemy:set_npc', this, function () {
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
            let state = getState();
            if (state.hasEnemyAggressivenessBorder()) {
                mainVisualOuterClasses.push(npc.aggressiveness);
            }

            if (state.hasEnemyDangerousBorder() && (npc.dangerous || this.enemyvisual.enemy.isImportant())) {
                mainVisualInnerClasses.push('dangerous');
                if( this.enemyvisual.enemy.isRareNpc() ) {
                    mainVisualInnerClasses.push('rare');
                }
            } else if (this.enemyvisual.enemy.isAwakenedNpc()) {
                mainVisualInnerClasses.push('awakened');
            } else if (this.enemyvisual.enemy.enemy_patrol_id !== null) {
                mainVisualInnerClasses.push('patrol');
            }

            let mapContext = state.getMapContext();
            let hasShroudedAffix = mapContext.hasAffix(AFFIX_SHROUDED);
            if (this.enemyvisual.enemy.isShrouded() && hasShroudedAffix) {
                mainVisualInnerClasses.push('shrouded');
            } else if (this.enemyvisual.enemy.isShroudedZulGamux() && hasShroudedAffix) {
                mainVisualInnerClasses.push('shrouded_zul_gamux');
            } else if (this.enemyvisual.enemy.isNotShrouded() && state.isMapAdmin()) {
                mainVisualInnerClasses.push('no_shrouded');
            } else if (this.enemyvisual.enemy.isInspiring()) {
                mainVisualInnerClasses.push('inspiring');
            } else if (this.enemyvisual.enemy.isEncrypted()) {
                mainVisualInnerClasses.push('encrypted');
            } else if (this.enemyvisual.enemy.isPridefulNpc()) {
                mainVisualInnerClasses.push('prideful');
            } else if (this.enemyvisual.enemy.isTormented()) {
                mainVisualInnerClasses.push('tormented');
            }
        }

        // Any additional classes to add for when the enemy is selectable
        // let selectionClasses = [];
        // if (this.enemyvisual.enemy.isSelectable()) {
        //     selectionClasses.push('selected_enemy_icon');
        // }

        return $.extend(data, {
            // Set the main icon
            main_visual_outer_classes: mainVisualOuterClasses.join(' '),
            main_visual_inner_classes: mainVisualInnerClasses.join(' '),
            selection_classes: [] // selectionClasses.join(' ')
        });
    }

    /**
     * Must be overridden by implementing classes
     * @protected
     */
    _refreshNpc() {

    }

    /**
     * @param textLength {Number}
     * @returns {*}
     * @protected
     */
    _getTextWidth(textLength = 1) {
        let size = this.enemyvisual.mainVisual.getSize();
        let width = size.iconSize[0];

        width -= c.map.enemy.calculateMargin(width);

        // More characters to display..
        if (textLength >= 4) {
            width -= 22;
        } else if (textLength === 3) {
            width -= 17;
        } else if (textLength === 2) {
            width -= 14;
        } else {
            width -= 10;
        }
        // Dangerous = less space
        if ((this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.dangerous) || this.enemyvisual.enemy.isImportant() || this.enemyvisual.enemy.enemy_patrol_id !== null) {
            width -= 2;
            // Obsolete enemies require additional subtraction to keep it looking nice
            if (this.enemyvisual.enemy.isObsolete()) {
                width -= 3;
            }
        }


        // Inverse zoom
        width += (c.map.settings.maxZoom - getState().getMapZoomLevel());

        return width;
    }

    getSize() {
        console.assert(this instanceof EnemyVisualMain, 'this is not an EnemyVisualMain!', this);

        let state = getState();
        let zoomLevelOffset = state.getMapZoomLevel() * 2;

        // Don't do expensive calculations if we don't need to
        if (this._sizeCache.hasOwnProperty(zoomLevelOffset)) {
            return this._sizeCache[zoomLevelOffset];
        }

        let mapContext = state.getMapContext();
        let health = this.enemyvisual.enemy.npc === null ? 0 : this.enemyvisual.enemy.npc.base_health * ((this.enemyvisual.enemy.npc.health_percentage ?? 100) / 100);
        if (this.enemyvisual.enemy.npc === null) {
            if (!state.isMapAdmin()) {
                console.warn('Enemy has no NPC!', this.enemyvisual.enemy);
            }
        } else {
            // Special catch for all dungeon enemies
            if (this.enemyvisual.enemy.npc.dungeon_id === -1) {
                health = (mapContext.getNpcsMinHealth() + mapContext.getNpcsMaxHealth()) / 2;
            }
        }
        let calculatedSize = c.map.enemy.calculateSize(
            health,
            mapContext.getNpcsMinHealth(),
            mapContext.getNpcsMaxHealth()
        );

        // Smaller MDT icons to make it easier to link them
        if (this.enemyvisual.enemy.is_mdt) {
            calculatedSize *= c.map.enemy.mdt_size_factor;
        }

        // If boss, grow
        if (this.enemyvisual.enemy.npc !== null && [NPC_CLASSIFICATION_ID_BOSS, NPC_CLASSIFICATION_ID_FINAL_BOSS].includes(this.enemyvisual.enemy.npc.classification_id)) {
            calculatedSize *= c.map.enemy.boss_size_factor;
        }


        this._sizeCache[zoomLevelOffset] = {
            iconSize: [calculatedSize + zoomLevelOffset, calculatedSize + zoomLevelOffset]
        };
        return this.getSize();
    }

    /**
     * @returns {string}
     */
    getName() {
        return 'EnemyVisualName';
    }

    cleanup() {
        super.cleanup();
        console.assert(this instanceof EnemyVisualMain, 'this is not an EnemyVisualMain!', this);

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
    }
}
