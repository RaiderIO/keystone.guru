var mapObj;
var _currentMapName;
var _currentFloor;

var _mapInitialized = false;
var _mapTileLayer;

function initMap() {
    if (!_mapInitialized) {
        mapObj = L.map('map', {
            minZoom: 1,
            maxZoom: 4
        });

        mapObj.setView([0, 0], 0);
    } else {
        mapObj.removeLayer(_mapTileLayer);
    }

    _mapTileLayer = L.tileLayer('https://mpplnr.wofje.nl/images/tiles/' + _currentMapName + '/' + _currentFloor + '/{z}/{x}_{y}.png', {
        maxZoom: 4,
        attribution: '',
        tileSize: L.point(384, 256),
        noWrap: true,
        continuousWorld: true
    }).addTo(mapObj);

    _mapInitialized = true;
}

function setCurrentMapName(name, floor) {
    console.log(">> setCurrentMapName ", name, floor);
    _currentMapName = name;
    _currentFloor = floor;
    // mapObj.re

    initMap();
}