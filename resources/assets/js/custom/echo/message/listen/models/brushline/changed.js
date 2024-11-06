/**
 * @typedef {Object} BrushlineChangedData
 * @property {MessageCoordinatesPolyline} coordinates
 */

/**
 * @property {String} model_class
 * @property {Object} model
 * @property {BrushlineChangedData} model_data
 */
class BrushlineChangedMessage extends ModelMessage {
    static getName() {
        return 'brushline-changed';
    }
}
