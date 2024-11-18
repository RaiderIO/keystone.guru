/**
 * @typedef {Object} PathChangedData
 * @property {MessageCoordinatesPolyline} coordinates
 */

/**
 * @property {String} model_class
 * @property {Object} model
 * @property {PathChangedData} model_data
 */
class PathChangedMessage extends ModelMessage {
    static getName() {
        return 'path-changed';
    }
}
