/**
 * @property {Number} mapping_version_id
 */
class VersionableMapObject extends MapObject {
    constructor(map, layer, options) {
        super(map, layer, options);

        this.mapping_version_id = getState().getMapContext().getMappingVersion().id;
    }


    _getAttributes(force = false) {
        return super._getAttributes(force).concat([
            new Attribute({
                name: 'mapping_version_id',
                type: 'int',
                edit: false, // Not directly changeable by user
                default: null
            }),
        ]);
    }
}
