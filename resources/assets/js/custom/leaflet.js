var mapObj;
var _currentMapName;
var _currentFloor;

function initMap(){
    mapObj = L.map('map', {
        minZoom: 1,
        maxZoom: 4
    });

    var positron = L.tileLayer('https://mpplnr.wofje.nl/images/tiles/' + _currentMapName + '/' + _currentFloor + '/{z}/{x}_{y}.png', {
        maxZoom: 4,
        attribution: '',
        tileSize: L.point(384, 256),
        noWrap: true
    }).addTo(mapObj);

    mapObj.setView([0, 0], 0);
}

function setCurrentMapName(name, floor) {
    _currentMapName = name;
    _currentFloor = floor;

    initMap();
}