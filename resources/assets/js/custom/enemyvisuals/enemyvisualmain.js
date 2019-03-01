/**
 * Main visual icons only define an extra size.
 */

class EnemyVisualMain extends EnemyVisualIcon {
    constructor(enemyvisual) {
        super(enemyvisual);

        let self = this;

        // Listen to changes in the NPC to update the icon and re-draw the visual
        this.enemyvisual.enemy.register('enemy:set_npc', this, this._refreshNpc.bind(this));

        this.circleMenu = null;

        this.enemyvisual.register('enemyvisual:builtvisual', this, function () {

            let id = self.enemyvisual.enemy.id;
            // When the visual exists, bind a click method to it (to increase performance)
            $('#map_enemy_visual_' + id).bind('click', self._visualClicked.bind(self));
        });
    }

    _visualClicked() {
        console.assert(this instanceof EnemyVisualMain, this, 'this is not an EnemyVisualMain!');

        let self = this;
        let template = Handlebars.templates['map_enemy_raid_marker_template'];
        let id = self.enemyvisual.enemy.id;

        let data = {
            id: id
        };

        let $container = $('#map_enemy_visual_' + id);
        $container.append(template(data));

        if (self.circleMenu === null) {
            // Init circle menu and open it
            self.circleMenu = $('#map_enemy_raid_marker_radial_' + id).circleMenu({
                direction: 'full',
                step_in: 0,
                step_out: 0,
                trigger: 'click',
                transition_function: 'linear',
                circle_radius: 40,
                item_diameter: 16,
                speed: 200,
                open: function () {
                    self.enemyvisual.enemy.unbindTooltip();
                },
                close: function () {
                    self.enemyvisual.enemy.bindTooltip();

                    // Delete self
                    $('#map_enemy_raid_marker_radial_' + id).delay(500).queue(function () {
                        $(this).remove().dequeue();
                    });
                    self.circleMenu = null;

                    // Re-bind this function
                    $container.unbind('click');
                    $container.bind('click', self._visualClicked.bind(self));
                },
                select: function (evt, index) {
                    // Assign the selected raid marker
                    self.enemyvisual.enemy.assignRaidMarker($(index).data('name'));

                    // Delete self
                    $('#map_enemy_raid_marker_radial_' + id).delay(500).queue(function () {
                        $(this).remove().dequeue();
                    });
                    self.circleMenu = null;

                    // Re-bind this function
                    $container.unbind('click');
                    $container.bind('click', self._visualClicked.bind(self));
                }
            });

            self.circleMenu.circleMenu('open');
            // Unbind this function so we don't get repeat clicks
            $container.unbind('click');
            // Give the user a way to close the menu by clicking the enemy again
            $container.bind('click', function(){
                self.circleMenu.circleMenu('close');
            });
        }
    }

    /**
     * Must be overriden by implementing classes
     * @protected
     */
    _refreshNpc() {

    }

    getSize() {
        return {};
    }

    cleanup() {
        super.cleanup();
        console.assert(this instanceof EnemyVisualMain, this, 'this is not an EnemyVisualMain!');

        this.enemyvisual.enemy.unregister('enemy:set_npc', this);
        this.enemyvisual.unregister('enemyvisual:builtvisual', this);
    }
}