
/**
 * @property {String} model_class
 * @property {Object} model
 * @property {string} color
 */
class UserColorChangedMessage extends ModelMessage {
    static getName() {
        return 'user-color-changed';
    }
}
