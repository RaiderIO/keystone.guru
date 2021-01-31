class EnemyVisualMainEnemyForces extends EnemyVisualMain {

    constructor(enemyvisual) {
        super(enemyvisual);

        this.iconName = 'enemy_forces';
    }

    _getValidIconNames() {
        // Nothing is valid, we don't work with icon names. One size fits all!
        return ['enemy_forces'];
    }

    _getTemplateData() {
        console.assert(this instanceof EnemyVisualMainEnemyForces, 'this is not an EnemyVisualMainEnemyForces!', this);

        let data = super._getTemplateData();

        let displayText = this._getDisplayText();
        let width = this._getWidth();

        // Just append a single class
        data.main_visual_outer_classes += ' enemy_icon_npc_enemy_forces text-white text-center';
        // Slightly hacky fix to get the enemy forces to show up properly (font was changed away from Leaflet default to site default for all others)
        data.main_visual_html = `<div 
            id="map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_forces" 
            class="align-middle text-center" style="display: flex; width: 100%; height: 100%; font: 12px 'Helvetica Neue', Arial, Helvetica, sans-serif; font-size: ${width}px;">
                <div class="my-auto w-100">${displayText}</div>
        </div>`;

        return data;
    }

    /**
     * Called whenever the NPC of the enemy has been refreshed.
     */
    _refreshNpc() {
        // Re-draw the visual
        this.setIcon(this.iconName);
    }

    /**
     *
     * @returns {number}
     * @private
     */
    _getDisplayText() {
        return getState().getKillZonesNumberStyle() === KILL_ZONES_NUMBER_STYLE_ENEMY_FORCES ?
            this.enemyvisual.enemy.getEnemyForces() :
            `${getFormattedPercentage(this.enemyvisual.enemy.getEnemyForces(), this.enemyvisual.map.getEnemyForcesRequired())}`;
    }

    /**
     *
     * @returns {*}
     * @private
     */
    _getWidth() {
        let displayText = this._getDisplayText();
        let size = this.enemyvisual.mainVisual.getSize();
        let width = size.iconSize[0];

        width -= c.map.enemy.calculateMargin(width);

        // More characters to display..
        if (displayText.length >= 4) {
            width -= 22;
        } else if (displayText.length === 3) {
            width -= 17;
        } else if (displayText.length === 2) {
            width -= 14;
        } else {
            width -= 10;
        }
        // Dangerous = less space
        if (this.enemyvisual.enemy.npc !== null && this.enemyvisual.enemy.npc.dangerous) {
            width -= 2;
        }

        // Inverse zoom
        width += (5 - getState().getMapZoomLevel());

        return width;
    }

    /**
     *
     */
    refreshSize() {
        super.refreshSize();

        let width = this._getWidth();
        $(`#map_enemy_visual_${this.enemyvisual.enemy.id}_enemy_forces`)
            .css('font-size', `${width}px`)
            // .css('line-height', `${width}px`)
        ;
    }

    /**
     *
     * @returns {*}
     */
    shouldRefreshOnNumberStyleChanged() {
        return true;
    }

    cleanup() {
        super.cleanup();

    }
}