/**
 * @typedef {Object} ArrowChangedData
 * @property {MessageCoordinatesPolyline} coordinates
 */

/**
 * @property {String} model_class
 * @property {Object} model
 * @property {ArrowChangedData} model_data
 */
class ArrowChangedMessage extends ModelMessage {
    static getName() {
        return 'arrow-changed';
    }
}
