// Default icon; placeholder while placing a new enemy. This can't really use the Visual system, it'd require
// too much rewrites. Better to just make a small placeholder like this and assign it to the below constructs.
let DefaultEnemyIcon = new L.divIcon({className: 'enemy_icon'});
let MDTEnemyIconSelected = new L.divIcon({className: 'enemy_icon mdt_enemy_icon leaflet-edit-marker-selected'});

$(function () {
    L.Draw.Enemy = L.Draw.Marker.extend({
        statics: {
            TYPE: 'enemy'
        },
        options: {
            icon: DefaultEnemyIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Enemy.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletEnemyMarker = L.Marker.extend({
    options: {
        icon: DefaultEnemyIcon
    }
});

class Enemy extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Enemy';
        // Used for keeping track of what kill zone this enemy is attached to
        /** @type KillZone */
        this.kill_zone = null;
        this.enemy_forces_override = -1;
        /** @type Object May be set when loaded from server */
        this.npc = null;
        this.raid_marker_name = '';
        this.dangerous = false;
        // The visual display of this enemy
        this.visual = null;

        // MDT
        this.mdt_id = -1;

        let self = this;
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Remove/enable the popup
            self.setPopupEnabled(mapStateChangedEvent.data.newMapState instanceof MapState);
        });

        // Make sure all tooltips are closed to prevent having tooltips remain open after having zoomed (bug)
        getState().register('mapzoomlevel:changed', this, function () {
            self.bindTooltip();
        });

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, this._synced.bind(this));
    }

    _getPercentageString(enemyForces) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        // Do some fuckery to round to two decimal places
        return '(' + (Math.round((enemyForces / this.map.getEnemyForcesRequired()) * 10000) / 100) + '%)';
    }

    _synced(event) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        // Only if we should display this enemy
        if (this.layer !== null) {
            // Synced, can now build the popup since we know our ID
            this._rebuildPopup(event);

            // Create the visual now that we know all data to construct it properly
            if (this.visual !== null) {
                this.visual.cleanup();
            }
            this.visual = new EnemyVisual(this.map, this, this.layer);

            // Recreate the tooltip
            this.bindTooltip();
        }
    }

    /**
     * Since the ID may not be known at spawn time, this needs to be callable from when it is known (when it's synced to server).
     *
     * @param event
     * @private
     */
    _rebuildPopup(event) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
    }

    /**
     * Get all enemies that share the same pack as this enemy
     */
    getPackBuddies() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        let self = this;

        // Add all the enemies in said pack to the toggle display
        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let result = [];
        $.each(enemyMapObjectGroup.objects, function (index, enemy) {
            if (enemy.enemy_pack_id === self.enemy_pack_id) {
                result.push(enemy);
            }
        });

        return result;
    }

    /**
     * Sets the click popup to be enabled or not.
     * @param enabled True to enable, false to disable.
     */
    setPopupEnabled(enabled) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        if (this.layer !== null) {
            if (enabled) {
                this._rebuildPopup();
            } else {
                this.layer.unbindPopup();
            }
        }
    }

    /**
     * Get the amount of enemy forces that this enemy gives when killed.
     * @returns {number}
     */
    getEnemyForces() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.enemy_forces_override >= 0 ? this.enemy_forces_override : (this.npc === null ? 0 : this.npc.enemy_forces);
    }

    bindTooltip() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        if (this.layer !== null) {
            let text = '';
            if (this.npc !== null) {
                text = this.npc.name;
            } else {
                text = lang.get('messages.no_npc_found_label');
            }

            // Remove any previous tooltip
            this.unbindTooltip();
            this.layer.bindTooltip(text, {
                direction: 'top'
            });
        }
    }

    /**
     * Sets the NPC for this enemy based on a remote NPC object.
     * @param npc
     */
    setNpc(npc) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        this.npc = npc;


        // May be null if not set at all (yet)
        if (npc !== null) {
            this.npc_id = npc.id;
            this.enemy_forces = npc.enemy_forces;
        } else {
            // Not set :(
            this.npc_id = -1;
        }

        this.bindTooltip();
        this.signal('enemy:set_npc', {npc: npc});
    }

    /**
     * Sets the name of the raid marker and changes the icon on the map to that of the raid marker (allowing).
     * @param name
     */
    setRaidMarkerName(name) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        this.raid_marker_name = name;
        // Trigger a raid marker change event
        this.signal('enemy:set_raid_marker', {name: name});
    }

    /**
     * Gets the killzone for this enemy.
     * @returns {KillZone|null}
     */
    getKillZone() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.kill_zone;
    }

    /**
     * Sets the killzone for this enemy.
     * @param killZone object
     */
    setKillZone(killZone) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        let oldKillZone = this.kill_zone;
        this.kill_zone = killZone;

        if (this.kill_zone instanceof KillZone) {
            this.signal('killzone:attached', {previous: oldKillZone});
        }

        // We should notify it that we have detached from it
        if (oldKillZone !== null) {
            this.signal('killzone:detached', {previous: oldKillZone});
        }
    }

    /**
     * Get the color of an enemy based on rated difficulty by users.
     * @param difficulty
     */
    getDifficultyColor(difficulty) {
        let palette = window.interpolate(c.map.enemy.colors);
        // let rand = Math.random();
        let color = palette(difficulty);
        this.setColors({
            saved: color,
            savedBorder: color,
            edited: color,
            editedBorder: color
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        super.onLayerInit();

        let self = this;

        // Show a permanent tooltip for the enemy's name
        this.layer.on('click', function () {
            if (self.map.getMapState() instanceof EnemySelection && self.selectable) {
                self.signal('enemy:selected');
            }
        });

        if (this.isEditable() && this.map.options.edit) {
            this.onPopupInit();
        }
    }

    onPopupInit() {
        console.assert(this instanceof Enemy, 'this was not an Enemy', this);
        let self = this;

        self.map.leafletMap.on('contextmenu', function () {
            if (self.currentPatrolPolyline !== null) {
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });
    }

    isDeletable() {
        return false;
    }

    isEditable() {
        return false;
    }

    /**
     * Checks if this enemy is possibly selectable when selecting enemies.
     * @returns {*}
     */
    isSelectable() {
        return this.selectable && this.visual !== null;
    }

    /**
     * Set this enemy to be selectable whenever the user wants to select enemies.
     * @param value boolean True or false
     */
    setSelectable(value) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        this.selectable = value;
        if (this.visual !== null) {
            // Refresh the icon
            this.visual.refresh();
        }
    }

    /**
     * Assigns a raid marker to this enemy.
     * @param raidMarkerName The name of the marker, or empty to unset it
     */
    assignRaidMarker(raidMarkerName) {
        console.assert(this instanceof Enemy, 'this was not an Enemy', this);
        let self = this;

        $.ajax({
            type: 'POST',
            url: '/ajax/' + getState().getDungeonRoute().publicKey + '/raidmarker/' + self.id,
            dataType: 'json',
            data: {
                raid_marker_name: raidMarkerName
            },
            success: function (json) {
                self.map.leafletMap.closePopup();
                self.setRaidMarkerName(raidMarkerName);
            },
        });
    }

    cleanup() {
        console.assert(this instanceof Enemy, 'this was not an Enemy', this);
        super.cleanup();

        this.unregister('synced', this, this._synced.bind(this));
        this.map.unregister('map:mapstatechanged', this);
    }
}