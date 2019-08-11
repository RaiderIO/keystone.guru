class MapObjectGroupControls extends MapControl {
    constructor(map) {
        super(map);

        let self = this;

        this.map = map;

        this.mapControlOptions = {
            onAdd: function (leafletMap) {
                let template = Handlebars.templates['map_controls_template'];

                let data = {
                    mapobjectgroups: []
                };

                let mapObjectGroups = self.map.mapObjectGroupManager.mapObjectGroups;

                for(let i in mapObjectGroups){
                    let group = mapObjectGroups[i];
                    data.mapobjectgroups.push({
                        name: group.name,
                        title: group.title,
                        fa_class: group.fa_class,
                    });
                }

                // Build the status bar from the template
                self.statusbar = $(template(data));


                for (let i in mapObjectGroups) {
                    let group = mapObjectGroups[i];
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
     * Adds the Control to the current LeafletMap
     */
    addControl() {
        console.assert(this instanceof MapObjectGroupControls, 'this is not MapObjectGroupControls', this);

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend(this.mapControlOptions);

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        this._mapControl = L.control.statusbar({position: 'topright'}).addTo(this.map.leafletMap);
    }
}
