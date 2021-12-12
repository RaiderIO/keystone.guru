class CommonGroupAffixes extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);

        let self = this;

        this._automaticSeasonalIndexChange = typeof this.options.dungeonroute !== 'object';

        if (this.options.hasOwnProperty('teemingSelector')) {
            $(this.options.teemingSelector).bind('change', function () {
                $(self.options.selectSelector).val('');
                self._applyAffixRowSelectionOnList();
            });
        }

        $(`${this.options.selectSelector}_list_custom .affix_list_row`).bind('click', this._affixRowClicked.bind(this));
        $(`${this.options.dungeonSelector}`).on('change', this._dungeonChanged.bind(this));

        // Perform loading of existing affix groups
        this._applyAffixRowSelectionOnList();
        this._dungeonChanged();
    }

    _dungeonChanged() {
        console.log('test');
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
        let id = $el.data('id') + '';

        // Affixes is leading!
        let $affixRowSelect = $(this.options.selectSelector);
        let currentSelection = $affixRowSelect.val();

        // If it exists in the current selection
        let index = currentSelection.indexOf(id);
        if (index >= 0) {
            // remove it from the list
            currentSelection.splice(index, 1);
        }
        // Otherwise add it
        else {
            currentSelection.push(id);
        }

        $affixRowSelect.val(currentSelection);
        this._applyAffixRowSelectionOnList();
    }

    _getAffixGroupById(id) {
        let result = null;

        for (let i = 0; i < this.options.affixGroups.length; i++) {
            if (this.options.affixGroups[i].id === id) {
                result = this.options.affixGroups[i];
                break;
            }
        }

        return result;
    }

    /**
     * Applies the current selection to the list of affixes that are being displayed.
     * @private
     */
    _applyAffixRowSelectionOnList() {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);
        let self = this;

        let $list = $(`${this.options.selectSelector}_list_custom`);
        let selection = $(this.options.selectSelector).val();
        let selectedSeasonalIndices = [];

        $.each($list.children(), function (index, child) {
            let $child = $(child);
            let found = false;

            for (let i = 0; i < selection.length; i++) {
                let currentSelection = parseInt(selection[i]);

                if (currentSelection === $child.data('id')) {
                    let affixGroup = self._getAffixGroupById(currentSelection);
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


        if (this._isTeemingSelected()) {
            $('.affix_row_no_teeming').hide();
            $('.affix_row_teeming').show();
        } else {
            $('.affix_row_no_teeming').show();
            $('.affix_row_teeming').hide();
        }
    }
}
