class MapObjectGroupControls {
    constructor(map) {
        console.assert(this instanceof MapObjectGroupControls, this, 'this is not MapObjectGroupControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;
        this._mapControl = null;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let source = $("#map_controls_template").html();
                let template = handlebars.compile(source);

                let data = {
                    mapobjectgroups: []
                };
                for(let i in self.map.mapObjectGroups){
                    let group = self.map.mapObjectGroups[i];
                    data.mapobjectgroups.push({
                        name: group.name,
                        title: group.title,
                        fa_class: group.fa_class,
                    });
                }

                // Build the status bar from the template
                self.statusbar = $(template(data));


                for(let i in self.map.mapObjectGroups){
                    let group = self.map.mapObjectGroups[i];
                    self.statusbar.find('#map_controls_hide_' + group.name).bind('click', function (e) {
                        let checkbox = $(self.statusbar).find('#map_controls_hide_' + group.name + '_checkbox');
                        let shown = !group.isShown();

                        group.setVisibility(shown);

                        if (shown) {
                            checkbox.removeClass('fa-square');
                            checkbox.addClass('fa-check-square');
                        } else {
                            checkbox.removeClass('fa-check-square');
                            checkbox.addClass('fa-square');
                        }

                        e.preventDefault();
                        return false;
                    });
                }

                // self.statusbar.find('#map_controls_hide_enemy_packs').bind('click', function (e) {
                //     let checkbox = $(self.statusbar).find('#map_controls_hide_enemy_packs_checkbox');
                //     let shown = !self.map.isEnemyPacksShown();
                //
                //     self.map.setEnemyPacksVisibility(shown);
                //
                //     if (shown) {
                //         checkbox.removeClass('fa-square');
                //         checkbox.addClass('fa-check-square');
                //     } else {
                //         checkbox.removeClass('fa-check-square');
                //         checkbox.addClass('fa-square');
                //     }
                //
                //     e.preventDefault();
                //     return false;
                // });

                self.statusbar = self.statusbar[0];

                return self.statusbar;
            }
        };
    }

    /**
     * Cleans up the MapControl; removing it from the current LeafletMap.
     */
    cleanup() {
        console.assert(this instanceof MapObjectGroupControls, this, 'this is not MapObjectGroupControls');

        if (typeof this._mapControl === 'object') {
            this.map.leafletMap.removeControl(this._mapControl);
        }
    }

    /**
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof MapObjectGroupControls, this, 'this is not MapObjectGroupControls');

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend(this.mapControlOptions);

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        this._mapControl = L.control.statusbar({position: 'topright'}).addTo(this.map.leafletMap);
    }
}
