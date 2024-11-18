/**
 @property {Number} model_id
 @property {String} model_class
 */
class PathDeletedMessage extends Message {
    static getName() {
        return 'path-deleted';
    }
}
