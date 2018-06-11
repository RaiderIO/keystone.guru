var mapObj;
var _currentMapName;
var _currentFloor;

var _mapInitialized = false;
var _mapTileLayer;

/**
 * DOM object of the status bar. NOT a jQuery object!
 */
var _statusbar;

function initLeafletMap() {
    if (!_mapInitialized) {
        mapObj = L.map('map', {
            minZoom: 1,
            maxZoom: 4,
            maxBoundsViscosity: 0.5,
            editable: true
        });

        L.Control.Statusbar = L.Control.extend({
            onAdd: function (map) {
                _statusbar = $("<p>")
                    .css('font-size', '20px')
                    .css('font-weight', 'bold')
                    .css('color', '#5DADE2')
                    .html('Test status bar');
                _statusbar = _statusbar[0];

                return _statusbar;
            }
        });

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        L.control.statusbar({position: 'topright'}).addTo(mapObj);
    } else {
        mapObj.removeLayer(_mapTileLayer);
    }

    mapObj.setView([0, 0], 0);

    _mapTileLayer = L.tileLayer('https://mpplnr.wofje.nl/images/tiles/' + _currentMapName + '/' + _currentFloor + '/{z}/{x}_{y}.png', {
        maxZoom: 4,
        attribution: '',
        tileSize: L.point(384, 256),
        noWrap: true,
        continuousWorld: true
    }).addTo(mapObj);

    var polyline = L.polyline([[43.1, 1.2], [43.2, 1.3],[43.3, 1.2]]).addTo(mapObj);
    polyline.enableEdit();

    _mapInitialized = true;
}

function setLeafletStatusBarHtml(html){
    if( typeof _statusbar !== 'undefined' ){
        $(_statusbar).html(html);
    }
}

// Just a wrapper for initMap().
function refreshLeafletMap() {
    initLeafletMap();
}

function setCurrentMapName(name, floor) {
    console.log(">> setCurrentMapName ", name, floor);
    setLeafletStatusBarHtml(name);
    _currentMapName = name;
    _currentFloor = floor;
    console.log("OK setCurrentMapName ", name, floor);
}