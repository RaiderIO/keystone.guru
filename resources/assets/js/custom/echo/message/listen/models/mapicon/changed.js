/**
 * @typedef {Object} MapIconChangedData
 * @property {MessageCoordinates} coordinates
 */

/**
 * @property {String} model_class
 * @property {Object} model
 * @property {MapIconChangedData} model_data
 */
class MapIconChangedMessage extends ModelMessage {
    static getName() {
        return 'mapicon-changed';
    }
}
