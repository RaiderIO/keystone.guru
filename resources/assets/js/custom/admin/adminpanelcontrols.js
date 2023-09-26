class AdminPanelControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_admin_panel_template'];

                // Build the status bar from the template
                self.domElement = $(template($.extend({}, getHandlebarsDefaultVariables())));

                self.domElement = self.domElement[0];

                return self.domElement;
            }
        };

        this.mappingRotationsDone = 0;

        this.mappingXScaleCorrection = 1;
        this.mappingYScaleCorrection = 1;

        this.mappingXCorrection = 0;
        this.mappingYCorrection = 0;

        this.scaleStep = 0.01;
        this.moveStep = 2;

        this.map.leafletMap.on('mousemove', function (mouseMoveEvent) {
            let lat = _.round(mouseMoveEvent.latlng.lat, 3);
            let lng = _.round(mouseMoveEvent.latlng.lng, 3);

            let mdtX = _.round(lng * 2.185, 3);
            let mdtY = _.round(lat * 2.185, 3);

            // Ingame coordinates
            let floor = getState().getCurrentFloor()
            let ingameMapSizeX = floor.ingame_max_x - floor.ingame_min_x;
            let ingameMapSizeY = floor.ingame_max_y - floor.ingame_min_y;

            let mapSizeLat = -256;
            let mapSizeLng = 384;

            let invertedLat = mapSizeLat - lat;
            let invertedLng = mapSizeLng - lng;

            let factorLat = (invertedLat / mapSizeLat);
            let factorLng = (invertedLng / mapSizeLng);

            let ingameX = _.round((ingameMapSizeX * factorLng) + floor.ingame_min_x, 3);
            let ingameY = _.round((ingameMapSizeY * factorLat) + floor.ingame_min_y, 3);

            $('#admin_panel_mouse_coordinates').html(
                `<span style="font-size: 16px">
                 lat/lng: ${lat}/${lng}<br>
                 MDT x/y: ${mdtX}/${mdtY}<br>
                 x/y: ${ingameX}/${ingameY}
                </span>`
            );
        });
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        // Code for the domElement
        L.Control.domElement = L.Control.extend(this.mapControlOptions);

        L.control.domElement = function (opts) {
            return new L.Control.domElement(opts);
        };

        this._mapControl = L.control.domElement({position: 'bottomright'}).addTo(this.map.leafletMap);

        $('#mapping_manipulation_tools_rotate_btn').bind('click', this._mappingRotate.bind(this));

        $('#mapping_manipulation_tools_scale_x_minus_btn').bind('click', this._mappingScaleXMinus.bind(this));
        $('#mapping_manipulation_tools_scale_x_plus_btn').bind('click', this._mappingScaleXPlus.bind(this));

        $('#mapping_manipulation_tools_scale_y_minus_btn').bind('click', this._mappingScaleYMinus.bind(this));
        $('#mapping_manipulation_tools_scale_y_plus_btn').bind('click', this._mappingScaleYPlus.bind(this));

        $('#mapping_manipulation_tools_move_x_minus_btn').bind('click', this._mappingMoveXMinus.bind(this));
        $('#mapping_manipulation_tools_move_x_plus_btn').bind('click', this._mappingMoveXPlus.bind(this));

        $('#mapping_manipulation_tools_move_y_minus_btn').bind('click', this._mappingMoveYMinus.bind(this));
        $('#mapping_manipulation_tools_move_y_plus_btn').bind('click', this._mappingMoveYPlus.bind(this));

        let ids = [
            'mapping_manipulation_tools_rotate_btn',

            'mapping_manipulation_tools_scale_x_minus_btn',
            'mapping_manipulation_tools_scale_x_plus_btn',

            'mapping_manipulation_tools_scale_y_minus_btn',
            'mapping_manipulation_tools_scale_y_plus_btn',

            'mapping_manipulation_tools_move_x_minus_btn',
            'mapping_manipulation_tools_move_x_plus_btn',

            'mapping_manipulation_tools_move_y_minus_btn',
            'mapping_manipulation_tools_move_y_plus_btn',
        ];

        for (let index in ids) {
            L.DomEvent.disableClickPropagation(L.DomUtil.get(ids[index]));
        }
    }

    cleanup() {
        super.cleanup();

        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
    }

    _getIngameXForLng() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        let floor = getState().getCurrentFloor()
        let ingameMapSizeX = floor.ingame_max_x - floor.ingame_min_x;

        let mapSizeLng = 384;

        return ingameMapSizeX / mapSizeLng;
    }

    _getIngameYForLat() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        let floor = getState().getCurrentFloor()
        let ingameMapSizeY = floor.ingame_max_y - floor.ingame_min_y;
        let mapSizeLat = -256;

        return ingameMapSizeY / mapSizeLat;
    }

    _getAllMapObjects() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        let allMapObjects = {};

        for (let index in this.map.mapObjectGroupManager.mapObjectGroups) {
            let mapObjectGroup = this.map.mapObjectGroupManager.mapObjectGroups[index];

            allMapObjects = $.extend(allMapObjects, mapObjectGroup.objects);
        }

        return Object.values(allMapObjects);
    }

    _forEachMapObject(fn) {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        $.each(this._getAllMapObjects(), function (key, mapObject) {
            if (typeof mapObject.layer !== 'undefined' && mapObject.layer !== null) {
                let latLng = mapObject.layer.getLatLng();
                mapObject.layer.setLatLng(
                    fn(latLng, mapObject)
                );
            }
        });
    }

    _mappingRotate() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);

        this.mappingRotationsDone++;

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lng, latLng.lat * -1);
        });

        this._logCorrectedBounds();
    }

    _mappingScaleXMinus() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        this.mappingXScaleCorrection *= (1 - self.scaleStep);

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat, latLng.lng *= (1 - self.scaleStep));
        });

        this._logCorrectedBounds();
    }

    _mappingScaleXPlus() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        this.mappingXScaleCorrection *= (1 + self.scaleStep);

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat, latLng.lng *= (1 + self.scaleStep));
        });

        this._logCorrectedBounds();
    }

    _mappingScaleYMinus() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        this.mappingYScaleCorrection *= (1 - self.scaleStep);

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat *= (1 - self.scaleStep), latLng.lng);
        });

        this._logCorrectedBounds();
    }

    _mappingScaleYPlus() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        this.mappingYScaleCorrection *= (1 + self.scaleStep);

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat *= (1 + self.scaleStep), latLng.lng);
        });

        this._logCorrectedBounds();
    }

    _mappingMoveXMinus(event) {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        var div = L.DomUtil.get(event.currentTarget.id);
        L.DomEvent.disableClickPropagation(div);

        let correctedStep = self.moveStep * self.mappingXScaleCorrection;
        this.mappingXCorrection -= correctedStep;

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat, latLng.lng - correctedStep);
        });

        this._logCorrectedBounds();
    }

    _mappingMoveXPlus(event) {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        let correctedStep = self.moveStep * self.mappingXScaleCorrection;
        this.mappingXCorrection += correctedStep;

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat, latLng.lng + correctedStep);
        });

        this._logCorrectedBounds();
    }

    _mappingMoveYMinus() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        let correctedStep = self.moveStep * self.mappingYScaleCorrection;
        this.mappingYCorrection -= correctedStep;

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat - correctedStep, latLng.lng);
        });

        this._logCorrectedBounds();
    }

    _mappingMoveYPlus() {
        console.assert(this instanceof AdminPanelControls, 'this is not AdminPanelControls', this);
        let self = this;

        let correctedStep = self.moveStep * self.mappingYScaleCorrection;
        this.mappingYCorrection += correctedStep;

        this._forEachMapObject(function (latLng, mapObject) {
            return L.latLng(latLng.lat + correctedStep, latLng.lng);
        });

        this._logCorrectedBounds();
    }

    _logCorrectedBounds() {
        let currentFloor = getState().getCurrentFloor();

        let correctedIngameMinX = currentFloor.ingame_min_x;
        let correctedIngameMaxX = currentFloor.ingame_max_x;
        let correctedIngameMinY = currentFloor.ingame_min_y;
        let correctedIngameMaxY = currentFloor.ingame_max_y;

        // for (let i = 0; i < this.mappingRotationsDone; i++) {
        //     let previousMinY = correctedIngameMinY;
        //     correctedIngameMinY = correctedIngameMinX * -1;
        //     correctedIngameMinX = previousMinY;
        //
        //     let previousMaxY = correctedIngameMaxY;
        //     correctedIngameMaxY = correctedIngameMaxX * -1;
        //     correctedIngameMaxX = previousMaxY;
        // }

        console.log(
            'ingame_min_x: ' + ((correctedIngameMinX * 0.6754587979816992) + (401.153094836317 * this._getIngameXForLng())),
            'ingame_max_x: ' + ((correctedIngameMaxX * 0.6754587979816992) + (401.153094836317 * this._getIngameXForLng())),
            'ingame_min_y: ' + ((correctedIngameMinY * 1.503752370924104) + (-273.0004745717513 * this._getIngameYForLat())),
            'ingame_max_y: ' + ((correctedIngameMaxY * 1.503752370924104) + (-273.0004745717513 * this._getIngameYForLat())),
        );
    }
}
