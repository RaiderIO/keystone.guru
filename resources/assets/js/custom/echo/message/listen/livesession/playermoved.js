/**
 * @property {string} player_guid
 * @property {string} character_name
 * @property {Number} lat
 * @property {Number} lng
 * @property {Number} floor_id
 */
class PlayerMovedMessage extends Message {
    static getName() {
        return 'player-moved';
    }
}
