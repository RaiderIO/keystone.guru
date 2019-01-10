// Icon sizes
let _smallIcon = {iconSize: [11, 11]};
let _bigIcon = {iconSize: [32, 32]};

// Default icon; placeholder while placing a new enemy. This can't really use the Visual system, it'd require
// too much rewrites. Better to just make a small placeholder like this and assign it to the below constructs.
let DefaultEnemyIcon = new L.divIcon($.extend({className: 'unset_enemy_icon'}, _smallIcon));

$(function () {
    L.Draw.Enemy = L.Draw.Marker.extend({
        statics: {
            TYPE: 'enemy'
        },
        options: {
            icon: DefaultEnemyIcon
        },
        initialize: function (map, options) {
            // Save the type so super can fire, need to do this as cannot do this.TYPE :(
            this.type = L.Draw.Enemy.TYPE;

            L.Draw.Feature.prototype.initialize.call(this, map, options);
        }
    });
});

let LeafletEnemyMarker = L.Marker.extend({
    options: {
        icon: DefaultEnemyIcon
    }
});

let LeafletMDTEnemyMarkerSelected = L.Marker.extend({
    options: {
        icon: DefaultEnemyIcon
    }
});

class Enemy extends MapObject {
    constructor(map, layer) {
        super(map, layer);

        this.label = 'Enemy';
        // Not actually saved to the enemy, but is used for keeping track of what killzone this enemy is attached to
        this.kill_zone_id = 0;
        this.faction = 'any'; // sensible default
        this.enemy_forces_override = -1;
        // May be set when loaded from server
        this.npc = null;
        this.raid_marker_name = '';

        // Infested variables
        this.infested_yes_votes = 0;
        this.infested_no_votes = 0;
        this.infested_user_vote = null;
        this.is_infested = false;

        // MDT
        this.mdt_id = -1;

        this.setSynced(true);

        let self = this;
        this.map.register('map:enemyselectionmodechanged', this, function (event) {
            // Remove the popup
            self.layer.unbindPopup();
            // Unselected a killzone
            if (event.data.finished) {
                // Restore it only if necessary
                self._rebuildPopup(event);
            }
        });

        // When we're synced, construct the popup.  We don't know the ID before that so we cannot properly bind the popup.
        this.register('synced', this, this._synced.bind(this));
    }

    _getPercentageString(enemyForces) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        // Do some fuckery to round to two decimal places
        return '(' + (Math.round((enemyForces / this.map.getEnemyForcesRequired()) * 10000) / 100) + '%)';
    }

    _synced(event) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');

        // Synced, can now build the popup since we know our ID
        this._rebuildPopup(event);

        // Create the visual now that we know all data to construct it properly
        this.visual = new EnemyVisual(this.map, this, this.layer);
    }

    /**
     * Since the ID may not be known at spawn time, this needs to be callable from when it is known (when it's synced to server).
     *
     * @param event
     * @private
     */
    _rebuildPopup(event) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        let self = this;

        // Popup should only be created when we're in edit mode
        if (this.map.edit) {
            // Popup trigger function, needs to be outside the synced function to prevent multiple bindings
            // This also cannot be a private function since that'll apparently give different signatures as well.
            let popupOpenFn = function (event) {
                $.each($('.enemy_raid_marker_icon'), function (index, value) {
                    let $icon = $(value);

                    // If we selected this raid marker..
                    if ($icon.data('name') === self.raid_marker_name) {
                        $icon.addClass('enemy_raid_marker_icon_selected');
                    }

                    $icon.unbind('click');
                    $icon.bind('click', function () {
                        self.assignRaidMarker($icon.data('name'));
                        // Deselect current raid markers
                        $('.enemy_raid_marker_icon_selected').removeClass('enemy_raid_marker_icon_selected');
                        // Add it to this one
                        $icon.addClass('enemy_raid_marker_icon_selected');
                    });
                });
                let $clearMarker = $('#enemy_raid_marker_clear_' + self.id);
                $clearMarker.unbind('click');
                $clearMarker.bind('click', function () {
                    // Empty is unassign
                    self.assignRaidMarker('');
                });
            };

            let customPopupHtml = $('#enemy_edit_popup_template').html();
            // Remove template so our
            let template = handlebars.compile(customPopupHtml);

            let data = {id: self.id};

            // Build the status bar from the template
            customPopupHtml = template(data);

            let customOptions = {
                'maxWidth': '160',
                'minWidth': '160',
                'className': 'popupCustom'
            };

            self.layer.unbindPopup();
            self.layer.bindPopup(customPopupHtml, customOptions);

            // Have you tried turning it off and on again?
            self.layer.off('popupopen', popupOpenFn);
            self.layer.on('popupopen', popupOpenFn);
        }
    }

    /**
     * Sets the click popup to be enabled or not.
     * @param enabled True to enable, false to disable.
     */
    setPopupEnabled(enabled) {
        if (enabled) {
            this._rebuildPopup();
        } else {
            this.layer.unbindPopup();
        }
    }

    /**
     * Get the amount of enemy forces that this enemy gives when killed.
     * @returns {number}
     */
    getEnemyForces() {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        return this.enemy_forces_override >= 0 ? this.enemy_forces_override : (this.npc === null ? 0 : this.npc.enemy_forces);
    }

    bindTooltip() {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        let source = $("#map_enemy_tooltip_template").html();
        let template = handlebars.compile(source);

        let data = {};
        if (this.npc !== null) {
            // Determine what to show for enemy forces based on override or not
            let enemy_forces = this.npc.enemy_forces;

            // Admin maps have 0 enemy forces
            if (this.map.getEnemyForcesRequired() > 0) {
                if (this.enemy_forces_override >= 0 || enemy_forces >= 1) {
                    // @TODO This HTML probably needs to go somewhere else
                    if (this.enemy_forces_override >= 0) {
                        enemy_forces = '<s>' + enemy_forces + '</s> ' +
                            '<span style="color: orange;">' + this.enemy_forces_override + '</span> ' + this._getPercentageString(this.enemy_forces_override);
                    } else if (enemy_forces >= 1) {
                        enemy_forces += ' ' + this._getPercentageString(enemy_forces);
                    }
                } else if (enemy_forces === -1) {
                    enemy_forces = 'unknown';
                }
            }

            let netVotes = this.infested_yes_votes - this.infested_no_votes;
            data = {
                npc_name: this.npc.name,
                enemy_forces: enemy_forces,
                base_health: this.npc.base_health,
                infested_yes_votes: this.infested_yes_votes,
                infested_no_votes: this.infested_no_votes,
                infested_net_votes: netVotes >= 0 ? '+' + netVotes : netVotes,
                id: this.id,
                attached_to_pack: this.enemy_pack_id >= 0 ? 'true (' + this.enemy_pack_id + ')' : 'false',
                visual: typeof this.visual !== 'undefined' ? this.visual.constructor.name : 'undefined'
            };
        }

        this.layer.bindTooltip(template(data), {
            offset: [0, 10],
            direction: 'bottom'
        });
    }

    /**
     * Sets the NPC for this enemy based on a remote NPC object.
     * @param npc
     */
    setNpc(npc) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        this.npc = npc;


        // May be null if not set at all (yet)
        if (npc !== null) {
            this.npc_id = npc.id;
            this.enemy_forces = npc.enemy_forces;
        } else {
            // Not set :(
            this.npc_id = -1;
        }

        this.signal('enemy:set_npc', {npc: npc});

        this.bindTooltip();
    }

    /**
     * Sets the name of the raid marker and changes the icon on the map to that of the raid marker (allowing).
     * @param name
     */
    setRaidMarkerName(name) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        this.raid_marker_name = name;
        // Trigger a raid marker change event
        this.signal('enemy:set_raid_marker', {name: name});
    }

    /**
     * Sets the killzone for this enemy.
     * @param killZoneId id
     */
    setKillZone(killZoneId) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        this.kill_zone_id = killZoneId;

        // We only want to trigger these events when the killzone is actively being edited, not when loading in
        // the connections from the server initially
        if (this.map.isEnemySelectionEnabled()) {
            if (this.kill_zone_id >= 0) {
                this.signal('killzone:attached');
            } else {
                this.signal('killzone:detached');
            }
        }
    }

    /**
     * Get the color of an enemy based on rated difficulty by users.
     * @param difficulty
     */
    getDifficultyColor(difficulty) {
        let palette = window.interpolate(c.map.enemy.colors);
        // let rand = Math.random();
        let color = palette(difficulty);
        this.setColors({
            saved: color,
            savedBorder: color,
            edited: color,
            editedBorder: color
        });
    }

    // To be overridden by any implementing classes
    onLayerInit() {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        super.onLayerInit();

        let self = this;

        // Show a permanent tooltip for the enemy's name
        this.layer.on('click', function () {
            if (self.selectable) {
                self.signal('enemy:selected');
            }
        });

        if (this.isEditable() && this.map.edit) {
            this.onPopupInit();
        }
    }

    onPopupInit() {
        console.assert(this instanceof Enemy, this, 'this was not an Enemy');
        let self = this;

        self.map.leafletMap.on('contextmenu', function () {
            if (self.currentPatrolPolyline !== null) {
                self.map.leafletMap.addLayer(self.currentPatrolPolyline);
                self.currentPatrolPolyline.disable();
            }
        });
    }

    /**
     * Checks if this enemy is possibly selectable when selecting enemies.
     * @returns {*}
     */
    isSelectable() {
        return this.selectable;
    }

    /**
     * Set this enemy to be selectable whenever the user wants to select enemies.
     * @param value boolean True or false
     */
    setSelectable(value) {
        console.assert(this instanceof Enemy, this, 'this is not an Enemy');
        this.selectable = value;
        // Refresh the icon
        this.visual.refresh();
    }

    /**
     * Assigns a raid marker to this enemy.
     * @param raidMarkerName The name of the marker, or empty to unset it
     */
    assignRaidMarker(raidMarkerName) {
        console.assert(this instanceof Enemy, this, 'this was not an Enemy');
        let self = this;

        let successFn = function (json) {
            self.map.leafletMap.closePopup();
            self.setRaidMarkerName(raidMarkerName);
        };

        // No network traffic!
        if (this.map.isTryModeEnabled()) {
            successFn();
        } else {
            $.ajax({
                type: 'POST',
                url: '/ajax/enemy/' + self.id + '/raidmarker',
                dataType: 'json',
                data: {
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    raid_marker_name: raidMarkerName
                },
                success: successFn,
            });
        }
    }

    /**
     * Lets the current user vote for infested enemies.
     * @param vote boolean True to vote yes, false to vote no.
     */
    voteInfested(vote) {
        console.assert(this instanceof Enemy, this, 'this was not an Enemy');
        let self = this;

        let successFn = function (json) {
            self.infested_yes_votes = json.infested_yes_votes;
            self.infested_no_votes = json.infested_no_votes;
            self.infested_user_vote = json.infested_user_vote;
            self.is_infested = json.is_infested;
            self.bindTooltip();
            self.signal('enemy:infested_vote', json);
        };

        // No network traffic!
        if (this.map.isTryModeEnabled()) {
            // User makes infested as they please
            successFn({'is_infested': vote});
        } else {
            $.ajax({
                type: 'POST',
                url: '/ajax/enemy/' + self.id + '/infested',
                dataType: 'json',
                data: {
                    vote: vote ? '1' : '0'
                },
                success: successFn,
            });
        }
    }

    cleanup() {
        super.cleanup();

        this.unregister('synced', this, this._synced.bind(this));
        this.map.unregister('map:enemyselectionmodechanged', this);
    }
}