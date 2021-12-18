/**
 * @property [currentExpansionKey: string, dungeonroute: string] options
 */
class CommonGroupAffixes extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);

        let self = this;

        this.currentSelection = this.options.defaultSelected;
        this.currentSelectionExpansionKey = null;
        this.hasDungeonRoute = typeof this.options.dungeonroute !== 'undefined' && this.options.dungeonroute !== null;

        this._automaticSeasonalIndexChange = this.hasDungeonRoute;

        if (this.options.hasOwnProperty('teemingSelector')) {
            $(this.options.teemingSelector).bind('change', function () {
                self._applyAffixRowSelection();
            });
        }

        $(`${this.options.selectSelector}_list_custom .affix_list_row`).bind('click', this._affixRowClicked.bind(this));
        $(`${this.options.dungeonSelector}`).on('change', this._dungeonChanged.bind(this));

        // Perform loading of existing affix groups
        this._dungeonChanged();
    }

    /**
     * Dungeon selection changed, we also need to change the list of affixes that may be selected
     * @private
     */
    _dungeonChanged() {
        let selectedDungeonId = $(`${this.options.dungeonSelector}`).val();
        let expansionKey = this.options.dungeonExpansions[selectedDungeonId];

        // Don't mess with it if it's not working for whatever reason
        if (typeof expansionKey !== 'undefined' && expansionKey.length > 0) {

            // Hide everything
            let $affixListRows = $(`${this.options.selectSelector}_list_custom .affix_list_row`).hide();
            // Show the affixes for the expansion that was selected
            $affixListRows.filter(`.${expansionKey}`).show();

            // If the expansion changed we need to change the default selection
            if (this.currentSelectionExpansionKey !== expansionKey && !this.hasDungeonRoute) {
                this.currentSelectionExpansionKey = expansionKey;
                this.currentSelection = [this.options.currentAffixes[this.currentSelectionExpansionKey]];
            }

            // Show the correct presets for this expansion (if any)
            $(`.presets`).hide().filter(`.${this.currentSelectionExpansionKey}`).show();

            this._applyAffixRowSelection();
        } else {
            console.warn(`Could not find expansionKey`, expansionKey);
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
     * @param {Number} id
     * @returns {Object|null}
     * @private
     */
    _getAffixGroupById(id) {
        let result = null;

        for (let i = 0; i < this.options.allAffixGroups.length; i++) {
            if (this.options.allAffixGroups[i].id === id) {
                result = this.options.allAffixGroups[i];
                break;
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
}
