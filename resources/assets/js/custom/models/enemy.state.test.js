// ---------------------------------------------------------------------------
// Coverage for Enemy's state layer (#3674, target B): enemy forces calculation,
// the ignore/visibility filter, selection & kill zone state, the popup state
// transitions driven by the map state, and the NPC classification predicates.
//
// The tooltip (#3670) and popup-during-map-state (#3703) regressions live in the
// sibling enemy.test.js; this file deliberately does not repeat them.
//
// Follows the global-script model recipe documented at the top of killzone.test.js:
// define the globals enemy.js touches at LOAD time before require()-ing it, then
// hand the class fake collaborators instead of building a real DungeonMap.
// ---------------------------------------------------------------------------

// 1a. Leaflet: enemy.js builds div icons and draw handlers at load time.
global.L = {
    divIcon: function () {
    },
    Marker: {extend: () => function () {}},
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
    DomEvent: {preventDefault: () => {}},
};

// 1b. Map states. `_assignPopup()` and the constructor's map:mapstatechanged handler both
// test against MapState; EnemySelection is what onLayerInit() checks for.
global.MapState = class MapState {
};
global.EditMapState = class EditMapState extends global.MapState {
};
global.EnemySelection = class EnemySelection extends global.MapState {
};

// 1c. Collaborator classes only ever used through `instanceof`.
global.KillZone = class KillZone {
    constructor(id) {
        this.id = id;
    }
};
// The real chain is MapContextMappingVersionEdit -> MapContextMappingVersion -> MapContext, with
// MapContextDungeonRoute a SIBLING branch off MapContext - the mapping editor's context is not a
// dungeon route context. Enemy#getVisualData() branches on exactly that distinction.
global.MapContext = class MapContext {
};
global.MapContextDungeonRoute = class MapContextDungeonRoute extends global.MapContext {
};
global.MapContextMappingVersion = class MapContextMappingVersion extends global.MapContext {
};
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit extends global.MapContextMappingVersion {
};

// 1d. Constants referenced as bare globals, with their real values from constants.js.
global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';
global.AFFIX_BEGUILING = 'Beguiling';
global.AFFIX_AWAKENED = 'Awakened';
global.AFFIX_INSPIRING = 'Inspiring';
global.AFFIX_TORMENTED = 'Tormented';
global.AFFIX_ENCRYPTED = 'Encrypted';
global.AFFIX_SHROUDED = 'Shrouded';
global.ENEMY_SEASONAL_TYPE_BEGUILING = 'beguiling';
global.ENEMY_SEASONAL_TYPE_AWAKENED = 'awakened';
global.ENEMY_SEASONAL_TYPE_INSPIRING = 'inspiring';
global.ENEMY_SEASONAL_TYPE_TORMENTED = 'tormented';
global.ENEMY_SEASONAL_TYPE_ENCRYPTED = 'encrypted';
global.ENEMY_SEASONAL_TYPE_MDT_PLACEHOLDER = 'mdt_placeholder';
global.ENEMY_SEASONAL_TYPE_SHROUDED = 'shrouded';
global.ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX = 'shrouded_zul_gamux';
global.ENEMY_SEASONAL_TYPE_NO_SHROUDED = 'no_shrouded';
global.ENEMY_SEASONAL_TYPE_REQUIRES_ACTIVATION = 'requires_activation';
global.NPC_CLASSIFICATION_ID_BOSS = 3;
global.NPC_CLASSIFICATION_ID_RARE = 5;
global.NPC_TYPE_CRITTER = 3;
global.NPC_ID_NATHREZIM_INFILTRATOR = 189878;
global.NPC_ID_ZUL_GAMUX = 190128;
// Awakened/Prideful NPC ids are hardcoded in enemy.js itself, mirrored here for readability.
const NPC_IDS_AWAKENED = [161124, 161241, 161244, 161243];
const NPC_ID_AWAKENED_OBELISK = NPC_IDS_AWAKENED[0];
const NPC_ID_PRIDEFUL = 173729;

// 1e. Lightweight base class standing in for VersionableMapObject -> MapObject, providing only
// what Enemy calls on `super` / `this`. Emitted signals are recorded on `_emittedSignals`.
global.VersionableMapObject = class VersionableMapObject {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this.id = 0;
        this._cachedAttributes = null;
        this._emittedSignals = [];
    }

    register() {
    }

    unregister() {
    }

    signal(event, data) {
        this._emittedSignals.push({event, data});
    }

    _getAttributes() {
        return [];
    }

    // Enemy#shouldBeVisible() short-circuits on shouldBeIgnored() and otherwise defers to the base
    // chain, which mapobject.test.js covers in full. Returning true here isolates the Enemy half.
    shouldBeVisible() {
        return true;
    }

    isVisibleOnScreen() {
        return true;
    }

    // Mirrors MapObject#_assignPopup, guard included: it only binds on an editable map object of an
    // editable map. Enemy#isEditable() returns false, so a plain enemy never gets a popup bound -
    // dropping that guard here would let a test assert a bind production never performs.
    _assignPopup(layer = null) {
        const targetLayer = layer === null ? this.layer : layer;

        if (targetLayer !== null) {
            targetLayer.off('popupopen');
            targetLayer.unbindPopup();

            if (this.map.options.edit && this.isEditable() && this.isEditableByPopup()) {
                targetLayer.bindPopup('<div>popup</div>', {});
            }
        }
    }

    isEditable() {
        return true;
    }

    isEditableByPopup() {
        return true;
    }

    unbindTooltip() {
    }

    loadRemoteMapObject() {
    }
};

// 1f. Handlebars template + default variables used to render the tooltip body.
global.Handlebars = {
    templates: {
        enemy_tooltip_template: (data) => `<div>${data.name}</div>`,
    },
};
global.getHandlebarsDefaultVariables = () => ({});

// 1g. The shared setup's `$` stub has no extend().
global.$.extend = Object.assign;

/**
 * Installs a fake global state (and map context) for the duration of a test. Must run BEFORE the
 * enemy is constructed - the Enemy constructor registers on the map context.
 *
 * `mappingEditor` drives BOTH `isMapAdmin()` and the map context type on purpose: they are the
 * same condition in production - `StateManager#isMapAdmin()` is literally
 * `this._mapContext instanceof MapContextMappingVersionEdit` (statemanager.js). shouldBeIgnored()
 * happens to consult them through two different expressions, and modelling those as independent
 * knobs would let a test assert a state the app can never be in.
 *
 * @param {Object} options
 * @param {boolean} options.mappingEditor Whether we're in the admin mapping editor.
 * @param {boolean} options.teeming Whether the map itself is teeming.
 * @param {Array<String>} options.affixes The affixes active on the route.
 * @param {Number} options.enemyForcesShrouded Forces a shrouded enemy is worth.
 * @param {Number} options.enemyForcesShroudedZulGamux Forces the Zul'Gamux variant is worth.
 * @param {Number|null} options.dungeonDifficulty The route's dungeon difficulty.
 * @param {boolean} options.mdtMappingMode Whether MDT mapping mode is enabled.
 */
function setFakeState({
                          mappingEditor = false,
                          teeming = false,
                          affixes = [],
                          enemyForcesShrouded = 0,
                          enemyForcesShroudedZulGamux = 0,
                          dungeonDifficulty = null,
                          mdtMappingMode = false,
                      } = {}) {
    const mapContext = mappingEditor
        ? new global.MapContextMappingVersionEdit()
        : new global.MapContextDungeonRoute();

    Object.assign(mapContext, {
        register: () => {
        },
        unregister: () => {
        },
        getAuras: () => [],
        // MapContextMappingVersion hard-codes both of these ("when mapping a dungeon assume we have
        // all affixes so things show up properly"), so the editor cannot be short an affix or
        // non-teeming. Honouring that here keeps the caller's affixes/teeming from describing a
        // state the editor can never be in.
        getTeeming: () => mappingEditor || teeming,
        hasAffix: (affix) => mappingEditor || affixes.includes(affix),
        getEnemyForcesShrouded: () => enemyForcesShrouded,
        getEnemyForcesShroudedZulGamux: () => enemyForcesShroudedZulGamux,
        getDungeonDifficulty: () => dungeonDifficulty,
    });

    global.getState = () => ({
        isMapAdmin: () => mappingEditor,
        getMapContext: () => mapContext,
        getMdtMappingModeEnabled: () => mdtMappingMode,
    });
}

setFakeState();

const {Enemy} = require('./enemy');

// 1h. Only ever used through `instanceof` (raid markers are not available on the admin mapping page).
global.AdminEnemy = class AdminEnemy extends Enemy {
};

/**
 * A fake Leaflet layer recording the popup binds/unbinds a state transition performs.
 *
 * @returns {Object}
 */
function makeFakeLayer() {
    return {
        popupBindCount: 0,
        popupUnbindCount: 0,
        on() {
        },
        off() {
        },
        bindPopup() {
            this.popupBindCount++;
        },
        unbindPopup() {
            this.popupUnbindCount++;
        },
        bindTooltip() {
        },
        unbindTooltip() {
        },
        getTooltip() {
            return null;
        },
        getLatLng: () => ({lat: 0, lng: 0}),
    };
}

/**
 * A fake DungeonMap exposing only what Enemy touches: an event bus whose listeners can be fired
 * by hand, the enemy forces manager, a kill zone group and the current map state.
 *
 * @param {Object} options
 * @param {MapState|null} options.mapState The currently active map state.
 * @param {Number} options.enemyForcesRequired Total forces needed for the route.
 * @param {Object} options.killZonesById Kill zones resolvable by id.
 * @returns {Object}
 */
function makeFakeMap({mapState = null, edit = false, enemyForcesRequired = 100, killZonesById = {}} = {}) {
    const listeners = {};
    let currentMapState = mapState;

    return {
        options: {edit},
        listeners,
        register(name, listener, fn) {
            (listeners[name] = listeners[name] ?? []).push(fn);
        },
        unregister() {
        },
        getMapState: () => currentMapState,
        enemyForcesManager: {
            getEnemyForcesRequired: () => enemyForcesRequired,
        },
        mapObjectGroupManager: {
            getByName: () => ({
                findMapObjectById: (id) => killZonesById[id] ?? null,
            }),
        },
        /**
         * Switches the map state and notifies listeners, mirroring DungeonMap#setMapState(): the
         * new state is in place BEFORE the event is delivered, so a listener that calls back into
         * getMapState() sees the same world the signal describes.
         *
         * @param {MapState|null} newMapState
         */
        setMapState(newMapState) {
            const previousMapState = currentMapState;
            currentMapState = newMapState;

            for (const fn of listeners['map:mapstatechanged'] ?? []) {
                fn({
                    name: 'map:mapstatechanged',
                    context: this,
                    data: {newMapState, previousMapState},
                });
            }
        },
    };
}

/**
 * Builds an Enemy with a fake map and layer, with any per-instance properties applied on top.
 *
 * The two overrides are seeded to null because that is what a *loaded* enemy has: neither Attribute
 * declares a `default:` (so MapObject#_setDefaults() skips them both), and they only become null
 * when loadRemoteMapObject() assigns them from the server payload. Without the seeding these would
 * be `undefined` here, and `undefined !== null` takes the override branch in getEnemyForces() -
 * which is also exactly what a client-constructed, never-loaded enemy would hit in the browser.
 *
 * @param {Object} properties
 * @param {Object|null} map
 * @returns {Enemy}
 */
function makeEnemy(properties = {}, map = null) {
    return Object.assign(new Enemy(map ?? makeFakeMap(), null), {
        enemy_forces_override: null,
        enemy_forces_override_teeming: null,
    }, properties);
}

/**
 * An npc worth `enemyForces` forces (and `enemyForcesTeeming` on a teeming map).
 *
 * @param {Object} options
 * @returns {Object}
 */
function makeNpc({id = 1, enemyForces = 10, enemyForcesTeeming = null, classificationId = 1, npcTypeId = 1} = {}) {
    return {
        id,
        name: 'Murkbrine Shorerunner',
        classification_id: classificationId,
        npc_type_id: npcTypeId,
        enemy_forces: {enemy_forces: enemyForces, enemy_forces_teeming: enemyForcesTeeming},
    };
}

const signalsOf = (enemy, event) => enemy._emittedSignals.filter((signal) => signal.event === event);

describe('Enemy.getEnemyForces', () => {
    test('getEnemyForces_givenNoNpc_returnsZero', () => {
        setFakeState();

        expect(makeEnemy({npc: null}).getEnemyForces()).toBe(0);
    });

    test('getEnemyForces_givenAnNpcWithoutEnemyForces_returnsZero', () => {
        setFakeState();

        expect(makeEnemy({npc: {id: 1, enemy_forces: null}}).getEnemyForces()).toBe(0);
    });

    test('getEnemyForces_givenAPlainEnemy_returnsTheNpcForces', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({enemyForces: 12})}).getEnemyForces()).toBe(12);
    });

    test('getEnemyForces_givenATeemingMap_returnsTheTeemingNpcForces', () => {
        setFakeState({teeming: true});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12, enemyForcesTeeming: 8}),
        }).getEnemyForces()).toBe(8);
    });

    test('getEnemyForces_givenATeemingMapAndNoTeemingNpcForces_returnsTheRegularNpcForces', () => {
        setFakeState({teeming: true});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12, enemyForcesTeeming: null}),
        }).getEnemyForces()).toBe(12);
    });

    test('getEnemyForces_givenATeemingMapAndATeemingOverride_returnsTheOverride', () => {
        setFakeState({teeming: true});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12, enemyForcesTeeming: 8}),
            enemy_forces_override_teeming: 3,
        }).getEnemyForces()).toBe(3);
    });

    test('getEnemyForces_givenAnOverride_returnsTheOverride', () => {
        setFakeState();

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12}),
            enemy_forces_override: 5,
        }).getEnemyForces()).toBe(5);
    });

    test('getEnemyForces_givenANonTeemingMap_ignoresTheTeemingOverride', () => {
        setFakeState({teeming: false});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12, enemyForcesTeeming: 8}),
            enemy_forces_override_teeming: 3,
        }).getEnemyForces()).toBe(12);
    });

    test('getEnemyForces_givenAShroudedEnemy_returnsTheRouteShroudedForces', () => {
        setFakeState({affixes: [AFFIX_SHROUDED], enemyForcesShrouded: 45});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12}),
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED,
        }).getEnemyForces()).toBe(45);
    });

    test('getEnemyForces_givenAShroudedEnemyOnATeemingMap_prefersTheShroudedForces', () => {
        // The shrouded override is checked before teeming, so it wins over both the teeming npc
        // value and an explicit teeming override.
        setFakeState({teeming: true, affixes: [AFFIX_SHROUDED], enemyForcesShrouded: 45});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12, enemyForcesTeeming: 8}),
            enemy_forces_override_teeming: 3,
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED,
        }).getEnemyForces()).toBe(45);
    });

    test('getEnemyForces_givenAZulGamuxEnemy_returnsTheZulGamuxForces', () => {
        setFakeState({affixes: [AFFIX_SHROUDED], enemyForcesShrouded: 45, enemyForcesShroudedZulGamux: 90});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12}),
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
        }).getEnemyForces()).toBe(90);
    });

    test('getEnemyForces_givenATeemingMapAndOnlyAPlainOverride_ignoresThatOverride', () => {
        // The teeming branch and the plain-override branch are alternatives in one else-if chain,
        // so on a teeming map a plain override never applies - the mirror of the test above.
        setFakeState({teeming: true});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12, enemyForcesTeeming: 8}),
            enemy_forces_override: 5,
        }).getEnemyForces()).toBe(8);
    });

    test('getEnemyForces_givenAZulGamuxEnemyWithoutTheAffix_returnsTheRegularNpcForces', () => {
        setFakeState({affixes: [], enemyForcesShroudedZulGamux: 90});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12}),
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
        }).getEnemyForces()).toBe(12);
    });

    test('getEnemyForces_givenAShroudedEnemyWithoutTheAffix_returnsTheRegularNpcForces', () => {
        // isShrouded() also requires the affix to be active, so without it the enemy is worth
        // whatever its npc is worth.
        setFakeState({affixes: [], enemyForcesShrouded: 45});

        expect(makeEnemy({
            npc: makeNpc({enemyForces: 12}),
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED,
        }).getEnemyForces()).toBe(12);
    });
});

describe('Enemy._getPercentageString', () => {
    test('_getPercentageString_givenForces_returnsThePercentageOfTheRouteTotal', () => {
        setFakeState();
        const enemy = makeEnemy({}, makeFakeMap({enemyForcesRequired: 300}));

        expect(enemy._getPercentageString(6)).toBe('(2%)');
    });

    test('_getPercentageString_givenARepeatingFraction_roundsToTwoDecimals', () => {
        setFakeState();
        const enemy = makeEnemy({}, makeFakeMap({enemyForcesRequired: 300}));

        expect(enemy._getPercentageString(1)).toBe('(0.33%)');
    });
});

describe('Enemy.setNpc', () => {
    test('setNpc_givenAnNpcWithForces_copiesTheForcesAndSignals', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setNpc(makeNpc({id: 7, enemyForces: 12, enemyForcesTeeming: 8}));

        expect(enemy.npc_id).toBe(7);
        expect(enemy.enemy_forces).toBe(12);
        expect(enemy.enemy_forces_teeming).toBe(8);
        expect(signalsOf(enemy, 'enemy:set_npc')).toHaveLength(1);
    });

    test('setNpc_givenAnNpcWithoutForces_zeroesTheForces', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setNpc({id: 7, name: 'Nameless'});

        expect(enemy.enemy_forces).toBe(0);
        expect(enemy.enemy_forces_teeming).toBeNull();
    });

    test('setNpc_givenNull_clearsTheNpcIdAndStillSignals', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setNpc(null);

        expect(enemy.npc_id).toBeNull();
        expect(signalsOf(enemy, 'enemy:set_npc')).toHaveLength(1);
    });
});

describe('Enemy.shouldBeIgnored - awakened enemies linked to the last boss', () => {
    /**
     * @param {Object} properties
     * @returns {Enemy}
     */
    const makeAwakenedEnemy = (properties = {}) => makeEnemy({
        npc: makeNpc({id: NPC_ID_AWAKENED_OBELISK}),
        ...properties,
    });

    test('shouldBeIgnored_givenAnAwakenedEnemyLinkedToTheLastBossWithoutAKillZone_returnsTrue', () => {
        setFakeState({affixes: [AFFIX_AWAKENED]});
        const enemy = makeAwakenedEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_AWAKENED});
        enemy.isLinkedToLastBoss = () => true;

        expect(enemy.shouldBeIgnored()).toBe(true);
    });

    test('shouldBeIgnored_givenTheSameEnemyWithAKillZone_returnsFalse', () => {
        setFakeState({affixes: [AFFIX_AWAKENED]});
        const enemy = makeAwakenedEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_AWAKENED});
        enemy.isLinkedToLastBoss = () => true;
        enemy.setKillZone(new KillZone(1));

        expect(enemy.shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenTheSameEnemyForAMapAdmin_returnsFalse', () => {
        setFakeState({mappingEditor: true, affixes: [AFFIX_AWAKENED]});
        const enemy = makeAwakenedEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_AWAKENED});
        enemy.isLinkedToLastBoss = () => true;

        expect(enemy.shouldBeIgnored()).toBe(false);
    });
});

describe('Enemy.shouldBeIgnored - seasonal types', () => {
    test.each([
        ['beguiling', ENEMY_SEASONAL_TYPE_BEGUILING, AFFIX_BEGUILING],
        ['awakened', ENEMY_SEASONAL_TYPE_AWAKENED, AFFIX_AWAKENED],
        ['tormented', ENEMY_SEASONAL_TYPE_TORMENTED, AFFIX_TORMENTED],
        ['encrypted', ENEMY_SEASONAL_TYPE_ENCRYPTED, AFFIX_ENCRYPTED],
    ])('shouldBeIgnored_given%sEnemyWithoutItsAffix_returnsTrue', (label, seasonalType) => {
        setFakeState({affixes: []});

        expect(makeEnemy({seasonal_type: seasonalType}).shouldBeIgnored()).toBe(true);
    });

    test.each([
        ['beguiling', ENEMY_SEASONAL_TYPE_BEGUILING, AFFIX_BEGUILING],
        ['awakened', ENEMY_SEASONAL_TYPE_AWAKENED, AFFIX_AWAKENED],
        ['tormented', ENEMY_SEASONAL_TYPE_TORMENTED, AFFIX_TORMENTED],
        ['encrypted', ENEMY_SEASONAL_TYPE_ENCRYPTED, AFFIX_ENCRYPTED],
    ])('shouldBeIgnored_given%sEnemyWithItsAffix_returnsFalse', (label, seasonalType, affix) => {
        setFakeState({affixes: [affix]});

        expect(makeEnemy({seasonal_type: seasonalType}).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenAnMdtPlaceholder_returnsTrue', () => {
        // Placeholders only exist to suppress import warnings, so no affix ever brings them back.
        setFakeState({affixes: [AFFIX_SHROUDED]});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_MDT_PLACEHOLDER}).shouldBeIgnored()).toBe(true);
    });

    test('shouldBeIgnored_givenANoShroudedEnemyWhileShroudedIsActive_returnsTrue', () => {
        // "no shrouded" enemies are the ones being replaced by the shrouded variant.
        setFakeState({affixes: [AFFIX_SHROUDED]});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_NO_SHROUDED}).shouldBeIgnored()).toBe(true);
    });

    test('shouldBeIgnored_givenANoShroudedEnemyWithoutTheAffix_returnsFalse', () => {
        setFakeState({affixes: []});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_NO_SHROUDED}).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenTheNathrezimInfiltratorWithoutTheShroudedAffix_returnsTrue', () => {
        // Arrange: every seeded npc 189878 row carries seasonal_type 'shrouded'
        setFakeState({affixes: []});
        const enemy = makeEnemy({
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED,
            npc: makeNpc({id: NPC_ID_NATHREZIM_INFILTRATOR}),
        });

        // Act + Assert
        expect(enemy.shouldBeIgnored()).toBe(true);
    });

    test("shouldBeIgnored_givenZulGamuxAsSeededWithoutTheShroudedAffix_returnsFalse", () => {
        // Arrange: the special case at the bottom of shouldBeIgnored() names NPC_ID_ZUL_GAMUX, but
        // it is gated on seasonal_type === 'shrouded' - and every seeded npc 190128 row carries
        // 'shrouded_zul_gamux' instead, so the branch is unreachable for it. Pinned as-is: a real
        // Zul'Gamux stays visible without the affix (and isShroudedZulGamux() is false, so
        // getEnemyForces() falls back to its npc value). Flagged on the MR rather than "fixed"
        // here, since which half is wrong is a mapping question.
        setFakeState({affixes: []});
        const enemy = makeEnemy({
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED_ZUL_GAMUX,
            npc: makeNpc({id: NPC_ID_ZUL_GAMUX}),
        });

        // Act + Assert
        expect(enemy.shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenTheNathrezimInfiltratorWithTheShroudedAffix_returnsFalse', () => {
        setFakeState({affixes: [AFFIX_SHROUDED]});

        expect(makeEnemy({
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED,
            npc: makeNpc({id: NPC_ID_NATHREZIM_INFILTRATOR}),
        }).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenARegularEnemyMarkedShroudedWithoutTheAffix_returnsFalse', () => {
        // The MDT mapping marks the replaced trash mob itself as shrouded rather than adding the
        // Infiltrator; hiding those left dungeons with no enemy at all when Shrouded was inactive.
        setFakeState({affixes: []});

        expect(makeEnemy({
            seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED,
            npc: makeNpc({id: 12345}),
        }).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenASeasonalEnemyInTheMappingEditor_returnsFalse', () => {
        // The mapping editor must show everything regardless of which affixes a route has.
        setFakeState({mappingEditor: true, affixes: []});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_MDT_PLACEHOLDER}).shouldBeIgnored()).toBe(false);
    });
});

describe('Enemy.shouldBeIgnored - dungeon difficulty and critters', () => {
    test('shouldBeIgnored_givenAMismatchingDungeonDifficulty_returnsTrue', () => {
        setFakeState({dungeonDifficulty: 1});

        expect(makeEnemy({dungeon_difficulty: 2}).shouldBeIgnored()).toBe(true);
    });

    test('shouldBeIgnored_givenAMatchingDungeonDifficulty_returnsFalse', () => {
        setFakeState({dungeonDifficulty: 2});

        expect(makeEnemy({dungeon_difficulty: 2}).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenNoDungeonDifficulty_returnsFalse', () => {
        setFakeState({dungeonDifficulty: 2});

        expect(makeEnemy({dungeon_difficulty: null}).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenACritter_returnsTrue', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({npcTypeId: NPC_TYPE_CRITTER})}).shouldBeIgnored()).toBe(true);
    });

    test('shouldBeIgnored_givenACritterWithAMatchingDungeonDifficulty_returnsFalse', () => {
        // The dungeon difficulty branch returns outright, so it shadows the critter check below it:
        // a critter that carries a matching difficulty is never filtered out as a critter.
        setFakeState({dungeonDifficulty: 2});

        expect(makeEnemy({
            dungeon_difficulty: 2,
            npc: makeNpc({npcTypeId: NPC_TYPE_CRITTER}),
        }).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenACritterInTheMappingEditor_returnsFalse', () => {
        setFakeState({mappingEditor: true});

        expect(makeEnemy({npc: makeNpc({npcTypeId: NPC_TYPE_CRITTER})}).shouldBeIgnored()).toBe(false);
    });
});

describe('Enemy.shouldBeIgnored - MDT enemies', () => {
    test('shouldBeIgnored_givenAnMdtEnemyWithMdtMappingModeDisabled_returnsTrue', () => {
        setFakeState({mdtMappingMode: false});

        expect(makeEnemy({is_mdt: true}).shouldBeIgnored()).toBe(true);
    });

    test('shouldBeIgnored_givenAnMdtEnemyWithMdtMappingModeEnabled_returnsFalse', () => {
        setFakeState({mdtMappingMode: true});

        expect(makeEnemy({is_mdt: true}).shouldBeIgnored()).toBe(false);
    });

    test('shouldBeIgnored_givenAnMdtEnemyInTheMappingEditor_stillReturnsTrue', () => {
        // The MDT check sits outside the mapping-editor bail-out, unlike every check above it.
        setFakeState({mappingEditor: true, mdtMappingMode: false});

        expect(makeEnemy({is_mdt: true}).shouldBeIgnored()).toBe(true);
    });
});

describe('Enemy.shouldBeVisible', () => {
    test('shouldBeVisible_givenAnIgnoredEnemy_returnsFalse', () => {
        setFakeState();
        const enemy = makeEnemy();
        enemy.shouldBeIgnored = () => true;

        expect(enemy.shouldBeVisible()).toBe(false);
    });

    test('shouldBeVisible_givenAnEnemyThatIsNotIgnored_defersToTheBaseChain', () => {
        setFakeState();
        const enemy = makeEnemy();
        enemy.shouldBeIgnored = () => false;

        expect(enemy.shouldBeVisible()).toBe(true);
    });
});

describe('Enemy selection state', () => {
    test('isSelectable_givenAnEnemyWithoutAVisual_returnsFalse', () => {
        setFakeState();
        const enemy = makeEnemy({visual: null});

        enemy.setSelectable(true);

        expect(enemy.isSelectable()).toBe(false);
    });

    test('isSelectable_givenASelectableEnemyWithAVisual_returnsTrue', () => {
        setFakeState();
        const enemy = makeEnemy({visual: {}});

        enemy.setSelectable(true);

        expect(enemy.isSelectable()).toBe(true);
    });

    test('isSelectable_givenANonSelectableEnemyWithAVisual_returnsFalse', () => {
        setFakeState();
        const enemy = makeEnemy({visual: {}});

        expect(enemy.isSelectable()).toBe(false);
    });

    test('isVisibleOnScreen_givenAnEnemyWithoutAVisual_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({visual: null}).isVisibleOnScreen()).toBe(false);
    });

    test('isVisibleOnScreen_givenAnEnemyWithAVisual_defersToTheBaseChain', () => {
        setFakeState();

        expect(makeEnemy({visual: {}}).isVisibleOnScreen()).toBe(true);
    });
});

describe('Enemy.setKillZone', () => {
    test('setKillZone_givenAKillZone_signalsAttachedOnly', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setKillZone(new KillZone(1));

        expect(enemy.getKillZone().id).toBe(1);
        expect(signalsOf(enemy, 'killzone:attached')).toHaveLength(1);
        expect(signalsOf(enemy, 'killzone:detached')).toHaveLength(0);
    });

    test('setKillZone_givenADifferentKillZone_signalsBothAttachedAndDetached', () => {
        setFakeState();
        const enemy = makeEnemy();
        enemy.setKillZone(new KillZone(1));

        enemy.setKillZone(new KillZone(2));

        expect(signalsOf(enemy, 'killzone:attached')).toHaveLength(2);
        expect(signalsOf(enemy, 'killzone:detached')).toHaveLength(1);
    });

    test('setKillZone_givenTheSameKillZoneId_doesNotSignalDetached', () => {
        setFakeState();
        const enemy = makeEnemy();
        enemy.setKillZone(new KillZone(1));

        enemy.setKillZone(new KillZone(1));

        expect(signalsOf(enemy, 'killzone:detached')).toHaveLength(0);
    });

    test('setKillZone_givenNull_signalsDetachedOnly', () => {
        setFakeState();
        const enemy = makeEnemy();
        enemy.setKillZone(new KillZone(1));

        enemy.setKillZone(null);

        expect(enemy.getKillZone()).toBeNull();
        expect(signalsOf(enemy, 'killzone:attached')).toHaveLength(1);
        expect(signalsOf(enemy, 'killzone:detached')).toHaveLength(1);
    });
});

describe('Enemy overpull state', () => {
    test('setOverpulledKillZoneId_givenANewId_signalsOnce', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setOverpulledKillZoneId(3);

        expect(enemy.getOverpulledKillZoneId()).toBe(3);
        expect(signalsOf(enemy, 'overpulled:changed')).toHaveLength(1);
    });

    test('setOverpulledKillZoneId_givenTheSameId_doesNotSignalAgain', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setOverpulledKillZoneId(3);
        enemy.setOverpulledKillZoneId(3);

        expect(signalsOf(enemy, 'overpulled:changed')).toHaveLength(1);
    });

    test('getOverpulledKillZone_givenAnId_resolvesItThroughTheKillZoneGroup', () => {
        setFakeState();
        const killZone = new KillZone(3);
        const enemy = makeEnemy({}, makeFakeMap({killZonesById: {3: killZone}}));
        enemy.setOverpulledKillZoneId(3);

        expect(enemy.getOverpulledKillZone()).toBe(killZone);
    });

    test('getOverpulledKillZone_givenNoId_returnsNull', () => {
        setFakeState();

        expect(makeEnemy().getOverpulledKillZone()).toBeNull();
    });
});

describe('Enemy obsolete state', () => {
    test('setObsolete_givenAChangedValue_signalsOnce', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setObsolete(true);

        expect(enemy.isObsolete()).toBe(true);
        expect(signalsOf(enemy, 'obsolete:changed')).toHaveLength(1);
    });

    test('setObsolete_givenTheSameValue_doesNotSignal', () => {
        setFakeState();
        const enemy = makeEnemy();

        enemy.setObsolete(false);

        expect(signalsOf(enemy, 'obsolete:changed')).toHaveLength(0);
    });
});

describe('Enemy popup state transitions', () => {
    // These cover the constructor's `map:mapstatechanged` wiring - which enemy.test.js does not
    // touch, testing _assignPopup() directly instead. An editable enemy on an editable map is what
    // it takes to reach a bind at all: MapObject#_assignPopup() only binds when the map is
    // editable and the object says it is (Enemy#isEditable() returns false; AdminEnemy overrides).
    /**
     * @returns {{enemy: Enemy, map: Object, layer: Object}}
     */
    function makeEditableEnemyOnEditMap() {
        const map = makeFakeMap({edit: true});
        const layer = makeFakeLayer();
        const enemy = makeEnemy({layer}, map);
        enemy.isEditable = () => true;

        return {enemy, map, layer};
    }

    test('setPopupEnabled_givenAMapStateBecomingActive_unbindsThePopup', () => {
        // Arrange
        setFakeState();
        const {enemy, map, layer} = makeEditableEnemyOnEditMap();

        // Act
        map.setMapState(new EnemySelection());

        // Assert
        expect(enemy.isPopupEnabled).toBe(false);
        expect(layer.popupUnbindCount).toBe(1);
        expect(layer.popupBindCount).toBe(0);
    });

    test('setPopupEnabled_givenAMapStateEnding_bindsThePopupAgain', () => {
        // Arrange
        setFakeState();
        const {enemy, map, layer} = makeEditableEnemyOnEditMap();
        map.setMapState(new EnemySelection());

        // Act
        map.setMapState(null);

        // Assert
        expect(enemy.isPopupEnabled).toBe(true);
        expect(layer.popupBindCount).toBe(1);
    });

    test('setPopupEnabled_givenANonEditableEnemy_neverBindsAPopup', () => {
        // Arrange: a plain enemy on a route map - Enemy#isEditable() is false
        setFakeState();
        const map = makeFakeMap({edit: true});
        const layer = makeFakeLayer();
        const enemy = makeEnemy({layer}, map);

        // Act
        map.setMapState(new EnemySelection());
        map.setMapState(null);

        // Assert
        expect(enemy.isPopupEnabled).toBe(true);
        expect(layer.popupBindCount).toBe(0);
    });

    test('setPopupEnabled_givenNoLayer_stillRecordsTheFlag', () => {
        // Arrange
        setFakeState();
        const enemy = makeEnemy({layer: null});

        // Act
        enemy.setPopupEnabled(true);

        // Assert
        expect(enemy.isPopupEnabled).toBe(true);
    });
});

describe('Enemy NPC classification', () => {
    test('isRareNpc_givenARareNpc_returnsTrue', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({classificationId: NPC_CLASSIFICATION_ID_RARE})}).isRareNpc()).toBe(true);
    });

    test('isRareNpc_givenAnotherClassification_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({classificationId: NPC_CLASSIFICATION_ID_BOSS})}).isRareNpc()).toBe(false);
    });

    test('isRareNpc_givenNoNpc_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: null}).isRareNpc()).toBe(false);
    });

    test('isBossNpc_givenABossClassification_returnsTrue', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({classificationId: NPC_CLASSIFICATION_ID_BOSS})}).isBossNpc()).toBe(true);
    });

    test('isBossNpc_givenALowerClassification_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({classificationId: 2})}).isBossNpc()).toBe(false);
    });

    test('isBossNpc_givenNoNpc_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: null}).isBossNpc()).toBe(false);
    });

    test('isBossNpc_givenARareNpc_returnsTrue', () => {
        // The check is `>= NPC_CLASSIFICATION_ID_BOSS` (3) and rare is 5, so every rare counts as a
        // boss here. Pinned deliberately: it drives isImportant(), and thus what stays visible.
        setFakeState();

        expect(makeEnemy({npc: makeNpc({classificationId: NPC_CLASSIFICATION_ID_RARE})}).isBossNpc()).toBe(true);
    });

    test.each(NPC_IDS_AWAKENED)('isAwakenedNpc_givenAwakenedNpc%i_returnsTrue', (npcId) => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({id: npcId})}).isAwakenedNpc()).toBe(true);
    });

    test('isAwakenedNpc_givenAnotherNpc_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({id: 1})}).isAwakenedNpc()).toBe(false);
    });

    test('isPridefulNpc_givenThePridefulNpc_returnsTrue', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({id: NPC_ID_PRIDEFUL})}).isPridefulNpc()).toBe(true);
    });

    test('isPridefulNpc_givenAnotherNpc_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({id: 1})}).isPridefulNpc()).toBe(false);
    });

    test('isInspiring_givenAnInspiringEnemyWithTheAffix_returnsTrue', () => {
        setFakeState({affixes: [AFFIX_INSPIRING]});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_INSPIRING}).isInspiring()).toBe(true);
    });

    test('isInspiring_givenAnInspiringEnemyWithoutTheAffix_returnsFalse', () => {
        setFakeState({affixes: []});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_INSPIRING}).isInspiring()).toBe(false);
    });

    test('isTormented_givenATormentedEnemy_returnsTrueRegardlessOfTheAffix', () => {
        // Unlike isInspiring()/isShrouded(), this one does not consult the affix at all.
        setFakeState({affixes: []});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_TORMENTED}).isTormented()).toBe(true);
    });

    test('isImportant_givenAPlainTrashMob_returnsFalse', () => {
        setFakeState();

        expect(makeEnemy({npc: makeNpc({classificationId: 1})}).isImportant()).toBe(false);
    });

    test.each([
        ['a boss', {npc: makeNpc({classificationId: NPC_CLASSIFICATION_ID_BOSS})}],
        ['a prideful npc', {npc: makeNpc({id: NPC_ID_PRIDEFUL})}],
        ['an awakened npc', {npc: makeNpc({id: NPC_ID_AWAKENED_OBELISK})}],
        ['a tormented enemy', {seasonal_type: ENEMY_SEASONAL_TYPE_TORMENTED}],
    ])('isImportant_given%s_returnsTrue', (label, properties) => {
        setFakeState();

        expect(makeEnemy(properties).isImportant()).toBe(true);
    });

    test('isImportant_givenAShroudedEnemyWithTheAffix_returnsTrue', () => {
        setFakeState({affixes: [AFFIX_SHROUDED]});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED}).isImportant()).toBe(true);
    });

    test('isImportant_givenAShroudedEnemyWithoutTheAffix_returnsFalse', () => {
        setFakeState({affixes: []});

        expect(makeEnemy({seasonal_type: ENEMY_SEASONAL_TYPE_SHROUDED}).isImportant()).toBe(false);
    });
});
