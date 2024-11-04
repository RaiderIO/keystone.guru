/**
 * @property {String} model_class
 * @property {Object} model
 */
class NpcChangedMessage extends ModelMessage {
    static getName() {
        return 'npc-changed';
    }
}
