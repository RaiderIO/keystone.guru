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
 * @property {Number} floor_id
 * @property {Number} enemy_pack_id
 * @property {Number} npc_id
 * @property {Number} mdt_id
 * @property {Number} mdt_npc_id
 * @property {Number} exclusive_enemy_id
 * @property {String} seasonal_type
 * @property {Number} seasonal_index
 * @property {Number} enemy_forces_override
 * @property {Number} enemy_forces_override_teeming
 * @property {Number} dungeon_difficulty
 * @property {String} raid_marker_name
 * @property {Boolean} required
 * @property {Boolean} skippable
 * @property {Number} lat
 * @property {Number} lng
 *
 * @property L.Layer layer
 */
class Enemy extends VersionableMapObject {
    constructor(map, layer, options = {name: 'enemy', has_route_model_binding: true}) {
        super(map, layer, options);

        this.label = 'Enemy';
        // Used for keeping track of what kill zone this enemy is attached to
        /** @type KillZone */
        this.kill_zone = null;
        /** @type {Object|null} May be set when loaded from server */
        this.npc = null;
        /** @type {EnemyPatrol|null} May be set when loaded from server */
        this.enemyPatrol = null;
        /** @type {Enemy|Null} If we are an awakened NPC, we're linking it to another Awakened NPC that's next to the boss */
        this.linked_awakened_enemy = null;
        this.active_auras = [];
        /** @type {Enemy|null} If we have an enemy that we're sharing exclusivity with (Theater of Pain mini bosses) */
        this.exclusive_enemy = null;

        // MDT
        this.mdt_id = null;
        this.mdt_npc_id = null;
        this.is_mdt = false;

        // The visual display of this enemy
        /** @type {EnemyVisual} */
        this.visual = null;
        this.isPopupEnabled = false;
        this.overpulledKillZoneId = null;
        this.obsolete = false;
        this.selectNpcs = [];

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
        this.register('object:initialized', this, function () {
            self.bindTooltip();
        });

        // If we added or removed NPCs, we clear the cache
        getState().getMapContext().register(['npc:added', 'npc:removed'], this, function () {
            self.selectNpcs = [];
        });
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

        let selectAuras = [];
        let auras = getState().getMapContext().getAuras();
        for (let index in auras) {
            if (auras.hasOwnProperty(index)) {
                let aura = auras[index];
                selectAuras.push({
                    id: aura.id,
                    name: `${aura.name} (${aura.id})`,
                    html: `<img src="${aura.icon_url}" alt="${aura.name}" width="32px"/> ${aura.name}</a>`
                });
            }
        }


        return this._cachedAttributes = super._getAttributes(force).concat([
            new Attribute({
                name: 'enemy_pack_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null
            }),
            new Attribute({
                name: 'enemy_patrol_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null
            }),
            new Attribute({
                name: 'enemy_forces_override',
                type: 'int',
                admin: true,
                category: 'legacy',
            }),
            new Attribute({
                name: 'enemy_forces_override_teeming',
                type: 'int',
                admin: true,
                category: 'legacy',
            }),
            new Attribute({
                name: 'dungeon_difficulty',
                type: 'select',
                admin: true,
                values: [{
                    id: DUNGEON_DIFFICULTY_10_MAN,
                    name: lang.get(`dungeons.difficulty.${DUNGEON_DIFFICULTY_10_MAN}`)
                }, {
                    id: DUNGEON_DIFFICULTY_25_MAN,
                    name: lang.get(`dungeons.difficulty.${DUNGEON_DIFFICULTY_25_MAN}`)
                }],
                default: null,
                getter: function () {
                    return self.dungeon_difficulty === null || self.dungeon_difficulty <= 0 ? null : self.dungeon_difficulty;
                },
                category: 'advanced',
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
                values: this._getSelectNpcs.bind(this),
                default: null,
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
                type: 'select',
                admin: true,
                show_default: false,
                values: function () {
                    return getState().getMapContext().getFloorSelectValues();
                },
                default: getState().getCurrentFloor().id
            }),
            new Attribute({
                name: 'is_mdt',
                type: 'bool',
                edit: false, // Not directly changeable by user
                default: false,
                setter: function (value) {
                    // Exception for MDT enemies
                    self.is_mdt = value;
                }
            }),
            new Attribute({
                name: 'enemy_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: -1
            }),
            new Attribute({
                name: 'mdt_id',
                type: 'int',
                admin: true,
                // edit: false, // Not directly changeable by user
            }),
            new Attribute({
                name: 'mdt_npc_id',
                type: 'select',
                admin: true,
                values: this._getSelectNpcs.bind(this),
                default: null,
                live_search: true,
                setter: function (value) {
                    // Values from a select are always strings, cast this
                    let parsed = parseInt(value);
                    self.mdt_npc_id = value === null || parsed === -1 ? null : parsed;
                },
                category: 'legacy'
            }),
            new Attribute({
                name: 'exclusive_enemy_id',
                type: 'int',
                default: null,
                setter: function (value) {
                    let enemyMapObjectGroup = self.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

                    self.exclusive_enemy_id = value;
                    self.setExclusiveEnemy(enemyMapObjectGroup.findMapObjectById(value));
                },
            }),
            new Attribute({
                name: 'seasonal_type',
                type: 'select',
                admin: true,
                default: null,
                values: [
                    {id: ENEMY_SEASONAL_TYPE_BEGUILING, name: lang.get('enemies.seasonal_type.beguiling')},
                    {id: ENEMY_SEASONAL_TYPE_AWAKENED, name: lang.get('enemies.seasonal_type.awakened')},
                    {id: ENEMY_SEASONAL_TYPE_INSPIRING, name: lang.get('enemies.seasonal_type.inspiring')},
                    {id: ENEMY_SEASONAL_TYPE_PRIDEFUL, name: lang.get('enemies.seasonal_type.prideful')},
                    {id: ENEMY_SEASONAL_TYPE_TORMENTED, name: lang.get('enemies.seasonal_type.tormented')},
                    {id: ENEMY_SEASONAL_TYPE_ENCRYPTED, name: lang.get('enemies.seasonal_type.encrypted')},
                    {id: ENEMY_SEASONAL_TYPE_MDT_PLACEHOLDER, name: lang.get('enemies.seasonal_type.mdt_placeholder')},
                    {id: ENEMY_SEASONAL_TYPE_REQUIRES_ACTIVATION, name: lang.get('enemies.seasonal_type.requires_activation')},
                    {id: ENEMY_SEASONAL_TYPE_SHROUDED, name: lang.get('enemies.seasonal_type.shrouded')},
                    {
                        id: ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
                        name: lang.get('enemies.seasonal_type.shrouded_zul_gamux')
                    },
                    {id: ENEMY_SEASONAL_TYPE_NO_SHROUDED, name: lang.get('enemies.seasonal_type.no_shrouded')}
                ],
                setter: function (value) {
                    self.seasonal_type = value <= 0 ? null : value;
                },
                category: 'advanced',
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
                },
                category: 'advanced',
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
                },
                category: 'legacy'
            }),
            new Attribute({
                name: 'required',
                type: 'bool',
                admin: true,
                default: false,
                category: 'advanced',
            }),
            new Attribute({
                name: 'skippable',
                type: 'bool',
                admin: true,
                default: false,
                category: 'advanced',
            }),
            new Attribute({
                name: 'hyper_respawn',
                type: 'bool',
                admin: true,
                default: false,
                category: 'advanced',
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
            }),
            new Attribute({
                name: 'kill_priority',
                type: 'select',
                admin: true,
                default: null,
                values: [
                    {id: -19, name: 'Very low'},
                    {id: -10, name: 'Low'},
                    {id: 0, name: 'Normal'},
                    {id: 10, name: 'High'},
                    {id: 19, name: 'Very high'},
                ],
                category: 'advanced',
            })
        ]);
    }

    /**
     *
     * @returns {[]}
     * @private
     */
    _getSelectNpcs() {
        // Return cache if we have it
        if (this.selectNpcs.length > 0) {
            return this.selectNpcs;
        }

        let npcs = getState().getMapContext().getNpcs();
        for (let index in npcs) {
            if (npcs.hasOwnProperty(index)) {
                let npc = npcs[index];
                this.selectNpcs.push({
                    id: npc.id,
                    name: `${npc.name} (${npc.id})`
                });
            }
        }

        return this.selectNpcs;
    }

    _getPercentageString(enemyForces) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        // Do some fuckery to round to two decimal places
        return '(' + (Math.round((enemyForces / this.map.enemyForcesManager.getEnemyForcesRequired()) * 10000) / 100) + '%)';
    }

    _onObjectChanged() {
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

        if (this.shouldBeIgnored()) {
            this.setVisible(false);
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
            let scaledHealth = this.npc.base_health * ((this.npc.health_percentage ?? 100) / 100);

            let mapContext = getState().getMapContext();
            let keyLevelLabel = '';
            let affixes = [];

            if (mapContext instanceof MapContextDungeonRoute && mapContext.getGameVersion().key === GAME_VERSION_RETAIL) {
                // noinspection JSAssignmentUsedAsCondition
                if ((mapContext.hasAffix(AFFIX_FORTIFIED) && [NPC_CLASSIFICATION_ID_NORMAL, NPC_CLASSIFICATION_ID_ELITE].includes(this.npc.classification_id))) {
                    affixes.push(AFFIX_FORTIFIED);
                }
                // noinspection JSAssignmentUsedAsCondition
                if ((mapContext.hasAffix(AFFIX_TYRANNICAL) && [NPC_CLASSIFICATION_ID_BOSS, NPC_CLASSIFICATION_ID_FINAL_BOSS].includes(this.npc.classification_id))) {
                    affixes.push(AFFIX_BOLSTERING);
                }
                if (mapContext.hasAffix(AFFIX_XALATATHS_GUILE)) {
                    affixes.push(AFFIX_XALATATHS_GUILE);
                }

                scaledHealth = c.map.enemy.calculateHealthForKey(scaledHealth, mapContext.getLevelMin(), affixes);
                keyLevelLabel = ` (+${mapContext.getLevelMin()})`;
            } else {
                scaledHealth = Math.round(scaledHealth);
            }

            let percentageString = this.npc.health_percentage !== null && this.npc.health_percentage !== 100 ? ` (${this.npc.health_percentage}%)` : ``;

            result = {info: [], custom: []};
            // @formatter:off
            result.info.push({
                key: lang.get('messages.sidebar_enemy_health_label') + keyLevelLabel,
                value: scaledHealth.toLocaleString() + percentageString,
                info: affixes.length === 0 ? false : lang.get('messages.sidebar_enemy_health_affixes_label', {
                    affixes: affixes.join(', '),
                    baseHealth: this.npc.base_health.toLocaleString(),
                    factor: Math.round(c.map.enemy.getKeyScalingFactor(mapContext.getLevelMin(), affixes) * 100)
                })
            });

            // Defined in sitescripts
            // noinspection JSUnresolvedReference
            if (isUserAdmin) {
                result.info.push({
                    key: lang.get('messages.sidebar_enemy_id_label'),
                    value: this.id
                });
                result.info.push({
                    key: lang.get('messages.sidebar_enemy_npc_id_label'),
                    value: `<a href="/admin/npc/${this.npc.id}">${this.npc.id}</a>`
                });
            }

            if (mapContext.getGameVersion().key === GAME_VERSION_RETAIL) {
                // These affixes have been removed
                // result.info.push({key: lang.get('messages.sidebar_enemy_bursting_label'), value: this.npc.bursting});
                // result.info.push({key: lang.get('messages.sidebar_enemy_bolstering_label'), value: this.npc.bolstering});
                // result.info.push({key: lang.get('messages.sidebar_enemy_sanguine_label'), value: this.npc.sanguine});


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
            } else {
                result.info.push({key: lang.get('messages.sidebar_enemy_runs_away_in_fear_label'), value: this.npc.runs_away_in_fear});
                result.info.push({key: lang.get('messages.sidebar_hyper_respawns_label'), value: this.hyper_respawn ? 1 : 0});
            }
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
                let gameVersion = mapContext.getDungeon().game_version;

                for (let index in this.npc.spells) {
                    if (this.npc.spells.hasOwnProperty(index)) {
                        let spell = this.npc.spells[index];

                        spell.wowhead_url = this.getWowheadLinkForGameVersion(gameVersion, spell);

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

    getWowheadLinkForGameVersion(gameVersion, spell) {
        let wowheadBaseUrl = 'https://www.wowhead.com';

        switch (gameVersion.key) {
            case GAME_VERSION_WOTLK:
                wowheadBaseUrl += '/wrath';
                break;
            case GAME_VERSION_CLASSIC:
                wowheadBaseUrl += '/classic';
                break;
        }

        const slug = spell.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')   // Replace non-alphanumeric with dashes
            .replace(/^-+|-+$/g, '');      // Trim leading/trailing dashes

        return `${wowheadBaseUrl}/spell=${spell.id}/${slug}`;
    }

    /**
     * Checks if this enemy is the last boss or not.
     * @returns {boolean}
     */
    isLastBoss() {
        return this.npc !== null && this.npc.classification_id === NPC_CLASSIFICATION_ID_FINAL_BOSS;
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
        if (this.enemy_pack_id !== null) {
            // Add all the enemies in said pack to the toggle display
            let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

            for (let key in enemyMapObjectGroup.objects) {
                let enemy = enemyMapObjectGroup.objects[key];
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

        if (this.npc !== null && this.npc.enemy_forces !== null) {
            result = this.npc.enemy_forces.enemy_forces;

            let mapContext = getState().getMapContext();

            // Override first
            if (this.isShrouded()) {
                result = mapContext.getEnemyForcesShrouded();
            } else if (this.isShroudedZulGamux()) {
                result = mapContext.getEnemyForcesShroudedZulGamux();
            } else if (mapContext.getTeeming()) {
                if (this.enemy_forces_override_teeming !== null) {
                    result = this.enemy_forces_override_teeming;
                } else if (this.npc.enemy_forces.enemy_forces_teeming !== null) {
                    result = this.npc.enemy_forces.enemy_forces_teeming;
                }
            } else if (this.enemy_forces_override !== null) {
                result = this.enemy_forces_override;
            }
        }

        return result;
    }

    bindTooltip() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);

        if (this.layer !== null) {
            let text;
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
            if (npc.enemy_forces === null || typeof npc.enemy_forces === 'undefined') {
                this.enemy_forces = 0;
                this.enemy_forces_teeming = null;
            } else {
                this.enemy_forces = npc.enemy_forces.enemy_forces;
                this.enemy_forces_teeming = npc.enemy_forces.enemy_forces_teeming;
            }

        } else {
            // Not set :(
            this.npc_id = null;
        }

        this.signal('enemy:set_npc', {npc: npc});
    }

    /**
     *
     * @param enemyPatrol {EnemyPatrol}
     */
    setEnemyPatrol(enemyPatrol) {
        if (this.enemyPatrol !== null) {
            this.enemyPatrol.removeEnemy(this);
        }

        this.enemyPatrol = enemyPatrol;
        this.enemy_patrol_id = null;

        if (this.enemyPatrol !== null) {
            this.enemyPatrol.addEnemy(this);
            this.enemy_patrol_id = enemyPatrol.id;
        }
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
     *
     * @returns {boolean}
     */
    shouldBeIgnored() {
        if (!getState().isMapAdmin()) {
            // If our linked awakened enemy has a killzone, we cannot display ourselves. But don't hide those on the map
            if (this.isAwakenedNpc() && this.isLinkedToLastBoss() && this.getKillZone() === null) {
                // console.warn(`Hiding awakened enemy linked to last boss since it's already killed in the map ${this.id}`);
                return true;
            }
        }

        let mapContext = getState().getMapContext();
        if (!(mapContext instanceof MapContextMappingVersionEdit)) {
            // If we are tormented, but the route has no tormented enemies..
            if (this.hasOwnProperty('seasonal_type')) {
                let hasShroudedAffix = mapContext.hasAffix(AFFIX_SHROUDED);
                if ((this.seasonal_type === ENEMY_SEASONAL_TYPE_BEGUILING && !mapContext.hasAffix(AFFIX_BEGUILING)) ||
                    (this.seasonal_type === ENEMY_SEASONAL_TYPE_AWAKENED && !mapContext.hasAffix(AFFIX_AWAKENED)) ||
                    (this.seasonal_type === ENEMY_SEASONAL_TYPE_TORMENTED && !mapContext.hasAffix(AFFIX_TORMENTED)) ||
                    (this.seasonal_type === ENEMY_SEASONAL_TYPE_ENCRYPTED && !mapContext.hasAffix(AFFIX_ENCRYPTED)) ||
                    (this.seasonal_type === ENEMY_SEASONAL_TYPE_SHROUDED && !hasShroudedAffix) ||
                    // Special case for enemies marked as non-shrouded which replace the enemies that are marked as shrouded
                    (this.seasonal_type === ENEMY_SEASONAL_TYPE_NO_SHROUDED && hasShroudedAffix) ||
                    // MDT placeholders are only to suppress warnings when importing - don't show these on the map
                    this.seasonal_type === ENEMY_SEASONAL_TYPE_MDT_PLACEHOLDER) {
                    // console.warn(`Hiding enemy due to enemy being tormented but our route does not supported tormented units ${this.id}`);
                    return true;
                }
            }

            if (this.hasOwnProperty('dungeon_difficulty') && this.dungeon_difficulty !== null) {
                // If our dungeon difficulty is null, always show it. Otherwise, only show it when our difficulty matches
                // console.warn(`Hiding enemy since the dungeon difficulty does not match ${this.id}`);
                return mapContext.getDungeonDifficulty() !== this.dungeon_difficulty;
            }

            // Hide critters (Freehold)
            if (this.npc !== null && this.npc.npc_type_id === NPC_TYPE_CRITTER) {
                // console.warn(`Hiding enemy since it is a critter ${this.id}`);
                return true;
            }
        }

        // Hide MDT enemies
        // noinspection RedundantIfStatementJS
        if (this.hasOwnProperty('is_mdt') && this.is_mdt && !getState().getMdtMappingModeEnabled()) {
            // console.warn(`Hiding MDT enemy since MDT mapping mode is disabled ${this.id}`);
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    shouldBeVisible() {
        if (this.shouldBeIgnored()) {
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
    isRareNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null && this.npc.classification_id === NPC_CLASSIFICATION_ID_RARE;
    }

    /**
     *
     * @returns {boolean}
     */
    isBossNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null && this.npc.classification_id >= NPC_CLASSIFICATION_ID_BOSS;
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
    isEncryptedNpc() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.npc !== null && [185680, 185683, 185685].includes(this.npc.id);
    }

    /**
     *
     * @returns {boolean}
     */
    isInspiring() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_INSPIRING && getState().getMapContext().hasAffix(AFFIX_INSPIRING);
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
    isEncrypted() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_ENCRYPTED;
    }

    /**
     *
     * @returns {boolean}
     */
    isShrouded() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_SHROUDED && getState().getMapContext().hasAffix(AFFIX_SHROUDED);
    }

    /**
     *
     * @returns {boolean}
     */
    isShroudedZulGamux() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX && getState().getMapContext().hasAffix(AFFIX_SHROUDED);
    }

    /**
     *
     * @returns {boolean}
     */
    isNotShrouded() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_NO_SHROUDED;
    }

    /**
     *
     * @returns {boolean}
     */
    isRequiresActivation() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.seasonal_type === ENEMY_SEASONAL_TYPE_REQUIRES_ACTIVATION;
    }

    /**
     *
     * @returns {boolean}
     */
    isImportant() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.isBossNpc() ||
            this.isInspiring() ||
            this.isShrouded() ||
            this.isShroudedZulGamux() ||
            this.isTormented() ||
            this.isPridefulNpc() ||
            this.isAwakenedNpc();
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
     * Get the exclusive enemy that is linked to this enemy (if any).
     *
     * @returns {Enemy|null}
     */
    getExclusiveEnemy() {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        return this.exclusive_enemy;
    }

    /**
     * Sets this Enemy to be exclusive with another Enemy.
     *
     * @param exclusiveEnemy {Enemy}
     */
    setExclusiveEnemy(exclusiveEnemy) {
        console.assert(this instanceof Enemy, 'this is not an Enemy', this);
        console.assert(exclusiveEnemy?.id !== this.id, 'exclusiveEnemy must have a different id as ourselves!', exclusiveEnemy, this);

        // console.warn('Setting linked awakened enemy', this.id, awakenedEnemy.id);

        this.exclusive_enemy = exclusiveEnemy;
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
            success: function () {
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

        // If we added or removed NPCs, we clear the cache
        getState().getMapContext().unregister(['npc:added', 'npc:removed'], this);

        this.unregister('object:initialized', this);
        this.unregister('object:changed', this, this._onObjectChanged.bind(this));
        this.map.unregister('map:mapstatechanged', this);

        if (this.visual !== null) {
            this.visual.cleanup();
            this.visual = null;
        }
    }
}
