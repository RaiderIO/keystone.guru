// The satellite pill is drawn for a floor a checkpoint has members on but is not anchored to. It is
// added straight to the leaflet map rather than to the owning MapObjectGroup's layer group, so
// `MapObjectGroup.setVisibility(false)` (the "Enemy forces checkpoints" entry in the Map Elements
// dropdown) cannot reach it - it only ever removes each map object's own `layer`.
//
// Before this was gated, hiding the group left an orphan satellite on the map that `refreshPill()`
// re-created on every floor switch, and the choice is persisted in the `hidden_map_object_groups`
// cookie - so it came back on every page load with no way to get rid of it.
//
// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.VersionableMapObject = class VersionableMapObject {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
    }

    bindTooltip() {}
};
global.NUMBER_STYLE_ENEMY_FORCES = 'enemy_forces';
global.NUMBER_STYLE_PERCENTAGE = 'percentage';
global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.MAP_OBJECT_GROUP_ENEMY_FORCES_CHECKPOINT = 'enemyforcescheckpoint';

// Leaflet bits used at load time (the icon/marker/draw handler definitions at the top of the file).
global.L = {
    divIcon: function (options) {
        return {options};
    },
    Marker: {extend: (o) => o},
    Draw: {
        Marker: {extend: (o) => o},
        Feature: {prototype: {initialize: () => {}}},
    },
};

const {EnemyForcesCheckpoint} = require('./enemyforcescheckpoint');

/**
 * Builds a checkpoint on a bare prototype (Object.create), so none of the constructor's signal wiring
 * has to run, with just the collaborators `_refreshSatellitePill` reaches for.
 *
 * @param options {{groupIsShown?: Boolean, currentFloorId?: Number}}
 */
function createCheckpoint({groupIsShown = true, currentFloorId = 2} = {}) {
    const removedLayers = [];
    const addedLayers = [];

    const mapObjectGroup = {
        isShown: () => groupIsShown,
        objects: {},
    };

    const enemyMapObjectGroup = {
        objects: {
            1: {id: 1, enemy_forces_checkpoint_id: 55, floor_id: 2, source_floor_id: null, lat: 10, lng: 20},
            2: {id: 2, enemy_forces_checkpoint_id: 55, floor_id: 2, source_floor_id: null, lat: 30, lng: 40},
        },
    };

    const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
    checkpoint.id = 55;
    // Anchored on floor 1, but the members above live on floor 2 - so floor 2 wants a satellite.
    checkpoint.floor_id = 1;
    checkpoint.layer = null;
    checkpoint._satelliteLayerGroup = null;
    checkpoint.map = {
        leafletMap: {
            removeLayer: (layer) => removedLayers.push(layer),
        },
        mapObjectGroupManager: {
            getByName: (name) => (name === 'enemy' ? enemyMapObjectGroup : mapObjectGroup),
        },
    };

    global.getState = () => ({
        getCurrentFloor: () => ({id: currentFloorId}),
    });

    global.L.layerGroup = () => ({
        addTo: function () {
            addedLayers.push(this);

            return this;
        },
    });
    global.L.marker = () => ({
        addTo: (layerGroup) => layerGroup,
    });

    return {checkpoint, removedLayers, addedLayers};
}

describe('EnemyForcesCheckpoint._refreshSatellitePill', () => {
    it('refreshSatellitePill_givenVisibleMapObjectGroup_drawsTheSatellite', () => {
        // Arrange
        const {checkpoint} = createCheckpoint({groupIsShown: true});

        // Act
        checkpoint._refreshSatellitePill('<div>10%</div>');

        // Assert
        expect(checkpoint._satelliteLayerGroup).not.toBeNull();
    });

    it('refreshSatellitePill_givenHiddenMapObjectGroup_drawsNothing', () => {
        // Arrange
        const {checkpoint} = createCheckpoint({groupIsShown: false});

        // Act
        checkpoint._refreshSatellitePill('<div>10%</div>');

        // Assert
        expect(checkpoint._satelliteLayerGroup).toBeNull();
    });

    it('refreshSatellitePill_givenMapObjectGroupHiddenAfterBeingShown_removesTheExistingSatellite', () => {
        // Arrange
        const shown = createCheckpoint({groupIsShown: true});
        shown.checkpoint._refreshSatellitePill('<div>10%</div>');
        const drawnSatellite = shown.checkpoint._satelliteLayerGroup;

        const hidden = createCheckpoint({groupIsShown: false});
        hidden.checkpoint._satelliteLayerGroup = drawnSatellite;

        // Act
        hidden.checkpoint._refreshSatellitePill('<div>10%</div>');

        // Assert
        // A floor switch while the group is hidden must not leave (or re-create) an orphan pill.
        expect(hidden.checkpoint._satelliteLayerGroup).toBeNull();
        expect(hidden.removedLayers).toContain(drawnSatellite);
    });

    it('refreshSatellitePill_givenAnchorFloorIsTheCurrentFloor_drawsNothing', () => {
        // Arrange
        // The checkpoint's own marker already covers its anchor floor.
        const {checkpoint} = createCheckpoint({groupIsShown: true, currentFloorId: 1});

        // Act
        checkpoint._refreshSatellitePill('<div>10%</div>');

        // Assert
        expect(checkpoint._satelliteLayerGroup).toBeNull();
    });
});

/**
 * Builds a checkpoint with just the collaborators the pill and the tooltip reach for.
 *
 * The two number style settings are deliberately given OPPOSING values, so a test can tell which of
 * the two was consulted: a checkpoint is a group total (like a pull), so it must follow "Pull number
 * style" and never "Enemy number style" - which is about the numbers drawn on individual enemies.
 *
 * @param options {{killZonesNumberStyle: String, mapNumberStyle: String}}
 */
function createCheckpointForNumberStyle({killZonesNumberStyle, mapNumberStyle}) {
    const enemyMapObjectGroup = {
        objects: {
            1: {id: 1, enemy_forces_checkpoint_id: 55, floor_id: 1, source_floor_id: null},
        },
    };

    const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
    checkpoint.id = 55;
    checkpoint.floor_id = 1;
    checkpoint.name = 'Corridor';
    checkpoint.layer = {bindTooltip: (text) => (checkpoint._boundTooltipText = text)};
    checkpoint.map = {
        options: {noUI: false},
        mapObjectGroupManager: {
            getByName: (name) => (name === 'enemy' ? enemyMapObjectGroup : false),
        },
        enemyForcesManager: {
            getEnemyForcesForEnemies: () => 20,
            getEnemyForcesRequired: () => 100,
        },
    };

    global.getState = () => ({
        getCurrentFloor: () => ({id: 1}),
        getKillZonesNumberStyle: () => killZonesNumberStyle,
        getMapNumberStyle: () => mapNumberStyle,
    });

    // Echo the key back with its parameters, so a test can assert both which branch ran and the value.
    global.lang = {get: (key, params = {}) => `${key}:${JSON.stringify(params)}`};
    global.getFormattedPercentage = (amount, total) => `${Math.round((amount / total) * 100)}%`;
    global.Handlebars = {templates: {map_enemy_forces_checkpoint_pill: ({value}) => `<div>${value}</div>`}};

    return checkpoint;
}

describe('EnemyForcesCheckpoint number style', () => {
    it('getPillHtml_givenPullNumberStyleIsEnemyForces_rendersRawEnemyForces', () => {
        // Arrange
        const checkpoint = createCheckpointForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
            mapNumberStyle: NUMBER_STYLE_PERCENTAGE,
        });

        // Act
        const html = checkpoint._getPillHtml();

        // Assert
        // 100 required - 20 held by this checkpoint = 80 needed before entering.
        expect(html).toContain('js.enemy_forces_checkpoint_pill_enemy_forces');
        expect(html).toContain('80');
    });

    it('getPillHtml_givenPullNumberStyleIsPercentage_rendersAPercentage', () => {
        // Arrange
        const checkpoint = createCheckpointForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_PERCENTAGE,
            mapNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
        });

        // Act
        const html = checkpoint._getPillHtml();

        // Assert
        // The opposing "Enemy number style" value must not be able to reach the pill.
        expect(html).toContain('js.enemy_forces_checkpoint_pill_percentage');
        expect(html).toContain('80%');
    });

    it('bindTooltip_givenPullNumberStyleIsEnemyForces_rendersRawEnemyForces', () => {
        // Arrange
        const checkpoint = createCheckpointForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
            mapNumberStyle: NUMBER_STYLE_PERCENTAGE,
        });

        // Act
        checkpoint.bindTooltip();

        // Assert
        expect(checkpoint._boundTooltipText).toContain('js.enemy_forces_checkpoint_tooltip_enemy_forces');
    });

    it('bindTooltip_givenPullNumberStyleIsPercentage_rendersAPercentage', () => {
        // Arrange
        const checkpoint = createCheckpointForNumberStyle({
            killZonesNumberStyle: NUMBER_STYLE_PERCENTAGE,
            mapNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
        });

        // Act
        checkpoint.bindTooltip();

        // Assert
        expect(checkpoint._boundTooltipText).toContain('js.enemy_forces_checkpoint_tooltip_percentage');
        expect(checkpoint._boundTooltipText).toContain('20%');
    });
});

describe('EnemyForcesCheckpoint.isMapObjectGroupShown', () => {
    it('isMapObjectGroupShown_givenNoMapObjectGroup_returnsTrue', () => {
        // Arrange
        const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
        checkpoint.map = {mapObjectGroupManager: {getByName: () => false}};

        // Act
        const result = checkpoint.isMapObjectGroupShown();

        // Assert
        // No group to obey - never suppress on that basis.
        expect(result).toBe(true);
    });
});
