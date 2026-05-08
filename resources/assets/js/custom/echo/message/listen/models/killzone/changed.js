/**
 * @property {String} model_class
 * @property {Object} model
 * @property {Object} killzone_paths
 */
class KillZoneChangedMessage extends ModelMessage {
    static getName() {
        return 'killzone-changed';
    }
}
