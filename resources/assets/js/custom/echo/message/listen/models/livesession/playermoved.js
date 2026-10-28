/**
 * @typedef {Object} PlayerMovedData
 * @property {MessageCoordinates} coordinates
 */

/**
 * @property {string} player_guid
 * @property {string} character_name
 * @property {Number|null} class_id
 * @property {Number|null} specialization_id
 * @property {String|null} specialization_icon_url
 * @property {string} model_class
 * @property {Object} model
 * @property {PlayerMovedData} model_data
 */
class PlayerMovedMessage extends ModelMessage {
    static getName() {
        return 'player-moved';
    }
}
