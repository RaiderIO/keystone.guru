class StateManager extends Signalable {


    constructor() {
        super();

        this.map = null;
        // What enemy visual type we're displaying
        this.enemyDisplayType = null;
        // The beguiling preset we're displaying
        this.beguilingPreset = null;
        // The currently displayed floor ID
        this.floorId = null;
        // Map zoom level
        this.mapZoomLevel = 1;
    }

    /**
     * Sets the dungeon map for the state manager.
     * @param map DungeonMap
     */
    setDungeonMap(map) {
        let self = this;

        // Unreg ourself if necessary
        if (this.map !== null) {
            this.map.unregister('map:mapobjectgroupsfetchsuccess', this);
        }

        this.map = map;

        this.setEnemyDisplayType(this.map.options.defaultEnemyVisualType);
        this.setBeguilingPreset(this.map.options.dungeonroute.beguilingPreset);
        this.setFloorId(this.map.options.floorId);

        // Change defaults based on the hash if necessary
        if (window.location.hash.length > 0) {
            this.map.register('map:mapobjectgroupsfetchsuccess', this, function () {
                console.log('changing defaults..');
                // Fill the hashVariables with key=>value pairs
                let hashVariables = {};
                let variables = window.location.hash.replace('#', '').split('&');

                for (let i = 0; i < variables.length; i++) {
                    let variable = variables[i];

                    let keyValue = variable.split('=');
                    hashVariables[keyValue[0]] = keyValue[1];
                }

                // Enemy display type
                if (hashVariables.hasOwnProperty('display')) {
                    self.setEnemyDisplayType(hashVariables.display);
                }

                // Beguiling preset
                if (hashVariables.hasOwnProperty('preset')) {
                    self.setBeguilingPreset(parseInt(hashVariables.preset));
                }
                console.log(hashVariables);
            });
        }
    }

    /**
     * Sets the visual type that is currently being displayed.
     * @param enemyDisplayType int
     */
    setEnemyDisplayType(enemyDisplayType) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this.enemyDisplayType = enemyDisplayType;

        // Let everyone know it's changed
        this.signal('enemydisplaytype:changed', {enemyDisplayType: this.enemyDisplayType});
    }

    /**
     * Sets the floor ID.
     * @param floorId int
     */
    setFloorId(floorId) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        this.floorId = floorId;

        // Let everyone know it's changed
        this.signal('floorid:changed', {floorId: this.floorId});
    }

    /**
     * Sets the beguiling preset that is currently displayed on the map.
     * @param preset int
     */
    setBeguilingPreset(preset) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Only when actually changed..
        if (preset !== this.beguilingPreset) {
            this.beguilingPreset = preset;

            // Let everyone know it's changed
            this.signal('beguilingpreset:changed', {beguilingPreset: this.beguilingPreset});
        }
    }

    /**
     * Sets the current map zoom level.
     * @param zoom
     */
    setMapZoomLevel(zoom) {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        // Only when actually changed..
        if (zoom !== this.mapZoomLevel) {
            this.mapZoomLevel = zoom;

            // Let everyone know it's changed
            this.signal('mapzoomlevel:changed', {mapZoomLevel: this.mapZoomLevel});
        }
    }

    /**
     * Get the default visual to display for all enemies.
     * @returns {string}
     */
    getEnemyDisplayType() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.enemyDisplayType;
    }

    /**
     * Get the beguiling preset that is currently displayed on the map.
     * @returns {string}
     */
    getBeguilingPreset() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.beguilingPreset;
    }

    /**
     * Get the current map's zoom level.
     * @returns {string}
     */
    getMapZoomLevel() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);
        return this.mapZoomLevel;
    }

    /**
     * Gets the data of the currently selected floor
     * @returns {boolean|Object}
     */
    getCurrentFloor() {
        console.assert(this instanceof StateManager, 'this is not a StateManager', this);

        let self = this;
        let result = false;
        // Iterate over the found floors
        $.each(this.map.dungeonData.floors, function (index, value) {
            // Find the floor we're looking for
            if (parseInt(value.id) === parseInt(self.floorId)) {
                result = value;
                return false;
            }
        });

        return result;
    }
}