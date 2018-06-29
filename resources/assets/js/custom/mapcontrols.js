class MapControls {
    constructor(map){
        console.assert(this instanceof MapControls, this, 'this is not MapControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;

        // Code for the statusbar
        L.Control.Statusbar = L.Control.extend({
            onAdd: function (map) {
                self.statusbar = $($("#map_controls_template").html());

                self.statusbar.find('#map_controls_hide_enemies').bind('click', function(e){
                    let checkbox = $(self.statusbar).find('#map_controls_hide_enemies_checkbox');
                    let shown = !self.map.isEnemiesShown();

                    self.map.setEnemiesVisibility(shown);

                    if(shown){
                        checkbox.removeClass('fa-square');
                        checkbox.addClass('fa-check-square');
                    } else {
                        checkbox.removeClass('fa-check-square');
                        checkbox.addClass('fa-square');
                    }

                    e.preventDefault();
                    return false;
                });

                self.statusbar.find('#map_controls_hide_enemy_packs').bind('click', function(e){
                    let checkbox = $(self.statusbar).find('#map_controls_hide_enemy_packs_checkbox');
                    let shown = !self.map.isEnemyPacksShown();

                    self.map.setEnemyPacksVisibility(shown);

                    if(shown){
                        checkbox.removeClass('fa-square');
                        checkbox.addClass('fa-check-square');
                    } else {
                        checkbox.removeClass('fa-check-square');
                        checkbox.addClass('fa-square');
                    }

                    e.preventDefault();
                    return false;
                });

                self.statusbar = self.statusbar[0];

                return self.statusbar;
            }
        });

        L.control.statusbar = function (opts) {
            return new L.Control.Statusbar(opts);
        };

        L.control.statusbar({position: 'topright'}).addTo(this.map.leafletMap);
    }
}
