let LeafletIconUserMousePositionUnknown = L.divIcon({
    html: '<i class="fas fa-icons"></i>',
    iconSize: [32, 32],
    className: 'map_icon marker_div_icon_font_awesome map_icon_div_icon_unknown'
});

let LeafletIconUserMousePositionMarker = L.Marker.extend({
    options: {
        icon: LeafletIconUserMousePositionUnknown
    }
});

/**
 * @param echoUser {EchoUser}
 */
function getUserMousePositionIcon(echoUser) {
    let template = Handlebars.templates['map_user_mouse_location_visual_template'];

    let width = c.map.mapicon.calculateSize(32);
    let height = c.map.mapicon.calculateSize(32);

    let handlebarsData = $.extend({}, echoUser, {
        width: width,
        height: height
    });

    return L.divIcon({
        html: template(handlebarsData),
        iconSize: [width, height],
        tooltipAnchor: [0, -(height / 2)],
        popupAnchor: [0, -(height / 2)],
        className: 'map_icon'
    });
}


L.Draw.UserMousePosition = L.Draw.Marker.extend({
    statics: {
        TYPE: MAP_OBJECT_GROUP_USER_MOUSE_LOCATION
    },
    options: {
        icon: LeafletIconUserMousePositionUnknown
    },
    initialize: function (map, options) {
        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.UserMousePosition.TYPE;
        L.Draw.Feature.prototype.initialize.call(this, map, options);
    }
});

class UserMousePosition extends MapIcon {
    constructor(map, layer) {
        super(map, layer);
    }
}