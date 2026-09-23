// EnemyForcesCheckpoint delegates its rendering (pill icon, satellite pill, tooltip text) to
// EnemyForcesCheckpointVisual - see enemyvisuals/enemyforcescheckpointvisual.test.js for the
// rendering behavior itself. What's left here is the model's own data accessors, plus the thin
// lifecycle overrides (refreshPill/bindTooltip/cleanup) that only exist to delegate to the visual.
//
// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.VersionableMapObject = class VersionableMapObject {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
    }

    bindTooltip() {}

    cleanup() {}
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

// The constructor builds a real EnemyForcesCheckpointVisual, so this stub only needs to prove
// delegation happened - the visual's own behavior is covered in its own test file.
global.EnemyForcesCheckpointVisual = class EnemyForcesCheckpointVisual {
    constructor(map, checkpoint, layer) {
        this.map = map;
        this.checkpoint = checkpoint;
        this.layer = layer;
        this.refreshPillCalls = 0;
        this.bindTooltipCalls = 0;
        this.cleanupCalls = 0;
    }

    refreshPill() {
        this.refreshPillCalls++;
    }

    bindTooltip() {
        this.bindTooltipCalls++;
    }

    cleanup() {
        this.cleanupCalls++;
    }
};

const {EnemyForcesCheckpoint} = require('./enemyforcescheckpoint');
const {fakeMapObjectGroupManager} = require('#test/fixtures/mapObjectGroupManager');

describe('EnemyForcesCheckpoint rendering delegation', () => {
    /**
     * Builds a checkpoint on a bare prototype (Object.create), with just the collaborators the
     * lifecycle overrides reach for, plus a fresh fake visual to assert delegation onto.
     */
    function createCheckpoint() {
        const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
        checkpoint.map = {
            register: () => {},
            unregister: () => {},
            mapObjectGroupManager: fakeMapObjectGroupManager(() => null),
        };
        checkpoint.visual = new EnemyForcesCheckpointVisual(checkpoint.map, checkpoint, null);

        return checkpoint;
    }

    it('refreshPill_delegatesToTheVisual', () => {
        // Arrange
        const checkpoint = createCheckpoint();

        // Act
        checkpoint.refreshPill();

        // Assert
        expect(checkpoint.visual.refreshPillCalls).toBe(1);
    });

    it('bindTooltip_delegatesToTheVisual', () => {
        // Arrange
        const checkpoint = createCheckpoint();

        // Act
        checkpoint.bindTooltip();

        // Assert
        expect(checkpoint.visual.bindTooltipCalls).toBe(1);
    });

    it('cleanup_delegatesToTheVisual', () => {
        // Arrange
        const checkpoint = createCheckpoint();
        global.getState = () => ({
            unregister: () => {},
            getMapContext: () => ({unregister: () => {}}),
        });

        // Act
        checkpoint.cleanup();

        // Assert
        expect(checkpoint.visual.cleanupCalls).toBe(1);
    });
});

describe('EnemyForcesCheckpoint.isMapObjectGroupShown', () => {
    it('isMapObjectGroupShown_givenNoMapObjectGroup_returnsTrue', () => {
        // Arrange
        const checkpoint = Object.create(EnemyForcesCheckpoint.prototype);
        checkpoint.map = {mapObjectGroupManager: fakeMapObjectGroupManager(() => null)};

        // Act
        const result = checkpoint.isMapObjectGroupShown();

        // Assert
        // No group to obey - never suppress on that basis.
        expect(result).toBe(true);
    });
});
