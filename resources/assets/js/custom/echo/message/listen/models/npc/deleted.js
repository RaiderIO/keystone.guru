/**
 @property {Number} model_id
 @property {String} model_class
 */
class NpcDeletedMessage extends Message {
    static getName() {
        return 'npc-deleted';
    }
}
