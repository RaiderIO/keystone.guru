// Default icon; placeholder while placing a new enemy. This can't really use the Visual system, it'd require
// too much rewrites. Better to just make a small placeholder like this and assign it to the below constructs.
let DefaultEnemyIcon = new L.divIcon({className: 'enemy_icon'});
let MDTEnemyIconSelected = new L.divIcon({className: 'enemy_icon mdt_enemy_icon leaflet-edit-marker-selected'});

let LeafletEnemyMarker = L.Marker.extend({
    options: {
        icon: DefaultEnemyIcon
    }
});

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

/**
 * @property floor_id int
 * @property enemy_pack_id int
 * @property npc_id int
 * @property mdt_id int
 * @property seasonal_index int
 * @property enemy_forces_override int
 * @property enemy_forces_override_teeming int
 * @property raid_marker_name string
 * @property dangerous bool
 * @property lat float
 * @property lng float
 *
 * @property L.Layer layer
 */
class Enemy extends MapObject {
    constructor(map, layer) {
        super(map, layer, {name: 'enemy'});

        this.label = 'Enemy';
        // Used for keeping track of what kill zone this enemy is attached to
        /** @type KillZone */
        this.kill_zone = null;
        /** @type Object May be set when loaded from server */
        this.npc = null;
        /** @type Enemy If we are an awakened NPC, we're linking it to another Awakened NPC that's next to the boss */
        this.linked_awakened_enemy = null;
        // The visual display of this enemy
        this.visual = null;
        this.isPopupEnabled = false;

        // MDT
        this.mdt_id = -1;

        let self = this;
        this.map.register('map:mapstatechanged', this, function (mapStateChangedEvent) {
            // Remove/enable the popup
            self.setPopupEnabled(!(mapStateChangedEvent.data.newMapState instanceof MapState));
        });

        // Make sure all tooltips are closed to prevent having tooltips remain open after having zoomed (bug)
        // getState().register('mapzoomlevel:changed', this, function () {
        //     self.bindTooltip();
        // });

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('object:changed', this, this._onObjectChanged.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getAttributes(force) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        if (this._cachedAttributes !== null && !force) {
            return this._cachedAttributes;
        }

        let self = this;
        let selectNpcs = [];
        let npcs = getState().getMapContext().getNpcs();
        for (let index in npcs) {
            if (npcs.hasOwnProperty(index)) {
                let npc = npcs[index];
                selectNpcs.push({
                    id: npc.id,
                    name: `${npc.name} (${npc.id})`
                });
            }
        }

        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'enemy_pack_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: -1
            }),
            new Attribute({
                name: 'npc',
                type: 'object',
                default: null,
                setter: this.setNpc.bind(this),
                edit: false,
                save: false
            }),
            new Attribute({
                name: 'npc_id',
                type: 'select',
                admin: true,
                values: selectNpcs,
                default: -1,
                live_search: true
            }),
            new Attribute({
                name: 'floor_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'mdt_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: -1
            }),
            new Attribute({
                name: 'seasonal_index',
                type: 'int',
                admin: true,
                default: null,
                setter: function (value) {
                    // NaN check
                    if (value === '' || value !== value) {
                        value = null;
                    }
                    self.seasonal_index = value;
                }
            }),
            new Attribute({
                name: 'enemy_forces_override',
                type: 'int',
                admin: true,
                default: -1
            }),
            new Attribute({
                name: 'enemy_forces_override_teeming',
                type: 'int',
                admin: true,
                default: -1
            }),
            new Attribute({
                name: 'lat',
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer.getLatLng().lat;
                },
                default: 0
            }),
            new Attribute({
                name: 'lng',
                type: 'float',
                edit: false,
                getter: function () {
                    return self.layer.getLatLng().lng;
                },
                default: 0
            }),
            new Attribute({
                name: 'raid_marker_name',
                type: 'string',
                edit: false,
                save: false,
                setter: this.setRaidMarkerName.bind(this),
                default: ''
            }),
            new Attribute({
                name: 'dangerous',
                type: 'bool',
                edit: false,
                save: false,
                default: false
            })
        ]);
    }

    _getPercentageString(enemyForces) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        // Do some fuckery to round to two decimal places
        return '(' + (Math.round((enemyForces / this.map.getEnemyForcesRequired()) * 10000) / 100) + '%)';
    }

    _onObjectChanged(syncedEvent) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        // Only if we should display this enemy
        if (this.layer !== null) {
            // Synced, can now build the popup since we know our ID
            this._rebuildPopup(syncedEvent);

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
     * @inheritDoc
     **/
    loadRemoteMapObject(remoteMapObject, parentAttribute = null) {
        super.loadRemoteMapObject(remoteMapObject, parentAttribute);

        if (remoteMapObject.hasOwnProperty('is_mdt')) {
            // Exception for MDT enemies
            this.is_mdt = remoteMapObject.is_mdt;
            // Whatever enemy this MDT enemy is linked to
            this.enemy_id = remoteMapObject.enemy_id;
            // Hide this enemy by default
            this.setDefaultVisible(false);
            this.setIsLocal(remoteMapObject.local);
        }

        // When in admin mode, show all enemies
        if (!getState().isMapAdmin()) {
            // Hide this enemy by default
            this.setDefaultVisible(this.shouldBeVisible());
        }

        this.visual = new EnemyVisual(this.map, this, this.layer);
    }

    /**
     * Get data that may be displayed to the user in the front-end.
     * @returns {[]|null}
     */
    getVisualData() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        let result = null;

        if (this.npc !== null) {
            result = {info: []};
            // @formatter:off
            result.info.push({key: lang.get('messages.sidebar_enemy_name_label'), value: this.npc.name})
            result.info.push({key: lang.get('messages.sidebar_enemy_health_label'), value: this.npc.base_health.toLocaleString()})
            result.info.push({key: lang.get('messages.sidebar_enemy_bursting_label'), value: this.npc.bursting})
            result.info.push({key: lang.get('messages.sidebar_enemy_bolstering_label'), value: this.npc.bolstering})
            result.info.push({key: lang.get('messages.sidebar_enemy_sanguine_label'), value: this.npc.sanguine})
            // @formatter:on

            if (typeof this.npc.npcbolsteringwhitelists !== 'undefined' && this.npc.npcbolsteringwhitelists.length > 0) {
                let npcBolsteringWhitelistValues = '';
                let count = 0;
                for (let index in this.npc.npcbolsteringwhitelists) {
                    if (this.npc.npcbolsteringwhitelists.hasOwnProperty(index)) {
                        let whitelistedNpc = this.npc.npcbolsteringwhitelists[index];
                        npcBolsteringWhitelistValues += whitelistedNpc.whitelistnpc.name;
                        // Stop before the end
                        if (count < this.npc.npcbolsteringwhitelists.length - 1) {
                            npcBolsteringWhitelistValues += '<br>';
                        }
                    }
                    count++;
                }
                result.info.push({
                    key: lang.get('messages.sidebar_enemy_bolstering_whitelist_npcs_label'),
                    value: npcBolsteringWhitelistValues
                })
            }
        }

        return result;
    }

    /**
     * Checks if this enemy is linked to the last boss or not.
     * @returns {boolean}
     */
    isLinkedToLastBoss() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        let result = false;

        let packBuddies = this.getPackBuddies();
        for (let i = 0; i < packBuddies.length; i++) {
            let packBuddy = packBuddies[i];

            if (packBuddy.npc !== null && packBuddy.npc.classification_id === 4) {
                result = true;
                break;
            }
        }

        return result;
    }

    /**
     * Get all enemies that share the same pack as this enemy
     * @return {Enemy[]}
     */
    getPackBuddies() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        let result = [];

        // Only if we're part of a pack
        if (this.enemy_pack_id >= 0) {
            // Add all the enemies in said pack to the toggle display
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

            for (let i = 0; i < enemyMapObjectGroup.objects.length; i++) {
                let enemy = enemyMapObjectGroup.objects[i];
                // Visible check for possible hidden Awakened Enemies on the last boss
                if (enemy.enemy_pack_id === this.enemy_pack_id && enemy.id !== this.id && enemy.isVisible()) {
                    result.push(enemy);
                }
            }
        }

        return result;
    }

    /**
     * Sets the click popup to be enabled or not.
     * @param enabled True to enable, false to disable.
     */
    setPopupEnabled(enabled) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        if (this.layer !== null) {
            if (enabled && !this.isPopupEnabled) {
                this._rebuildPopup();
            } else if (!enabled && this.isPopupEnabled) {
                this.layer.unbindPopup();
            }
        }

        this.isPopupEnabled = enabled;
    }

    /**
     * Get the amount of enemy forces that this enemy gives when killed.
     * @returns {number}
     */
    getEnemyForces() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        let result = 0;
        if (this.npc !== null) {
            result = this.npc.enemy_forces;

            // Override first
            if (getState().getMapContext().getTeeming()) {
                if (this.enemy_forces_override_teeming >= 0) {
                    result = this.enemy_forces_override_teeming;
                } else if (this.npc.enemy_forces_teeming >= 0) {
                    result = this.npc.enemy_forces_teeming;
                }
            } else if (this.enemy_forces_override >= 0) {
                result = this.enemy_forces_override;
            }
        }

        return result;
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
            this.enemy_forces_teeming = npc.enemy_forces_teeming;
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
     * Gets the kill zone for this enemy.
     * @returns {KillZone|null}
     */
    getKillZone() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.kill_zone;
    }

    /**
     * Sets the kill zone for this enemy.
     * @param killZone object
     */
    setKillZone(killZone) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        let oldKillZone = this.kill_zone;
        this.kill_zone = killZone;

        if (this.id === 4284) {
            console.warn('setKillZone', this, oldKillZone, this.kill_zone);
        }
        if (this.kill_zone instanceof KillZone) {
            this.signal('killzone:attached', {previous: oldKillZone});
        }

        // We should notify it that we have detached from it
        if (oldKillZone !== null && (this.kill_zone === null || oldKillZone.id !== this.kill_zone.id)) {
            this.signal('killzone:detached', {previous: oldKillZone});
        }
    }

    shouldBeVisible() {
        // If our linked awakened enemy has a killzone, we cannot display ourselves. But don't hide those on the map
        if (this.linked_awakened_enemy !== null && this.linked_awakened_enemy.getKillZone() !== null && this.isLinkedToLastBoss()) {
            console.log(`Hiding enemy ${this.id}`);
            return false;
        }

        // Hide MDT enemies
        if (this.hasOwnProperty('is_mdt') && this.is_mdt && !getState().getMdtMappingModeEnabled()) {
            return false;
        }

        return super.shouldBeVisible();
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
            } else {
                self.signal('enemy:clicked');
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

    isVisibleOnScreen() {
        return super.isVisibleOnScreen() && this.visual !== null;
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
    }

    /**
     *
     * @returns {boolean}
     */
    isAwakenedNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null &&
            (this.npc.id === 161124 || this.npc.id === 161241 || this.npc.id === 161244 || this.npc.id === 161243);
    }

    /**
     * Get the Awakened NPC that is linked to this enemy (if any).
     * @returns {Enemy|null}
     */
    getLinkedAwakenedEnemy() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.linked_awakened_enemy;
    }

    /**
     * Sets this Awakened Enemy to be linked to another Awakened Enemy.
     * @param awakenedEnemy {Enemy}
     */
    setLinkedAwakenedEnemy(awakenedEnemy) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        console.assert(this.isAwakenedNpc(), 'this must be an Awakened NPC!', this);
        console.assert(awakenedEnemy.isAwakenedNpc(), 'awakenedEnemy must be an Awakened NPC!', awakenedEnemy);
        console.assert(awakenedEnemy.id !== this.id, 'awakenedEnemy must have a different id as ourselves!', awakenedEnemy, this);
        console.assert(awakenedEnemy.npc.id === this.npc.id, 'awakenedEnemy must have the same NPC id as ourselves!', awakenedEnemy.npc, this.npc);

        this.linked_awakened_enemy = awakenedEnemy;
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
            url: `/ajax/${getState().getMapContext().getPublicKey()}/raidmarker/${self.id}`,
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

    toString() {
        return 'Enemy-' + this.id;
    }

    cleanup() {
        console.assert(this instanceof Enemy, 'this was not an Enemy', this);
        super.cleanup();

        this.unregister('object:changed', this, this._onObjectChanged.bind(this));
        this.map.unregister('map:mapstatechanged', this);

        if (this.visual !== null) {
            this.visual.cleanup();
            this.visual = null;
        }
    }
}