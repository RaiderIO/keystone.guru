var mapObj;
var _currentMapName;
var _currentFloor;

function initMap() {
    mapObj = new google.maps.Map(document.getElementById('map'), {
        center: {lat: 0, lng: 0},
        zoom: 1,
        streetViewControl: false,
        mapTypeControlOptions: {
            mapTypeIds: ['dungeonmap']
        }
    });

    var customMapType = new google.maps.ImageMapType({
        getTileUrl: function (coord, zoom) {
            console.log("getTileUrl", coord, zoom);
            var normalizedCoord = getNormalizedCoord(coord, zoom);
            if (!normalizedCoord) {
                return '/images/test/test_tile.png';
            }

            var result = _currentMapName + '/' + _currentFloor + '/' + zoom + '/' + normalizedCoord.x + '_' + normalizedCoord.y + '.png';
            console.log(normalizedCoord, result);
            return result;
        },
        tileSize: new google.maps.Size(384, 256),
        maxZoom: 4,
        minZoom: 1,
        radius: 1738000,
        name: 'dungeonmap'
    });

    mapObj.mapTypes.set('dungeonmap', customMapType);
    mapObj.setMapTypeId('dungeonmap');
}

// Normalizes the coords that tiles repeat across the x axis (horizontally)
// like the standard Google map tiles.
function getNormalizedCoord(coord, zoom) {
    var y = coord.y;
    var x = coord.x;

    // tile range in one direction range is dependent on zoom level
    // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
    var tileRange = 1 << zoom;

    // don't repeat across y-axis (vertically)
    if (y < 0 || y >= tileRange) {
        return null;
    }

    // repeat across x-axis
    if (x < 0 || x >= tileRange) {
        // x = (x % tileRange + tileRange) % tileRange;
        return null;
    }

    return {x: x, y: y};
}

function setCurrentMapName(name, floor) {
    console.log(name, floor);
    var oldMapName = _currentMapName;
    var oldFloor = _currentFloor;
    _currentMapName = name;
    _currentFloor = floor;
    if (typeof mapObj !== "undefined") {
        // Thank you https://stackoverflow.com/questions/25687831/refreshing-google-maps-api-v3-layers
        var tiles = $("#map").find("img");
        for (var i = 0; i < tiles.length; i++) {
            var src = $(tiles[i]).attr("src");

            // If not from google
            if (src.indexOf("gstatic") < 0) {
                console.log("Manually refreshing!");
                // Manually switch the tiles, refresh them
                var new_src = src.replace(oldMapName + "/" + oldFloor, _currentMapName + "/" + _currentFloor).split("?ts")[0] + '?ts=' + (new Date()).getTime();
                $(tiles[i]).attr("src", new_src);
            }
        }
    }
}