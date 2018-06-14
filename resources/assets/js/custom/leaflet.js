var mapObj;
var _currentMapName;
var _currentFloor;

var _mapInitialized = false;
var _mapTileLayer;

/**
 * DOM object of the status bar. NOT a jQuery object!
 */
var _statusbar;

var _mapInitializedListeners = [];

/**
 * Register a listener to be called when the map is initialized so you can
 * @param fn
 */
function onMapInitialized(fn) {
    _mapInitializedListeners.push(fn);
}

function initLeafletMap() {
    if (!_mapInitialized) {
        // Create the map object
        mapObj = L.map('map', {
            minZoom: 1,
            maxZoom: 4,
            // We use a custom draw control, so don't use this
            // drawControl: true,
            // Simple 1:1 coordinates to meters, don't use Mercator or anything like that
            crs: L.CRS.Simple,
            // Context menu when right clicking stuff
            contextmenu: true
        });


        // Playground


        // Code for the statusbar
        // L.Control.Statusbar = L.Control.extend({
        //     onAdd: function (map) {
        //         _statusbar = $("<p>")
        //             .css('font-size', '20px')
        //             .css('font-weight', 'bold')
        //             .css('color', '#5DADE2')
        //             .html('Test status bar');
        //         _statusbar = _statusbar[0];
        //
        //         return _statusbar;
        //     }
        // });
        //
        // L.control.statusbar = function (opts) {
        //     return new L.Control.Statusbar(opts);
        // };
        //
        // L.control.statusbar({position: 'topright'}).addTo(mapObj);
    } else {
        mapObj.removeLayer(_mapTileLayer);
    }

    mapObj.setView([-128, 128], 0);

    _mapTileLayer = L.tileLayer('https://mpplnr.wofje.nl/images/tiles/' + _currentMapName + '/' + _currentFloor + '/{z}/{x}_{y}.png', {
        maxZoom: 4,
        attribution: '',
        tileSize: L.point(384, 256),
        noWrap: true,
        continuousWorld: true
    }).addTo(mapObj);

    // Notify everyone the map is initialized and ready to use
    if (!_mapInitialized) {
        $.each(_mapInitializedListeners, function (listener) {
            listener(mapObj);
        });
    }
    _mapInitialized = true;
}

function setLeafletStatusBarHtml(html) {
    if (typeof _statusbar !== 'undefined') {
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