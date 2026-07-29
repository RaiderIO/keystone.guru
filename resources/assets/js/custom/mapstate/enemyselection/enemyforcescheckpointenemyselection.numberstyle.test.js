// The snackbar shown while an admin assigns enemies to a checkpoint says how much that checkpoint
// currently holds. Like the checkpoint's own pill and tooltip, that figure is a group total, so it
// follows the "Pull number style" setting - not "Enemy number style", which governs the numbers drawn
// on individual enemies.
//
// Follows the global-script recipe from models/killzone.test.js: stub the collaborators the class
// bodies touch at LOAD time, then require the source.

global.Signalable = class Signalable {
};
global.LeafletKillZoneIconEditMode = {};
global.NUMBER_STYLE_ENEMY_FORCES = 'enemy_forces';
global.NUMBER_STYLE_PERCENTAGE = 'percentage';

// The real inheritance chain - the subclass references its base as a bare global (they are
// concatenated into one bundle).
const {MapState} = require('../mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('../mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;
const {EnemySelection} = require('./enemyselection');
global.EnemySelection = EnemySelection;

const {EnemyForcesCheckpointEnemySelection} = require('./enemyforcescheckpointenemyselection');

/**
 * Runs `_refreshSnackbar()` against a hand-rolled `this`, so no constructor (and none of its snackbar
 * / Leaflet wiring) has to run, and returns the text written into each snackbar element by selector.
 *
 * The two number style settings are deliberately given OPPOSING values, so a test can tell which of
 * the two was consulted.
 *
 * @param options {{killZonesNumberStyle: String, mapNumberStyle: String}}
 * @returns {Object<String, String>}
 */
function refreshSnackbar({killZonesNumberStyle, mapNumberStyle}) {
    const writtenText = {};

    const checkpoint = {
        name: 'Corridor',
        getEnemies: () => [{id: 1}, {id: 2}],
        getEnemyForces: () => 20,
    };

    global.getState = () => ({
        getKillZonesNumberStyle: () => killZonesNumberStyle,
        getMapNumberStyle: () => mapNumberStyle,
    });

    // Echo the key back with its parameters, so a test can assert both which branch ran and the value.
    global.lang = {get: (key, params = {}) => `${key}:${JSON.stringify(params)}`};
    global.getFormattedPercentage = (amount, total) => `${Math.round((amount / total) * 100)}%`;
    global.$ = (selector) => ({text: (text) => (writtenText[selector] = text)});

    EnemyForcesCheckpointEnemySelection.prototype._refreshSnackbar.call({
        getMapObject: () => checkpoint,
        map: {enemyForcesManager: {getEnemyForcesRequired: () => 100}},
    });

    return writtenText;
}

describe('EnemyForcesCheckpointEnemySelection._refreshSnackbar number style', () => {
    it('refreshSnackbar_givenPullNumberStyleIsEnemyForces_rendersRawEnemyForces', () => {
        // Arrange / Act
        const writtenText = refreshSnackbar({
            killZonesNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
            mapNumberStyle: NUMBER_STYLE_PERCENTAGE,
        });

        // Assert
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_summary'])
            .toContain('js.enemy_forces_checkpoint_snackbar_enemy_forces');
    });

    it('refreshSnackbar_givenPullNumberStyleIsPercentage_rendersAPercentage', () => {
        // Arrange / Act
        const writtenText = refreshSnackbar({
            killZonesNumberStyle: NUMBER_STYLE_PERCENTAGE,
            mapNumberStyle: NUMBER_STYLE_ENEMY_FORCES,
        });

        // Assert
        // The opposing "Enemy number style" value must not be able to reach the snackbar.
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_summary'])
            .toContain('js.enemy_forces_checkpoint_snackbar_percentage');
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_summary']).toContain('20%');
    });
});
