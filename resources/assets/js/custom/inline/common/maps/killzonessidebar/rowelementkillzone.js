class RowElementKillZone extends RowElement {

    /**
     *
     * @param killZonesSidebar {CommonMapsKillzonessidebar}
     * @param killZone {KillZone}
     */
    constructor(killZonesSidebar, killZone) {
        super(killZonesSidebar, killZonesSidebar.options.edit ? 'map_killzonessidebar_killzone_row_edit_template' : 'map_killzonessidebar_killzone_row_view_template');

        console.assert(killZonesSidebar instanceof CommonMapsKillzonessidebar, 'killZonesSidebar is not a CommonMapsKillzonessidebar', this);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', this);

        let self = this;

        /** @type Boolean */
        this.initialized = false;
        /** @type Boolean */
        this.selected = false;
        /** @type KillZone */
        this.killZone = killZone;
        /** @type Pickr|null */
        this.colorPicker = null;

        this.killZone.register('killzone:changed', this, this.updateText.bind(this));
    }

    /**
     * @inheritDoc
     */
    _getTemplateData() {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        return {
            'id': this.killZone.id,
            'color': this.killZone.color, // For viewing
            'has_kill_area': this.killZone.hasKillArea() ? '1' : '0'
        };
    }

    /**
     * Selects a killzone based on a killzone, instead of a button click.
     * @param killZone
     * @private
     */
    _selectKillZoneByMapObject(killZone) {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        this._killZoneRowClicked.call($(`#map_killzonessidebar_killzone_${this.killZone.id}.selectable`));
    }

    /**
     * Called when someone clicked on a killzone row and wants to switch selections accordingly
     * @private
     */
    _killZoneRowClicked(clickEvent) {
        // this is the element that was clicked, NOT this class

        // If there was an event, prevent clicking the 'expand' button also selecting the kill zone
        if (clickEvent !== null && typeof clickEvent !== 'undefined') {
            let $target = $(clickEvent.target);
            let $parent = $($target.parent());
            if ($target.hasClass('btn') || $target.hasClass('pcr-button') || $parent.is('button')) {
                return;
            }
        }

        let map = getState().getDungeonMap();
        // Get the currently selected killzone ID, if any (so we may deselect it)
        let currentlySelectedKillZoneId = 0;
        let currentMapState = map.getMapState();
        if (currentMapState !== null && currentMapState instanceof EnemySelection) {
            currentlySelectedKillZoneId = currentMapState.getMapObject().id;
        }

        // Get the ID of the killzone that the user wants to select (or deselect)
        let selectedKillZoneId = parseInt($(this).closest('.map_killzonessidebar_killzone').data('id'));
        if (selectedKillZoneId === currentlySelectedKillZoneId) {
            // Selected what was already selected; select nothing instead
            selectedKillZoneId = 0;
        }

        // Find the killzone and if found, switch our map to a selection for that killzone
        let newMapState = null;
        if (selectedKillZoneId > 0) {
            let killZoneMapObjectGroup = map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_KILLZONE);

            /** @type KillZone */
            let killZone = killZoneMapObjectGroup.findMapObjectById(selectedKillZoneId);
            if (killZone !== null) {
                // Same as this.options.edit, really
                if (getState().getMapContext() instanceof MapContextLiveSession) {
                    newMapState = new SelectKillZoneEnemySelectionOverpull(map, killZone);
                } else {
                    if (map.options.edit) {
                        newMapState = new EditKillZoneEnemySelection(map, killZone);
                    } else {
                        // Just highlight the pull when the user clicked a pull
                        newMapState = new ViewKillZoneEnemySelection(map, killZone);
                    }
                }

                // Move the map to the killzone's center location
                map.focusOnKillZone(killZone);
            }
        }

        // Either de-select, or add a new state to the map
        map.setMapState(newMapState);
    }

    /**
     * @inheritDoc
     */
    updateText() {
        super.updateText();
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        let killZoneEnemyForces = this.killZone.getEnemyForces();
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_forces_container:not(.draggable--original)`).toggle(killZoneEnemyForces > 0);

        if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_PERCENTAGE) {
            let enemyForcesCumulativePercent = getFormattedPercentage(this.killZone.getEnemyForcesCumulative(), this.map.enemyForcesManager.getEnemyForcesRequired());
            let enemyForcesPercent = getFormattedPercentage(killZoneEnemyForces, this.map.enemyForcesManager.getEnemyForcesRequired());

            $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_forces_cumulative:not(.draggable--original)`)
                .text(`${enemyForcesCumulativePercent}%`)
                .attr('title', `+${enemyForcesPercent}%`)
                .refreshTooltips();

            // console.warn('refresh tooltips percentage', this.killZone.id);
        } else if (getState().getKillZonesNumberStyle() === NUMBER_STYLE_ENEMY_FORCES) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_forces_cumulative:not(.draggable--original)`)
                .text(`${this.killZone.getEnemyForcesCumulative()}/${this.map.enemyForcesManager.getEnemyForcesRequired()}`)
                .attr('title', `+${killZoneEnemyForces}`)
                .refreshTooltips();

            // console.warn('refresh tooltips enemy forces', this.killZone.id);
        }
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_index:not(.draggable--original)`).text(this.killZone.getIndex());

        // Show boss icon or not
        let hasBoss = false, hasAwakened = false, hasPrideful = false, hasInspiring = false, hasShrouded = false,
            hasShroudedZulGamux = false;

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);
        for (let i = 0; i < this.killZone.enemies.length; i++) {
            let enemyId = this.killZone.enemies[i];
            for (let enemyKey in enemyMapObjectGroup.objects) {
                let enemy = enemyMapObjectGroup.objects[enemyKey];
                if (enemy.id === enemyId) {
                    if (!hasBoss && enemy.isBossNpc()) {
                        hasBoss = true;
                    } else if (!hasAwakened && enemy.isAwakenedNpc()) {
                        hasAwakened = true;
                    } else if (!hasPrideful && enemy.isPridefulNpc()) {
                        hasPrideful = true;
                    } else if (!hasInspiring && enemy.isInspiring()) {
                        hasInspiring = true;
                    } else if (!hasShrouded && enemy.isShrouded()) {
                        hasShrouded = true;
                    } else if (!hasShroudedZulGamux && enemy.isShroudedZulGamux()) {
                        hasShroudedZulGamux = true;
                    }
                    break;
                }
            }
        }

        let hasAnything = hasBoss || hasAwakened || hasPrideful || hasInspiring || hasShrouded || hasShroudedZulGamux;

        let cumulativeShroudedEnemyStacks = this.killZone.getShroudedEnemyStacksCumulative();
        // Reset any previous states
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_placeholder:not(.draggable--original)`).toggle(!hasAnything);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_boss:not(.draggable--original)`).toggle(hasBoss);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_awakened:not(.draggable--original)`).toggle(hasAwakened);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_prideful:not(.draggable--original)`).toggle(hasPrideful);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_inspiring:not(.draggable--original)`).toggle(hasInspiring);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_shrouded:not(.draggable--original)`).toggle(hasShrouded)
            .find('.shrouded_stacks').text(cumulativeShroudedEnemyStacks);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_has_shrouded_zul_gamux:not(.draggable--original)`).toggle(hasShroudedZulGamux)
            .find('.shrouded_stacks').text(cumulativeShroudedEnemyStacks);
        $(`#map_killzonessidebar_killzone_${this.killZone.id}_description:not(.draggable--original)`).html(
            c.map.sanitizeText(this.killZone.description)
        );
        // $(`#map_killzonessidebar_killzone_${this.killZone.id}_grip:not(.draggable--original)`).css('color', this.killZone.color);
        // .css('color', killZone.color).css('text-shadow', `1px 1px #222`);
    }

    /**
     * @inheritDoc
     */
    refresh() {
        super.refresh();
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        this.updateText();

        // Fill the enemy list
        let npcs = [];
        let obsoleteNpcs = [];
        let overpulledNpcs = [];

        let enemyMapObjectGroup = this.map.mapObjectGroupManager.getByName(MAP_OBJECT_GROUP_ENEMY);

        let addEnemyToNpcList = (function (enemyId) {
            /** @type {Enemy} */
            let enemy = enemyMapObjectGroup.findMapObjectById(enemyId);

            // If enemy found and said enemy has an npc
            if (enemy !== null && enemy.npc !== null) {
                // Put the enemy in the correct bucket
                let npcArr = (enemy.getOverpulledKillZoneId() !== null ? overpulledNpcs : (enemy.isObsolete() ? obsoleteNpcs : npcs));
                // If not in our array, add it
                if (!npcArr.hasOwnProperty(enemy.npc.id)) {
                    npcArr[enemy.npc.id] = {
                        name: enemy.npc.name,
                        awakened: enemy.isAwakenedNpc(),
                        prideful: enemy.isPridefulNpc(),
                        inspiring: false, // Will be set below
                        obsolete: enemy.isObsolete(),
                        overpulled: enemy.getOverpulledKillZoneId() !== null,
                        enemy: enemy,
                        count: 0,
                        enemy_forces: 0
                    };
                }

                npcArr[enemy.npc.id].count++;
                npcArr[enemy.npc.id].enemy_forces += enemy.getEnemyForces();
                npcArr[enemy.npc.id].inspiring = npcArr[enemy.npc.id].inspiring || enemy.isInspiring();
            }
        }).bind(this);

        // Add both overpulled enemies and regular enemies to their respective lists
        for (let i = 0; i < this.killZone.overpulledEnemies.length; i++) {
            addEnemyToNpcList(this.killZone.overpulledEnemies[i]);
        }

        for (let i = 0; i < this.killZone.enemies.length; i++) {
            addEnemyToNpcList(this.killZone.enemies[i]);
        }

        let $enemyList = $(`#map_killzonessidebar_killzone_${this.killZone.id}_enemy_list`);
        $enemyList.children().remove();

        let addNpcToUI = (function (index, npc) {
            let template = Handlebars.templates['map_killzonessidebar_killzone_row_enemy_template'];

            let data = $.extend({}, getHandlebarsDefaultVariables(), {
                'id': index,
                'pull_color': this.killZone.color,
                'enemy_forces': npc.enemy_forces,
                'enemy_forces_percent': getFormattedPercentage(npc.enemy_forces, this.map.enemyForcesManager.getEnemyForcesRequired()),
                'count': npc.count,
                'name': npc.name,
                'awakened': npc.awakened,
                'prideful': npc.prideful,
                'inspiring': npc.inspiring,
                'overpulled': npc.overpulled,
                'obsolete': npc.obsolete,
                'boss': npc.enemy.isBossNpc(),
                'dangerous': npc.enemy.npc.dangerous === 1
            });

            let $enemy = $(template(data));
            $enemyList.append($enemy);
            $enemy.refreshTooltips();
        }).bind(this);

        // Rebuild the npc lists
        for (let index in overpulledNpcs) {
            if (overpulledNpcs.hasOwnProperty(index)) {
                addNpcToUI(index, overpulledNpcs[index]);
            }
        }

        for (let index in obsoleteNpcs) {
            if (obsoleteNpcs.hasOwnProperty(index)) {
                addNpcToUI(index, obsoleteNpcs[index]);
            }
        }

        for (let index in npcs) {
            if (npcs.hasOwnProperty(index)) {
                addNpcToUI(index, npcs[index]);
            }
        }

        // Add spells to the pull if applicable
        let $spellList = $(`#map_killzonessidebar_killzone_${this.killZone.id}_spell_list`);
        $spellList.children().remove();
        let template = Handlebars.templates['map_killzonessidebar_killzone_row_spell_template'];

        for (let index in this.killZone.spells) {
            let spell = this.killZone.spells[index];

            let data = $.extend({}, getHandlebarsDefaultVariables(), spell, {
                'pull_color': this.killZone.color,
            });

            let $spell = $(template(data));
            $spell.refreshTooltips();
            $spellList.append($spell);
        }

        // Toggle the row color based on overpulled or obsolete npcs
        if (getState().getMapContext() instanceof MapContextLiveSession) {
            let $row = $(`#map_killzonessidebar_killzone_${this.killZone.id}`);
            $row.toggleClass('bg-success', overpulledNpcs.length > 0);
            $row.toggleClass('bg-danger', obsoleteNpcs.length > 0);
        }

        this.initialized = true;
    }

    /**
     * @inheritDoc
     **/
    render($targetContainer, $after = null) {
        super.render($targetContainer, $after);
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        let self = this;

        // Make it interactive
        $(`#map_killzonessidebar_killzone_${self.killZone.id}.selectable`).unbind('click').bind('click', this._killZoneRowClicked);

        if (this.killZonesSidebar.options.edit) {
            $(`#map_killzonessidebar_killzone_${self.killZone.id}_edit`).unbind('click').bind('click', function (clickedEvent) {
                // User wants to edit this pull
                self.killZonesSidebar.pullWorkBench.editPull(self.killZone.id);
            });
        }
    }

    /**
     * Selects the killzone in the sidebar
     *
     * @param selected {Boolean}
     * @private
     */
    select(selected) {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        // Deselect if we were selected and shouldn't be
        let classes = 'selected bg-success';
        if (this.selected && !selected) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}.selected`).removeClass(classes);
        }

        // Select the new one if we should
        if (!this.selected && selected) {
            $(`#map_killzonessidebar_killzone_${this.killZone.id}.selectable`).addClass(classes);

            // Make sure we can see the killzone in the sidebar
            let $killzone = $(`#map_killzonessidebar_killzone_${this.killZone.id}`);
            if ($killzone.length > 0 && !$killzone.visible()) {
                $killzone[0].scrollIntoView({behavior: 'smooth'});
            }
        }

        this.selected = selected;
    }

    /**
     * Gets the kill zone for this enemy.
     * @returns {KillZone|null}
     */
    getKillZone() {
        console.assert(this instanceof RowElementKillZone, 'this is not a RowElementKillZone', this);

        return this.killZone;
    }

    /**
     *
     * @param callback
     */
    remove(callback) {
        super.remove();

        let self = this;
        $(`#map_killzonessidebar_killzone_${self.killZone.id}`).fadeOut({
            complete: function () {
                // When done, remove completely
                $(`#map_killzonessidebar_killzone_${self.killZone.id}`).remove();

                // Tell the user what to do next!
                if ($('#killzones_container .selectable').length === 0) {
                    $('#killzones_no_pulls').show();
                }

                if (typeof callback === 'function') {
                    callback();
                }
            }
        });

        // Unset it, ish
        this.colorPicker = null;
        $('#killzones_no_pulls').hide();


        this.killZone.unregister('killzone:changed', this);
        getState().getDungeonMap().unregister('map:mapstatechanged', this);
    }
}
