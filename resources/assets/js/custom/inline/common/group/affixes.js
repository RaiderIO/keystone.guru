/**
 * @typedef {Object} OptionsType
 * @property {string} currentExpansionKey - The current expansion key.
 * @property {string} dungeonroute - The dungeon route.
 */

/**
 * @property {OptionsType} options
 */
class CommonGroupAffixes extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);

        let self = this;

        this.currentSelection = null;
        this.currentSelectionExpansionKey = null;
        this.hasDungeonRoute = typeof this.options.dungeonroute !== 'undefined' && this.options.dungeonroute !== null;

        this._automaticSeasonalIndexChange = this.hasDungeonRoute;

        if (this.options.hasOwnProperty('teemingSelector')) {
            $(this.options.teemingSelector).bind('change', function () {
                self._applyAffixRowSelection();
            });
        }

        $(`${this.options.selectSelector}_list_custom .affix_list_row`).unbind('click').bind('click', this._affixRowClicked.bind(this));
        $(`${this.options.dungeonSelector}`).on('change', this._dungeonChanged.bind(this));

        // Perform loading of existing affix groups
        this._dungeonChanged();
    }

    /**
     * Dungeon selection changed, we also need to change the list of affixes that may be selected
     * @private
     */
    _dungeonChanged() {
        let selectedDungeonId = parseInt($(`${this.options.dungeonSelector}`).val());
        let expansionKeyOfDungeonAtRelease = this.options.dungeonExpansions[selectedDungeonId];

        // Don't mess with it if it's not working for whatever reason
        if (typeof expansionKeyOfDungeonAtRelease !== 'undefined' && expansionKeyOfDungeonAtRelease.length > 0) {
            let initialize = false;
            // Hide everything
            let $affixListRows = $(`${this.options.selectSelector}_list_custom .affix_list_row`).hide();

            // Check if the selected dungeon is part of the current or next season, if so, we need to offer a selection
            // of affixes based on season, not on expansion
            let seasonForSelectedDungeon = this._getSeasonForDungeon(selectedDungeonId);
            // By default, assume that the currently selected expansion is the expansion that the dungeon released in
            // This dungeon was from a season - so grab the expansion of the current season instead
            this.currentSelectionExpansionKey = seasonForSelectedDungeon !== null ? seasonForSelectedDungeon.expansion.shortname : expansionKeyOfDungeonAtRelease;

            // When initially loading the page, assign the default selected affixes
            if (this.currentSelection === null) {
                this.currentSelection = this.options.defaultSelected;
                initialize = true;
            }

            if (seasonForSelectedDungeon !== null) {
                // Don't override the default selection at the start
                if (!initialize) {
                    // Try to select the affix based on the currently active expansion + season
                    if (seasonForSelectedDungeon.id === this.options.currentSeason.id) {
                        let currentAffix = this.options.currentAffixes[this.currentSelectionExpansionKey];
                        if (currentAffix !== null) {
                            this.currentSelection = [currentAffix.id];
                        } else {
                            this.currentSelection = [seasonForSelectedDungeon.affix_groups[0].id];
                        }
                    }

                    // Try to select the affix based on the currently active expansion + season
                    if (this._hasNextSeason() && seasonForSelectedDungeon.id === this.options.nextSeason.id) {
                        this.currentSelection = [seasonForSelectedDungeon.affix_groups[0].id];
                    }
                }

                // Show the affixes for the season that was selected
                $affixListRows.filter(`.season.season-${seasonForSelectedDungeon.id}`).show();
            }
                // If the expansion changed we need to change the default selection, OR if we initialize, that means we need to trigger the
            // 'show affix_groups' jquery code
            else if (initialize || (this.currentSelectionExpansionKey !== expansionKeyOfDungeonAtRelease && !this.hasDungeonRoute)) {
                // Don't override the default selection at the start
                if (!initialize) {
                    let currentAffix = this.options.currentAffixes[this.currentSelectionExpansionKey];
                    if (currentAffix !== null) {
                        this.currentSelection = [currentAffix.id];
                    } else {
                        let firstAffixGroupForExpansion = this._getFirstAffixGroupForExpansion(this.currentSelectionExpansionKey);
                        if (firstAffixGroupForExpansion !== null) {
                            this.currentSelection = [firstAffixGroupForExpansion.id];
                        } else {
                            this.currentSelection = [];
                        }
                    }
                }

                // Show the affixes for the expansion that was selected
                $affixListRows.filter(`.${expansionKeyOfDungeonAtRelease}:not(.season)`).show();
            }

            // Show the correct presets for this expansion (if any)
            $(`.presets`).hide().filter(`.${this.currentSelectionExpansionKey}`).show();

            this._applyAffixRowSelection();
        } else {
            console.warn(`Could not find expansionKey`, expansionKeyOfDungeonAtRelease);
        }
    }

    /**
     * Checks if teeming is currently selected or not
     * @returns {*|jQuery}
     * @private
     */
    _isTeemingSelected() {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);
        return $(this.options.teemingSelector).is(':checked');
    }

    /**
     * Triggered whenever a user click on a row of affixes.
     * @private
     */
    _affixRowClicked(clickEvent) {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);

        let $el = $(clickEvent.currentTarget);
        // Convert to string since currentSelection has strings
        let id = parseInt($el.data('id'));

        // If it exists in the current selection
        let index = this.currentSelection.indexOf(id);
        if (index >= 0) {
            // remove it from the list
            this.currentSelection.splice(index, 1);
        }
        // Otherwise add it
        else {
            this.currentSelection.push(id);
        }

        this._applyAffixRowSelection();
    }

    /**
     *
     * @returns {boolean}
     * @private
     */
    _hasNextSeason() {
        return typeof this.options.nextSeason !== 'undefined' && this.options.nextSeason !== null;
    }

    /**
     *
     * @param {Number} id
     * @returns {Object|null}
     * @private
     */
    _getSeasonForDungeon(id) {
        let result = null;

        for (let i = 0; i < this.options.currentSeason.dungeons.length; i++) {
            if (this.options.currentSeason.dungeons[i].id === id) {
                result = this.options.currentSeason;
                break;
            }
        }

        if (this._hasNextSeason()) {
            for (let i = 0; i < this.options.nextSeason.dungeons.length; i++) {
                if (this.options.nextSeason.dungeons[i].id === id) {
                    result = this.options.nextSeason;
                    break;
                }
            }
        }

        return result;
    }

    /**
     *
     * @param {Number} id
     * @returns {Object|null}
     * @private
     */
    _getAffixGroupById(id) {
        let result = null;

        // Check seasons first
        let seasons = [this.options.currentSeason, this.options.nextSeason];
        for (let i = 0; i < seasons.length; i++) {
            let season = seasons[i];
            if (season !== null && typeof season !== 'undefined') {
                for (let j = 0; j < season.affix_groups.length; j++) {
                    if (season.affix_groups[j].id === id) {
                        result = season.affix_groups[j];
                        break;
                    }
                }
            }
        }

        // Check expansions next
        for (let i = 0; i < this.options.allAffixGroups.length; i++) {
            if (this.options.allAffixGroups[i].id === id) {
                result = this.options.allAffixGroups[i];
                break;
            }
        }

        return result;
    }

    /**
     *
     * @param {Number} expansionKey
     * @returns {String|null}
     * @private
     */
    _getFirstAffixGroupForExpansion(expansionKey) {
        let result = null;
        let expansionId = null;

        for (let key in this.options.allExpansions) {
            if (this.options.allExpansions.hasOwnProperty(key) && key === expansionKey) {
                expansionId = this.options.allExpansions[key];
                break;
            }
        }

        console.assert(expansionId !== null, `ExpansionId must be found! Cannot find for ${expansionKey}`);

        for (let index in this.options.allAffixGroups) {
            if (this.options.allAffixGroups.hasOwnProperty(index)) {
                let affixGroupCandidate = this.options.allAffixGroups[index];
                if (affixGroupCandidate.expansion_id === expansionId) {
                    result = affixGroupCandidate;
                    break;
                }
            }
        }

        return result;
    }

    /**
     * Applies the current selection to the list of affixes that are being displayed.
     * @private
     */
    _applyAffixRowSelection() {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);
        let self = this;

        let $list = $(`${this.options.selectSelector}_list_custom`);
        let selectedSeasonalIndices = [];

        $.each($list.children(), function (index, child) {
            let $child = $(child);
            let found = false;
            let childId = $child.data('id');

            for (let i = 0; i < self.currentSelection.length; i++) {
                let currentSelection = self.currentSelection[i];

                if (currentSelection === childId) {
                    let affixGroup = self._getAffixGroupById(childId);

                    if (!selectedSeasonalIndices.hasOwnProperty(affixGroup.seasonal_index)) {
                        selectedSeasonalIndices[affixGroup.seasonal_index] = 0;
                    }
                    selectedSeasonalIndices[affixGroup.seasonal_index]++;

                    $child.addClass('affix_list_row_selected');
                    $child.find('.check').css('visibility', 'visible');
                    found = true;
                    break;
                }
            }

            if (!found) {
                $child.removeClass('affix_list_row_selected');
                // Keep space by using visibility
                $child.find('.check').css('visibility', 'hidden');
            }
        });

        // Will stop if user manually changed it
        if (this._automaticSeasonalIndexChange) {
            let max = 0;
            let maxIndex = -1;
            for (let index in selectedSeasonalIndices) {
                if (selectedSeasonalIndices.hasOwnProperty(index)) {
                    let seasonalIndexCount = selectedSeasonalIndices[index];

                    if (max < seasonalIndexCount) {
                        max = seasonalIndexCount;
                        maxIndex = index;
                    }
                }
            }

            if (maxIndex >= 0) {
                $('#seasonal_index').val(maxIndex);
                refreshSelectPickers();
            }
        }

        $(this.options.selectSelector).val(this.currentSelection);

        // Teeming is no longer a thing - this intervenes with the affix selection for expansions, re-instate if Teeming is ever a thing again
        // if (this._isTeemingSelected()) {
        //     $('.affix_row_no_teeming').hide();
        //     $('.affix_row_teeming').show();
        // } else {
        //     $('.affix_row_no_teeming').show();
        //     $('.affix_row_teeming').hide();
        // }
    }

    /**
     * @param dungeonId {Number}
     * @param newAffixGroups {Object}
     */
    overrideSelection(dungeonId, newAffixGroups) {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this)
        console.assert(typeof newAffixGroups === 'object', 'newAffixGroups was not an Object', this);

        $(`${this.options.dungeonSelector}`).val(dungeonId);

        this.currentSelection = newAffixGroups;
        this._applyAffixRowSelection();
    }
}
