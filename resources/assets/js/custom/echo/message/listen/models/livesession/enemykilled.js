/**
 * @typedef {Object} EnemyKilledData
 * @property {MessageCoordinates} coordinates
 */

/**
 * @property {string} model_class
 * @property {Object} model
 * @property {EnemyKilledData} model_data
 */
class EnemyKilledMessage extends ModelMessage {
    static getName() {
        return 'enemy-killed';
    }
}
