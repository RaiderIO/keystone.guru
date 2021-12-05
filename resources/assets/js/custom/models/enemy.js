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

let ENEMY_SEASONAL_TYPE_AWAKENED = 'awakened';
let ENEMY_SEASONAL_TYPE_INSPIRING = 'inspiring';
let ENEMY_SEASONAL_TYPE_PRIDEFUL = 'prideful';
let ENEMY_SEASONAL_TYPE_TORMENTED = 'tormented';

/**
 * @property {Number} floor_id
 * @property {Number} enemy_pack_id
 * @property {Number} npc_id
 * @property {Number} mdt_id
 * @property {Number} mdt_npc_id
 * @property {String} seasonal_type
 * @property {Number} seasonal_index
 * @property {Number} enemy_forces_override
 * @property {Number} enemy_forces_override_teeming
 * @property {String} raid_marker_name
 * @property {Boolean} dangerous
 * @property {Boolean} required
 * @property {Boolean} skippable
 * @property {Number} lat
 * @property {Number} lng
 *
 * @property L.Layer layer
 */
class Enemy extends MapObject {
    constructor(map, layer, options = {name: 'enemy'}) {
        super(map, layer, options);

        this.label = 'Enemy';
        // Used for keeping track of what kill zone this enemy is attached to
        /** @type KillZone */
        this.kill_zone = null;
        /** @type Object May be set when loaded from server */
        this.npc = null;
        /** @type Enemy If we are an awakened NPC, we're linking it to another Awakened NPC that's next to the boss */
        this.linked_awakened_enemy = null;
        this.active_auras = [];

        // MDT
        this.mdt_id = -1;
        this.mdt_npc_id = null;
        this.is_mdt = false;

        // The visual display of this enemy
        this.visual = null;
        this.isPopupEnabled = false;
        this.overpulledKillZoneId = null;
        this.obsolete = false;

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

        let selectAuras = [];
        let auras = getState().getMapContext().getAuras();
        for (let index in auras) {
            if (auras.hasOwnProperty(index)) {
                let aura = auras[index];
                selectAuras.push({
                    id: aura.id,
                    name: `${aura.name} (${aura.id})`,
                    html: `<img src="${aura.icon_url}" width="32px"/> ${aura.name}</a>`
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
            // new Attribute({
            //     name: 'npc',
            //     type: 'object',
            //     default: null,
            //     setter: this.setNpc.bind(this),
            //     edit: false,
            //     save: false
            // }),
            new Attribute({
                name: 'npc_id',
                type: 'select',
                admin: true,
                values: selectNpcs,
                default: -1,
                live_search: true,
                setter: function (value) {

                    // Only called when not in admin state
                    let mapContext = getState().getMapContext();

                    let npc = mapContext.findNpcById(value);
                    if (npc !== null) {
                        self.setNpc(npc);
                    }

                    self.npc_id = value;
                }
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
                name: 'mdt_npc_id',
                type: 'select',
                admin: true,
                values: selectNpcs,
                default: null,
                live_search: true,
                setter: function (value) {
                    // Values from a select are always strings, cast this
                    let parsed = parseInt(value);
                    self.mdt_npc_id = value === null || parsed === -1 ? null : parsed;
                }
            }),
            new Attribute({
                name: 'seasonal_type',
                type: 'select',
                admin: true,
                default: null,
                values: [
                    {id: ENEMY_SEASONAL_TYPE_AWAKENED, name: 'Awakened'},
                    {id: ENEMY_SEASONAL_TYPE_INSPIRING, name: 'Inspiring'},
                    {id: ENEMY_SEASONAL_TYPE_PRIDEFUL, name: 'Prideful'},
                    {id: ENEMY_SEASONAL_TYPE_TORMENTED, name: 'Tormented'}
                ],
                setter: function (value) {
                    self.seasonal_type = value;
                }
            }),
            new Attribute({
                name: 'seasonal_index',
                type: 'select',
                admin: true,
                default: null,
                values: [
                    {id: 0, name: 'Week 1'},
                    {id: 1, name: 'Week 2'},
                    {id: 2, name: 'Week 3'},
                    {id: 3, name: 'Week 4'},
                    {id: 4, name: 'Week 5'}
                ],
                setter: function (value) {
                    // NaN check
                    if (value === -1 || value === '' || value !== value) {
                        value = null;
                    }
                    self.seasonal_index = value;
                }
            }),
            new Attribute({
                name: 'active_auras',
                type: 'select',
                admin: true,
                default: null,
                values: selectAuras,
                multiple: true,
                setter: function (value) {
                    self.active_auras = value;
                }
            }),
            new Attribute({
                name: 'required',
                type: 'bool',
                admin: true,
                default: false
            }),
            new Attribute({
                name: 'skippable',
                type: 'bool',
                admin: true,
                default: false
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
        return '(' + (Math.round((enemyForces / this.map.enemyForcesManager.getEnemyForcesRequired()) * 10000) / 100) + '%)';
    }

    _onObjectChanged(syncedEvent) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        // Only if we should display this enemy
        if (this.layer !== null) {
            // Recreate the tooltip
            this.bindTooltip();
        }
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

            // Link the mdt_npc_id on load so that the visual knows to display it differently
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
            let linkedEnemy = enemyMapObjectGroup.findMapObjectById(this.enemy_id);
            if (linkedEnemy instanceof Enemy) {
                this.mdt_npc_id = linkedEnemy.mdt_npc_id;
            }
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
            result = {info: [], custom: []};
            // @formatter:off
            result.info.push({
                key: lang.get('messages.sidebar_enemy_health_label'),
                value: this.npc.base_health.toLocaleString()
            });
            result.info.push({key: lang.get('messages.sidebar_enemy_bursting_label'), value: this.npc.bursting});
            result.info.push({key: lang.get('messages.sidebar_enemy_bolstering_label'), value: this.npc.bolstering});
            result.info.push({key: lang.get('messages.sidebar_enemy_sanguine_label'), value: this.npc.sanguine});
            // Required means that you MUST kill this enemy, otherwise you cannot complete the dungeon
            // result.info.push({
            //     key: lang.get('messages.sidebar_enemy_skippable_label'),
            //     value: this.required ? 0 : 1
            // });
            // Skippable means that you CAN walk past this enemy without shroud - in theory, and may be excluded by the overpull feature
            result.info.push({
                key: lang.get('messages.sidebar_enemy_skippable_label'),
                value: this.skippable ? 1 : 0,
                info: lang.get('messages.sidebar_enemy_skippable_info_label')
            });
            // @formatter:on

            if (typeof this.npc.npcbolsteringwhitelists !== 'undefined' && this.npc.npcbolsteringwhitelists.length > 0) {
                let npcBolsteringWhitelistHtml = '';
                let count = 0;
                for (let index in this.npc.npcbolsteringwhitelists) {
                    if (this.npc.npcbolsteringwhitelists.hasOwnProperty(index)) {
                        let whitelistedNpc = this.npc.npcbolsteringwhitelists[index];
                        npcBolsteringWhitelistHtml += whitelistedNpc.whitelistnpc.name;
                        // Stop before the end
                        if (count < this.npc.npcbolsteringwhitelists.length - 1) {
                            npcBolsteringWhitelistHtml += '<br>';
                        }
                    }
                    count++;
                }


                let customTemplate = Handlebars.templates['map_sidebar_enemy_info_custom_template'];

                result.custom.push({
                    html: customTemplate({html: `<span class="font-weight-bold">${lang.get('messages.sidebar_enemy_bolstering_whitelist_npcs_label')}:</span>`}) +
                        customTemplate({html: npcBolsteringWhitelistHtml})
                });
            }

            if (typeof this.npc.spells !== 'undefined' && this.npc.spells.length > 0) {
                let spellHtml = '';
                let count = 0;
                let spellTemplate = Handlebars.templates['spell_template'];

                for (let index in this.npc.spells) {
                    if (this.npc.spells.hasOwnProperty(index)) {
                        let spell = this.npc.spells[index];
                        spellHtml += spellTemplate(spell);
                        // Stop before the end
                        if (count < this.npc.spells.length - 1) {
                            spellHtml += '<br>';
                        }
                    }
                    count++;
                }

                let customTemplate = Handlebars.templates['map_sidebar_enemy_info_custom_template'];

                result.custom.push({
                    html: customTemplate({html: `<span class="font-weight-bold">${lang.get('messages.sidebar_enemy_spell_label')}:</span>`}) +
                        customTemplate({html: spellHtml})
                });
            }
        }

        return result;
    }

    /**
     * Checks if this enemy is the last boss or not.
     * @returns {boolean}
     */
    isLastBoss() {
        return this.npc !== null && this.npc.classification_id === 4;
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

            if (packBuddy.isLastBoss()) {
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
     * @param enabled {Boolean} True to enable, false to disable.
     */
    setPopupEnabled(enabled) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        //
        // if( this.id === 4406 ) {
        //     console.warn('setPopupEnabled', enabled);
        // }

        if (this.layer !== null) {
            if (enabled && !this.isPopupEnabled) {
                this._assignPopup();
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
     * @param npc {Object}
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
     * @param name {String}
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
     * @param killZone {Object}
     * @param ignorePackBuddies {Boolean}
     */
    setKillZone(killZone, ignorePackBuddies = false) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        let oldKillZone = this.kill_zone;
        this.kill_zone = killZone;

        if (this.kill_zone instanceof KillZone) {
            this.signal('killzone:attached', {previous: oldKillZone, ignorePackBuddies: ignorePackBuddies});
        }

        // We should notify it that we have detached from it
        if (oldKillZone !== null && (this.kill_zone === null || oldKillZone.id !== this.kill_zone.id)) {
            this.signal('killzone:detached', {previous: oldKillZone, ignorePackBuddies: ignorePackBuddies});
        }
    }

    /**
     * @inheritDoc
     */
    shouldBeVisible() {
        if (!getState().isMapAdmin()) {
            // If our linked awakened enemy has a killzone, we cannot display ourselves. But don't hide those on the map
            if (this.isAwakenedNpc() && this.isLinkedToLastBoss() && this.getKillZone() === null) {
                return false;
            }
        }

        let mapContext = getState().getMapContext();
        if (mapContext instanceof MapContextDungeonRoute) {
            // If we are tormented, but the route has no tormented enemies..
            if (this.hasOwnProperty('seasonal_type') && this.seasonal_type === ENEMY_SEASONAL_TYPE_TORMENTED &&
                !mapContext.hasAffix(AFFIX_TORMENTED)) {
                // console.warn(`Hiding enemy due to enemy being tormented but our route does not supported tormented units ${this.id}`);
                return false;
            }
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
        this.layer.on('click', function (clickEvent) {
            if (self.map.getMapState() instanceof EnemySelection && self.selectable && !clickEvent.originalEvent.shiftKey) {
                self.signal('enemy:selected', {clickEvent: clickEvent});
            } else {
                self.signal('enemy:clicked', {clickEvent: clickEvent});
            }
        });
    }

    isVisibleOnScreen() {
        return this.visual !== null && super.isVisibleOnScreen();
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
     * @param value {Boolean} True or false
     */
    setSelectable(value) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        this.selectable = value;
    }

    /**
     * @returns {KillZone|null}
     */
    getOverpulledKillZone() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        let result = null;

        if (this.overpulledKillZoneId !== null) {
            let killZoneMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);
            result = killZoneMapObjectGroup.findMapObjectById(this.overpulledKillZoneId);
        }

        return result;
    }

    /**
     * Checks if this enemy is marked as overpulled or not.
     * @returns {Number|null}
     */
    getOverpulledKillZoneId() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.overpulledKillZoneId;
    }

    /**
     * Set this enemy to be marked as overpulled
     * @param killZoneId {Number|null} The kill zone ID that this enemy was overpulled in or after
     */
    setOverpulledKillZoneId(killZoneId) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        if (this.overpulledKillZoneId !== killZoneId) {
            this.overpulledKillZoneId = killZoneId;

            this.signal('overpulled:changed');
        }
    }

    /**
     * Checks if this enemy is marked as obsolete or not.
     * @returns {*}
     */
    isObsolete() {
        return this.obsolete;
    }

    /**
     * Set this enemy to be marked as obsolete (was part of a route, but is no longer because we determined we should no
     * longer pull this enemy after an overpull elsewhere).
     * @param value {Boolean} True or false
     */
    setObsolete(value) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        if (this.obsolete !== value) {
            this.obsolete = value;

            this.signal('obsolete:changed');
        }
    }

    /**
     *
     * @returns {boolean}
     */
    isBossNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null && this.npc.classification_id >= 3;
    }

    /**
     *
     * @returns {boolean}
     */
    isAwakenedNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null && [161124, 161241, 161244, 161243].includes(this.npc.id);
    }

    /**
     *
     * @returns {boolean}
     */
    isPridefulNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null && this.npc.id === 173729;
    }

    /**
     *
     * @returns {boolean}
     */
    isInspiring() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_INSPIRING;
    }

    /**
     *
     * @returns {boolean}
     */
    isTormented() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_TORMENTED;
    }

    /**
     *
     * @returns {boolean}
     */
    isImportant() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.isBossNpc() || this.isInspiring() || this.isPridefulNpc() || this.isAwakenedNpc() || this.isTormented();
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

        // console.warn('Setting linked awakened enemy', this.id, awakenedEnemy.id);

        this.linked_awakened_enemy = awakenedEnemy;
    }

    /**
     *
     * @returns {Number}
     */
    getMdtNpcId() {
        return this.mdt_npc_id !== null ? this.mdt_npc_id : this.npc_id;
    }

    /**
     * Assigns a raid marker to this enemy.
     * @param raidMarkerName {String} The name of the marker, or empty to unset it
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
