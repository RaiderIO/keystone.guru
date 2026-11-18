/**
 * @property {Object[]} points
 */
class MousePositionMessage extends Message {
    static getName() {
        return 'mouse-position';
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = MousePositionMessage;
}
