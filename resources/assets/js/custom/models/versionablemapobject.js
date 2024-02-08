/**
 * @property {Number} mapping_version_id
 */
class VersionableMapObject extends MapObject {
    constructor(map, layer, options) {
        let existingRouteSuffix = typeof options.route_suffix !== 'undefined' ? options.route_suffix : options.name;
        let mappingVersionId = getState().getMapContext().getMappingVersion().id;

        let routeSuffix = typeof options.ignore_mapping_version_suffix !== 'undefined' && options.ignore_mapping_version_suffix ?
            existingRouteSuffix : `mappingVersion/${mappingVersionId}/${existingRouteSuffix}`

        super(map, layer, $.extend({}, options, {route_suffix: routeSuffix}));

        this.mapping_version_id = mappingVersionId;
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
