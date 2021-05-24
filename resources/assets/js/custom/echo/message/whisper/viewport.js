/**
 * @property {L.latLng} center
 * @property {Number} zoom
 **/
class ViewPortMessage extends Message {
    static getName() {
        return 'viewport';
    }
}