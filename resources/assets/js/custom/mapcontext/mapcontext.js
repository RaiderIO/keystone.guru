class MapContext extends Signalable {
    constructor(options) {
        super();

        this._options = options;

        // Init class colors
        c.map.colorPickerDefaultOptions.swatches = this.getStaticClassColors();

        // Init map icon types
        let mapIconTypes = this._options.static.mapIconTypes;
        this.mapIconTypes = [];
        for (let i = 0; i < mapIconTypes.length; i++) {
            this.mapIconTypes.push(
                new MapIconType(mapIconTypes[i])
            )
        }

        // Init spells
        let spells = this._options.static.selectableSpells;
        this.spells = [];
        for (let i = 0; i < spells.length; i++) {
            this.spells.push(
                new Spell(spells[i])
            )
        }

        this.unknownMapIconType = this.getMapIconType(this._options.static.unknownMapIconType.id);
        this.awakenedObeliskGatewayMapIconType = this.getMapIconType(this._options.static.awakenedObeliskGatewayMapIconType.id);

        this.floorSelectValues = this.getFloorSelectValues();
    }

    /**
     * @returns {String}
     */
    getEnvironment() {
        return this._options.environment;
    }

    /**
     * Always return false in a context where affixes are not known
     * @param affix {String}
     * @returns {boolean}
     */
    hasAffix(affix) {
        return false;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticMapIconTypes() {
        return this.mapIconTypes;
    }

    /**
     * Get the Map Icon Type for an ID in the MAP_ICON_TYPES array.
     * @param mapIconTypeId {Number}
     * @returns {MapIconType}
     */
    getMapIconType(mapIconTypeId) {
        let mapIconType = this.getUnknownMapIconType();
        for (let i = 0; i < this.mapIconTypes.length; i++) {
            if (this.mapIconTypes[i].id === mapIconTypeId) {
                mapIconType = this.mapIconTypes[i];
                break;
            }
        }
        return mapIconType;
    }

    /**
     * Get the Map Icon Type for an ID in the MAP_ICON_TYPES array.
     * @param mapIconTypeKey {String}
     * @returns {MapIconType}
     */
    getMapIconTypeByKey(mapIconTypeKey) {
        let mapIconType = this.getUnknownMapIconType();
        for (let i = 0; i < this.mapIconTypes.length; i++) {
            if (this.mapIconTypes[i].key === mapIconTypeKey) {
                mapIconType = this.mapIconTypes[i];
                break;
            }
        }
        return mapIconType;
    }

    /**
     * Gets the default map icon for initializing; when the map icon is unknown.
     * @returns {MapIconType}
     */
    getUnknownMapIconType() {
        return this.unknownMapIconType;
    }

    /**
     * Gets the map icon when clicking the obelisk to place a gateway.
     * @returns {MapIconType}
     */
    getAwakenedObeliskGatewayMapIconType() {
        return this.awakenedObeliskGatewayMapIconType;
    }

    /**
     *
     * @returns {*}
     */
    getSpells() {
        return this.spells;
    }

    /**
     * @param spellId {Number}
     * @returns {Spell}
     */
    getSpell(spellId) {
        let spell = null;
        for (let i = 0; i < this.spells.length; i++) {
            if (this.spells[i].id === spellId) {
                spell = this.spells[i];
                break;
            }
        }
        return spell;
    }

    /**
     * Get all the colors of all current classes.
     * @returns {[]}
     */
    getStaticClassColors() {
        return this._options.static.classColors;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticCharacterClasses() {
        return this._options.static.characterClasses;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticCharacterClassSpecializations() {
        return this._options.static.characterClassSpecializations;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticRaidMarkers() {
        return this._options.static.raidMarkers;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticFactions() {
        return this._options.static.factions;
    }

    /**
     *
     * @returns {[]}
     */
    getStaticPublishStates() {
        return this._options.static.publishStates;
    }

    /**
     *
     * @returns {string}
     */
    getType() {
        return this._options.type;
    }

    /**
     *
     * @returns {string}
     */
    getFaction() {
        return this._options.faction;
    }

    /**
     *
     * @param faction {Number}
     */
    setFaction(faction) {
        this._options.faction = faction;
    }

    /**
     *
     * @returns {Boolean}
     */
    getTeeming() {
        return this._options.teeming;
    }

    /**
     *
     * @param teeming {Boolean}
     */
    setTeeming(teeming) {
        this._options.teeming = teeming;

        // Let everyone know it's changed
        this.signal('teeming:changed', {teeming: this._options.teeming});
    }

    /**
     *
     * @returns {null}
     */
    getInitialFloorId() {
        return this._options.floorId;
    }

    /**
     * Finds a floor by id.
     * @param index {Number}
     * @returns {*}|bool
     */
    getFloorByIndex(index) {
        console.assert(this instanceof MapContext, 'this is not a MapContext', this);
        let result = false;

        for (let i = 0; i < this._options.dungeon.floors.length; i++) {
            let floor = this._options.dungeon.floors[i];
            if (floor.index === index) {
                result = floor;
                break;
            }
        }

        return result;
    }

    /**
     * Finds a floor by id.
     * @param floorId {Number}
     * @returns {*}|bool
     */
    getFloorById(floorId) {
        console.assert(this instanceof MapContext, 'this is not a MapContext', this);
        let result = false;

        for (let i = 0; i < this._options.dungeon.floors.length; i++) {
            let floor = this._options.dungeon.floors[i];
            if (floor.id === floorId) {
                result = floor;
                break;
            }
        }

        return result;
    }

    /**
     * @param excludeFloorId {Number}
     * @returns {*[]}
     */
    getFloorSelectValues(excludeFloorId = null) {
        if (excludeFloorId === null && typeof this.floorSelectValues !== 'undefined') {
            return this.floorSelectValues;
        }

        // Fill it with all floors except our current floor, we can't switch to our own floor, that'd be silly
        let dungeonData = this.getDungeon();
        let selectFloors = [];

        for (let i in dungeonData.floors) {
            if (dungeonData.floors.hasOwnProperty(i)) {
                let floor = dungeonData.floors[i];
                if (floor.id !== excludeFloorId) {
                    selectFloors.push({
                        id: floor.id,
                        name: lang.get(floor.name),
                    });
                }
            }
        }

        return selectFloors;
    }

    /**
     * Get the default floor
     * @returns {*}|bool
     */
    getDefaultFloor() {
        let result = this._options.dungeon.floors[0];

        for (let i = 0; i < this._options.dungeon.floors.length; i++) {
            let floor = this._options.dungeon.floors[i];
            if (floor.default) {
                result = floor;
                break;
            }
        }

        return result;
    }

    /**
     *
     * @param latLng {L.latLng}
     * @param floor {{}|Number}
     * @returns {{}}
     */
    getIngameXY(latLng, floor) {
        if (typeof floor === 'number') {
            let foundFloor = this.getFloorById(floor);

            if (typeof foundFloor !== 'object') {
                console.error(`Unable to convert ingame xy, cannot find floor for id ${floor}`);
            } else {
                floor = foundFloor;
            }
        }

        let ingameMapSizeX = floor.ingame_max_x - floor.ingame_min_x;
        let ingameMapSizeY = floor.ingame_max_y - floor.ingame_min_y;

        // Invert the lat/lngs
        let factorLat = ((MAP_MAX_LAT - latLng.lat) / MAP_MAX_LAT);
        let factorLng = ((MAP_MAX_LNG - latLng.lng) / MAP_MAX_LNG);

        return [
            (ingameMapSizeX * factorLng) + floor.ingame_min_x,
            (ingameMapSizeY * factorLat) + floor.ingame_min_y
        ];
    }

    /**
     *
     * @returns {{}}
     */
    getDungeon() {
        return this._options.dungeon;
    }

    /**
     *
     * @returns {{}}
     */
    getGameVersion() {
        return this._options.dungeon.game_version;
    }

    /**
     *
     * @returns {[]}
     */
    getEnemies() {
        return this._options.dungeon.enemies;
    }

    /**
     *
     * @returns {[]}
     */
    getEnemyPacks() {
        return this._options.dungeon.enemyPacks;
    }

    /**
     *
     * @returns {[]}
     */
    getEnemyPatrols() {
        return this._options.dungeon.enemyPatrols;
    }

    /**
     *
     * @returns {[]}
     */
    getMapIcons() {
        return this._options.dungeon.mapIcons;
    }

    /**
     *
     * @returns {[]}
     */
    getDungeonFloorSwitchMarkers() {
        return this._options.dungeon.dungeonFloorSwitchMarkers;
    }

    /**
     *
     * @returns {[]}
     */
    getMountableAreas() {
        return this._options.dungeon.mountableAreas;
    }

    /**
     *
     * @returns {[]}
     */
    getFloorUnions() {
        return this._options.dungeon.floorUnions;
    }

    /**
     *
     * @returns {[]}
     */
    getFloorUnionAreas() {
        return this._options.dungeon.floorUnionAreas;
    }

    /**
     *
     * @returns {[]}
     */
    getNpcs() {
        return this._options.npcs;
    }

    /**
     * @param npcId {Number}
     * @returns {[]}
     */
    findNpcById(npcId) {
        let result = null;

        for (let i = 0; i < this._options.dungeon.npcs.length; i++) {
            if (this._options.dungeon.npcs[i].id === npcId) {
                result = this._options.dungeon.npcs[i];
                break;
            }
        }

        return result;
    }

    /**
     *
     * @returns {Number}
     */
    getMinEnemySizeDefault() {
        return this._options.minEnemySizeDefault;
    }

    /**
     *
     * @returns {Number}
     */
    getMaxEnemySizeDefault() {
        return this._options.maxEnemySizeDefault;
    }

    /**
     *
     * @returns {Number}
     */
    getKeystoneScalingFactor() {
        return this._options.keystoneScalingFactor;
    }

    /**
     *
     * @returns {[]}
     */
    getAuras() {
        return this._options.dungeon.auras;
    }

    /**
     * @param auraId {Number}
     * @returns {[]}
     */
    findAuraById(auraId) {
        let result = null;

        for (let i = 0; i < this._options.dungeon.auras.length; i++) {
            if (this._options.dungeon.auras[i].id === auraId) {
                result = this._options.dungeon.auras[i];
                break;
            }
        }

        return result;
    }

    /**
     *
     * @returns {*}
     */
    getEchoChannelName() {
        return this._options.echoChannelName;
    }

    /**
     *
     * @returns {Number|null}
     */
    getUserPublicKey() {
        return this._options.userPublicKey;
    }

    /**
     *
     * @returns {Number}
     */
    getEnemyForcesRequired() {
        return this._options.mappingVersion.enemy_forces_required;
    }

    /**
     *
     * @returns {Number}
     */
    getEnemyForcesRequiredTeeming() {
        return this._options.mappingVersion.enemy_forces_required_teeming;
    }

    /**
     *
     * @returns {Number}
     */
    getEnemyForcesShrouded() {
        return this._options.mappingVersion.enemy_forces_shrouded;
    }

    /**
     *
     * @returns {Number}
     */
    getEnemyForcesShroudedZulGamux() {
        return this._options.mappingVersion.enemy_forces_shrouded_zul_gamux;
    }

    /**
     *
     * @returns [{npc_id: Number, count: Number}]
     */
    getDungeonSpeedrunRequiredNpcs10Man() {
        return this._options.dungeon.dungeon_speedrun_required_npcs10_man;
    }

    /**
     *
     * @returns [{npc_id: Number, count: Number}]
     */
    getDungeonSpeedrunRequiredNpcs25Man() {
        return this._options.dungeon.dungeon_speedrun_required_npcs25_man;
    }

    /**
     *
     * @returns {Boolean}
     */
    isDungeonSpeedrunEnabled() {
        return this._options.dungeon.speedrun_enabled;
    }

    /**
     *
     * @returns {id: Number, dungeon_id: Number, version: Number, facade_enabled: Boolean}
     */
    getDungeonLatestMappingVersion() {
        return this._options.dungeon.latestMappingVersion;
    }

    /**
     *
     * @returns {id: Number, dungeon_id: Number, version: Number, facade_enabled: Boolean}
     */
    getMappingVersion() {
        return this._options.mappingVersion;
    }

    /**
     *
     * @returns {String}
     */
    getMappingVersionUpgradeUrl() {
        return this._options.mappingVersionUpgradeUrl;
    }

    /**
     *
     * @returns {Number}
     */
    getNpcsMinHealth() {
        return this._options.npcsMinHealth;
    }

    /**
     *
     * @returns {Number}
     */
    getNpcsMaxHealth() {
        return this._options.npcsMaxHealth;
    }
}
