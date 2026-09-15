// The snackbar shown while an admin assigns enemies to a checkpoint names which checkpoint is being
// edited. That name is stored as a translation key (e.g. "mapping.checkpoints.df.ruby_life_pools.
// ruby_overlook_name"), so it must be run through lang.get() before being written into the DOM -
// otherwise the admin sees the raw key instead of the translated checkpoint name.
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
 * @param name {String|null}
 * @returns {Object<String, String>}
 */
function refreshSnackbar(name) {
    const writtenText = {};

    const checkpoint = {
        name: name,
        getEnemies: () => [],
        getEnemyForces: () => 0,
    };

    global.getState = () => ({
        getKillZonesNumberStyle: () => NUMBER_STYLE_ENEMY_FORCES,
    });

    // Echo the key back so a test can tell whether lang.get() ran on it.
    global.lang = {get: (key, params = {}) => `translated:${key}:${JSON.stringify(params)}`};
    global.getFormattedPercentage = (amount, total) => `${Math.round((amount / total) * 100)}%`;
    global.$ = (selector) => ({text: (text) => (writtenText[selector] = text)});

    EnemyForcesCheckpointEnemySelection.prototype._refreshSnackbar.call({
        getMapObject: () => checkpoint,
        map: {enemyForcesManager: {getEnemyForcesRequired: () => 100}},
    });

    return writtenText;
}

describe('EnemyForcesCheckpointEnemySelection._refreshSnackbar name', () => {
    it('refreshSnackbar_givenNamedCheckpoint_translatesTheNameKeyInsteadOfShowingItRaw', () => {
        // Arrange / Act
        const writtenText = refreshSnackbar('mapping.checkpoints.df.ruby_life_pools.ruby_overlook_name');

        // Assert
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_name'])
            .toContain('translated:mapping.checkpoints.df.ruby_life_pools.ruby_overlook_name');
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_name'])
            .not.toContain('js.enemy_forces_checkpoint_unnamed_label');
    });

    it('refreshSnackbar_givenNullName_showsTheUnnamedLabel', () => {
        // Arrange / Act
        const writtenText = refreshSnackbar(null);

        // Assert
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_name'])
            .toContain('js.enemy_forces_checkpoint_unnamed_label');
    });

    it('refreshSnackbar_givenEmptyName_showsTheUnnamedLabel', () => {
        // Arrange / Act
        const writtenText = refreshSnackbar('');

        // Assert
        expect(writtenText['#map_enemy_forces_checkpoint_edit_snackbar_name'])
            .toContain('js.enemy_forces_checkpoint_unnamed_label');
    });
});
