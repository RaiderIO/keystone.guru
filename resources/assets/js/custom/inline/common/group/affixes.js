class CommonGroupAffixes extends InlineCode {
    /**
     *
     */
    activate() {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);

        let self = this;

        $(this.options.teemingSelector).bind('change', function () {
            $('#affixes').val('');
            self._applyAffixRowSelectionOnList();
        });
        $('.affix_list_row').bind('click', this._affixRowClicked.bind(this));

        // Perform loading of existing affix groups
        this._applyAffixRowSelectionOnList();
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
        let id = $el.data('id') + "";

        // Affixes is leading!
        let $affixRowSelect = $('#affixes');
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

    /**
     * Applies the current selection to the list of affixes that are being displayed.
     * @private
     */
    _applyAffixRowSelectionOnList() {
        console.assert(this instanceof CommonGroupAffixes, 'this was not a CommonGroupAffixes', this);
        
        let $list = $('#affixes_list_custom');
        let currentSelection = $('#affixes').val();

        $.each($list.children(), function (index, child) {
            let $child = $(child);
            let found = false;

            for (let i = 0; i < currentSelection.length; i++) {
                if (parseInt(currentSelection[i]) === $child.data('id')) {
                    $child.addClass('affix_list_row_selected');
                    $child.find('.check').show();
                    found = true;
                    break;
                }
            }

            if (!found) {
                $child.removeClass('affix_list_row_selected');
                $child.find('.check').hide();
            }
        });

        if (this._isTeemingSelected()) {
            $('.affix_row_no_teeming').hide();
            $('.affix_row_teeming').show();
        } else {
            $('.affix_row_no_teeming').show();
            $('.affix_row_teeming').hide();
        }
    }
}