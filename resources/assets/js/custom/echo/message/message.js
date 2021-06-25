/**
 * @property {Object} user
 * @property {Number} floor_id
 * @property {string} __name
 */
class Message extends Signalable {

    /**
     *
     * @param props Object
     */
    constructor(props) {
        super();

        // Default values
        this.user = {
            id: getState().getMapContext().getUserId()
        };
        this.floor_id = getState().getCurrentFloor().id;

        // This calls the implementing class' static getName() function
        this.__name = this.constructor.getName();


        // Assign the properties to the current object
        for (let index in props) {
            if (!index.startsWith('__') && props.hasOwnProperty(index)) {
                this[index] = props[index];
            }
        }
    }

    /**
     * @returns {Object}
     */
    toObject() {
        let result = {};

        for (let index in this) {
            // Include double underscores, but exclude single underscores (private properties)
            if (this.hasOwnProperty(index) && (index.startsWith('__') || !index.startsWith('_'))) {
                result[index] = this[index];
            }
        }

        return result;
    }
}