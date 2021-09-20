/**
 * @property {String} model_class
 * @property {Object} model
 */
class NpcChangedMessage extends Message {
    static getName() {
        return 'npc-changed';
    }
}
