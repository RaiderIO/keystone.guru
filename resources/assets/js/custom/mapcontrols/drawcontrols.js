$(function () {
    L.DrawToolbar.prototype.getModeHandlers = function (map) {
        return [
            {
                enabled: this.options.path,
                handler: new L.Draw.Path(map, this.options.path),
                title: this.options.path.title
            }, {
                enabled: this.options.killzone,
                handler: new L.Draw.KillZone(map, this.options.killzone),
                title: this.options.killzone.title
            }, {
                enabled: this.options.mapcomment,
                handler: new L.Draw.MapComment(map, this.options.mapcomment),
                title: this.options.mapcomment.title
            }, {
                enabled: this.options.brushline,
                handler: new L.Draw.Brushline(map, this.options.brushline),
                title: this.options.brushline.title
            },
            // {
            //     enabled: this.options.line,
            //     handler: new L.Draw.Line(map, this.options.line),
            //     title: this.options.line.title
            // },
            {
                enabled: this.options.enemypack,
                handler: new L.Draw.EnemyPack(map, this.options.enemypack),
                title: this.options.enemypack.title
            }, {
                enabled: this.options.enemy,
                handler: new L.Draw.Enemy(map, this.options.enemy),
                title: this.options.enemy.title
            }, {
                enabled: this.options.enemypatrol,
                handler: new L.Draw.EnemyPatrol(map, this.options.enemypatrol),
                title: this.options.enemypatrol.title
            }, {
                enabled: this.options.dungeonstartmarker,
                handler: new L.Draw.DungeonStartMarker(map, this.options.dungeonstartmarker),
                title: this.options.dungeonstartmarker.title
            }, {
                enabled: this.options.dungeonfloorswitchmarker,
                handler: new L.Draw.DungeonFloorSwitchMarker(map, this.options.dungeonfloorswitchmarker),
                title: this.options.dungeonfloorswitchmarker.title
            }
        ];
    };

    // Add some new strings to the draw controls
    // https://github.com/Leaflet/Leaflet.draw#customizing-language-and-text-in-leafletdraw
    $.extend(L.drawLocal.draw.handlers, {
        route: {
            tooltip: {
                start: 'Click to start drawing path.',
                cont: 'Click to continue drawing path.',
                end: 'Click the \'Finish\' button on the toolbar to complete your path.'
            }
        },
        brushline: {
            tooltip: {
                start: 'Click to start drawing line.',
                cont: 'Click and drag to continue drawing line.',
                end: 'Continue clicking/dragging, when done, press the \'Finish\' button on the toolbar to complete your line.'
            }
        }
    });
});

class DrawControls extends MapControl {
    constructor(map, editableItemsLayer) {
        super(map);
        console.assert(this instanceof DrawControls, this, 'this is not DrawControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this._mapControl = null;
        this.editableItemsLayer = editableItemsLayer;
        this.drawControlOptions = {};

        // Add a created item to the list of drawn items
        this.map.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            let layer = event.layer;
            self.editableItemsLayer.addLayer(layer);
        });

        this.map.hotkeys.attach('r', 'leaflet-draw-draw-path');
        this.map.hotkeys.attach('c', 'leaflet-draw-edit-edit');
        this.map.hotkeys.attach('d', 'leaflet-draw-edit-remove');

        // Handle changes
        $('#edit_route_freedraw_options_color').bind('change', function (changeEvent) {
            self.addControl();
            c.map.brushline.defaultColor = $(this).val();
            c.map.path.defaultColor = $(this).val();
            c.map.killzone.polylineOptions.color = $(this).val();
            c.map.killzone.polygonOptions.color = $(this).val();
        });
        $('#edit_route_freedraw_options_weight').bind('change', function (changeEvent) {
            self.addControl();
            c.map.brushline.defaultWeight = $('#edit_route_freedraw_options_weight :selected').val();
        });
    }

    /**
     * Gets the newly generated options for the drawing control.
     * @returns object
     * @protected
     */
    _getDrawControlOptions() {
        console.assert(this instanceof DrawControls, this, 'this was not a DrawControls');

        let color = $('#edit_route_freedraw_options_color').val();
        let weight = $('#edit_route_freedraw_options_weight').val();

        if( typeof color === 'undefined' ){
            color = '#9DFF56';
        }

        if( typeof weight === 'undefined' ){
            weight = 3;
        }

        return {
            position: 'topleft',
            draw: {
                path: {
                    shapeOptions: {
                        color: color,
                        weight: weight,
                        opacity: 1.0
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-route',
                    title: 'Draw a route'
                },
                killzone: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-bullseye',
                    title: 'Draw a killzone'
                },
                mapcomment: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-comment',
                    title: 'Create a map comment'
                },
                brushline: {
                    shapeOptions: {
                        color: color,
                        weight: weight,
                        opacity: 1.0
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-paint-brush',
                    title: 'Draw a line using a brush'
                },
                line: {
                    shapeOptions: {
                        color: color,
                        weight: weight,
                        opacity: 1.0
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-pencil-ruler',
                    title: 'Draw a line'
                },
                enemypack: false,
                enemypatrol: false,
                enemy: false,
                dungeonstartmarker: false,
                dungeonfloorswitchmarker: false,
            },
            edit: {
                featureGroup: this.editableItemsLayer, //REQUIRED!!
                remove: true
            }
        }
    }

    /**
     * Adds the control to the map.
     */
    addControl() {
        console.assert(this instanceof DrawControls, this, 'this was not a DrawControls');

        // Remove if exists
        if (this._mapControl !== null) {
            this.map.leafletMap.removeControl(this._mapControl);
        }

        // Add the control to the map
        this.drawControlOptions = this._getDrawControlOptions(this.editableItemsLayer);
        this._mapControl = new L.Control.Draw(this.drawControlOptions);
        this.map.leafletMap.addControl(this._mapControl);

        // If the option wants, render it with a font-awesome icon instead.
        // Surely there must be a better way for this but whatever, this works..
        for (let optionName in this.drawControlOptions.draw) {
            if (this.drawControlOptions.draw.hasOwnProperty(optionName)) {
                let option = this.drawControlOptions.draw[optionName];
                if (option.hasOwnProperty('faClass')) {
                    // Set the FA icon and remove the background image that was initially there
                    $(".leaflet-draw-draw-" + optionName)
                        .html("<i class='fas " + option.faClass + "'></i>")
                        .css('background-image', 'none');
                }
            }
        }

        // Add the leaflet draw control to the sidebar
        let container = this._mapControl.getContainer();
        let $targetContainer = $('#edit_route_draw_container');
        $targetContainer.append(container);

        // Now that the container is added, modify it to look the way we want it to
        let $container = $(container);
        // remove all classes
        $container.removeClass();
        $container.addClass('container');

        $.each($container.children(), function (i, child) {
            let $child = $(child);

            // Clear of classes, add a row
            let $parent = $child.removeClass().addClass('row');

            // Add columns to the buttons
            let $buttons = $parent.find('a');
            $buttons.addClass('col draw_icon mt-2');
            $buttons.attr('data-toggle', 'tooltip');

            // The buttons have a parent that shouldn't be there; strip the children from that bad parent!
            $parent.append($buttons);
        });

        // Custom controls
        // let $customDrawControls = $($container.children()[0]);


        // Edit the built-in draw controls
        let $editDrawControls = $($container.children()[1]);

        // Add some padding for the above custom controls
        $editDrawControls.addClass('mt-2');

        // Add custom content for the edit and remove buttons
        let $buttons = $editDrawControls.find('a');
        $buttons.attr('data-toggle', 'tooltip');
        $($buttons[0]).html("<i class='fas fa-edit'></i>");
        $($buttons[1]).html("<i class='fas fa-trash'></i>");

        refreshTooltips();
    }

    cleanup() {
        super.cleanup();

        // this.map.leafletMap.off(L.Draw.Event.CREATED);
    }
}