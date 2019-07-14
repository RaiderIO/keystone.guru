class StateManager extends Signalable {


    constructor() {
        super();

        this.map = null;
        // What enemy visual type we're displaying
        this.enemyDisplayType = null;
        // The beguiling preset we're displaying
        this.beguilingPreset = null;
    }

    /**
     * Sets the dungeon map for the state manager.
     * @param map DungeonMap
     */
    setDungeonMap(map){
        this.map = map;

        this.setEnemyDisplayType(this.map.options.defaultEnemyVisualType);
        this.setBeguilingPreset(this.map.options.dungeonroute.beguilingPreset);
    }

    /**
     * Sets the visual type that is currently being displayed.
     * @param enemyDisplayType
     */
    setEnemyDisplayType(enemyDisplayType) {
        this.enemyDisplayType = enemyDisplayType;

        // Let everyone know it's changed
        this.signal('enemydisplaytype:changed', {enemyDisplayType: this.enemyDisplayType});
    }

    /**
     * Sets the beguiling preset that is currently displayed on the map.
     * @param preset int
     */
    setBeguilingPreset(preset) {
        this.beguilingPreset = preset;

        // Let everyone know it's changed
        this.signal('beguilingpreset:changed', {beguilingPreset: this.beguilingPreset});
    }

    /**
     * Get the default visual to display for all enemies.
     * @returns {string}
     */
    getEnemyDisplayType() {
        return this.enemyDisplayType;
    }

    /**
     * Get the beguiling preset that is currently displayed on the map.
     * @returns {string}
     */
    getBeguilingPreset() {
        return this.beguilingPreset;
    }
}