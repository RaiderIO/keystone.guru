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
        // Not actually saved to the enemy, but is used for keeping track of what killzone this enemy is attached to
        this.kill_zone_id = 0;
        this.enemy_forces_override = -1;
        // May be set when loaded from server
        this.npc = null;
        this.raid_marker_name = '';
        this.dangerous = false;
        // May be null if we're not a Beguiling enemy
        this.beguiling_preset = null;

        // MDT
        this.mdt_id = -1;

        let self = this;
        this.map.register('map:enemyselectionmodechanged', this, function (selectionModeChangedEvent) {
            // Remove/enable the popup
            self.setPopupEnabled(selectionModeChangedEvent.data.finished);
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

        // Synced, can now build the popup since we know our ID
        this._rebuildPopup(event);

        // Create the visual now that we know all data to construct it properly
        this.visual = new EnemyVisual(this.map, this, this.layer);

        // Recreate the tooltip
        this.bindTooltip();
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
     * Checks if this enemy is a beguiling enemy.
     * @returns {boolean} True if it is, false if it is not.
     */
    isBeguiling() {
        // Beguiling NPCs have their dungeon ID set to -1 since they're the only ones whose
        return typeof this.beguiling_preset === 'number';
    }

    /**
     * Sets the click popup to be enabled or not.
     * @param enabled True to enable, false to disable.
     */
    setPopupEnabled(enabled) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        if (enabled) {
            this._rebuildPopup();
        } else {
            this.layer.unbindPopup();
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
     * Sets the killzone for this enemy.
     * @param killZoneId id
     */
    setKillZone(killZoneId) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        this.kill_zone_id = killZoneId;

        // We only want to trigger these events when the killzone is actively being edited, not when loading in
        // the connections from the server initially
        if (this.map.isEnemySelectionEnabled()) {
            if (this.kill_zone_id >= 0) {
                this.signal('killzone:attached');
            } else {
                this.signal('killzone:detached');
            }
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
            if (self.map.isEnemySelectionEnabled() && self.selectable) {
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

    /**
     * Checks if this enemy is possibly selectable when selecting enemies.
     * @returns {*}
     */
    isSelectable() {
        return this.selectable;
    }

    /**
     * Set this enemy to be selectable whenever the user wants to select enemies.
     * @param value boolean True or false
     */
    setSelectable(value) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        this.selectable = value;
        // Refresh the icon
        this.visual.refresh();
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
            url: '/ajax/' + this.map.getDungeonRoute().publicKey + '/raidmarker/' + self.id,
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
        this.map.unregister('map:enemyselectionmodechanged', this);
    }
}