/**
 @typedef {Object} CommonFormsOrderedselectOptions
 @property {string} listSelector      The <ol> holding the chosen items, in posting order.
 @property {string} templateSelector  <template> holding one blank list item.
 @property {string} addSelectSelector Select listing every item that may be added.
 @property {string} addButtonSelector Button adding the item chosen in the add select.
 @property {string} emptySelector     Shown instead of the list while it is empty.
 @property {string} countSelector     The "n / max" counter.
 @property {string} fullSelector      The note shown once the list holds max items.
 @property {string} statusSelector    Polite live region announcing every change.
 @property {Number} max
 @property {boolean} ajax             Report changes as events on the root instead of adding from the select.
 @property {string} rootSelector      The control's root element, which the ajax mode events are fired on.
 @property {Number|null} fullCount    What counts towards max in ajax mode, when that is more than this list.
 @property {string} countText         Contains :count and :max.
 @property {string} moveUpText        Contains :name.
 @property {string} moveDownText      Contains :name.
 @property {string} removeText        Contains :name.
 @property {string} addedStatusText   Contains :name and :position.
 @property {string} movedStatusText   Contains :name and :position.
 @property {string} removedStatusText Contains :name.
 */

/**
 * Keeps an ordered list of chosen items: add from a select, reorder by dragging or with the up/down
 * buttons, remove. Each item carries a hidden input, so the form posts the ids in list order. Every change
 * triggers `orderedselect:changed` on the list, which bubbles.
 *
 * In ajax mode the host page saves every change itself: a move fires `orderedselect:moved` and a remove
 * `orderedselect:removed` ({id, name, position}) on the root, and the host adds items, undoes changes and
 * tells the control how full it is through addItem(), removeItem(), setIds() and setFullCount().
 *
 * @property {CommonFormsOrderedselectOptions} options
 */
class CommonFormsOrderedselect extends InlineCode {

    activate() {
        super.activate();

        let $list = $(this.options.listSelector);

        if (!this.options.ajax) {
            $(this.options.addButtonSelector).unbind('click').bind('click', this._addSelected.bind(this));
        }
        $list.on('click', '.ordered_select_up', this._onMoveClicked.bind(this, -1));
        $list.on('click', '.ordered_select_down', this._onMoveClicked.bind(this, 1));
        $list.on('click', '.ordered_select_remove', this._onRemoveClicked.bind(this));

        if (typeof Draggable !== 'undefined') {
            this._sortable = new Draggable.Sortable($list[0], {
                draggable: '.ordered_select_item',
                handle: '.ordered_select_handle',
                mirror: {constrainDimensions: true},
            });
            // drag:stopped fires once the original item is back in the DOM at its new position
            this._sortable.on('drag:stopped', this._onDragStopped.bind(this));
        }

        this._refresh();
    }

    /**
     * Appends an item without announcing it as a change of the user's (ajax mode: the host saved it already).
     * @param {string|Number} id
     * @param {string} name
     */
    addItem(id, name) {
        if (this._findItem(id).length > 0) {
            return;
        }

        let $item = $($(this.options.templateSelector).prop('content').firstElementChild.cloneNode(true));
        $item.attr('data-id', id);
        $item.find('.ordered_select_label').text(name);
        $item.find('input[type="hidden"]').val(id);
        $(this.options.listSelector).append($item);

        this._refresh();
    }

    /**
     * Removes an item without announcing it as a change of the user's.
     * @param {string|Number} id
     */
    removeItem(id) {
        this._findItem(id).remove();
        this._refresh();
    }

    /**
     * @returns {string[]} The ids in list order.
     */
    getIds() {
        return this._getItems().map((index, element) => $(element).attr('data-id')).get();
    }

    /**
     * Puts the items in the passed order; ids that are not in the list are ignored.
     * @param {Array<string|Number>} ids
     */
    setIds(ids) {
        let $list = $(this.options.listSelector);
        ids.forEach(id => $list.append(this._findItem(id)));

        this._refresh();
    }

    /**
     * @param {Number} fullCount What counts towards max, e.g. every item of a group of lists.
     */
    setFullCount(fullCount) {
        this.options.fullCount = fullCount;
        this._refresh();
    }

    /**
     * @param {string|Number} id
     * @returns {jQuery}
     * @private
     */
    _findItem(id) {
        return this._getItems().filter((index, element) => $(element).attr('data-id') === String(id));
    }

    /**
     * @private
     */
    _onDragStopped() {
        this._refresh();

        if (this.options.ajax) {
            $(this.options.rootSelector).trigger('orderedselect:moved');
        }
    }

    /**
     * @private
     */
    _addSelected() {
        let $select = $(this.options.addSelectSelector);
        let id = $select.val();

        if (!id || this._getItems().length >= this.options.max) {
            return;
        }

        let $option = $select.find(`option[value="${id}"]`);
        let detail = $option.attr('data-detail');
        let isDetailWarning = $option.attr('data-detail-warning') === '1';
        // An option carrying a detail shows it in its text too; data-label holds the bare label
        let name = ($option.attr('data-label') ?? $option.text()).trim();

        let $item = $($(this.options.templateSelector).prop('content').firstElementChild.cloneNode(true));
        $item.attr('data-id', id);
        $item.find('.ordered_select_label').text(name);
        this._applyDetail($item, detail, isDetailWarning);
        $item.find('input[type="hidden"]').val(id);
        $(this.options.listSelector).append($item);

        $option.prop('disabled', true);
        $select.val('');

        this._refresh();
        this._announce(this.options.addedStatusText, name, this._getItems().length);

        // The add select is disabled once the list is full, so keep focus on something usable
        if ($select.prop('disabled')) {
            $item.find('.ordered_select_remove').trigger('focus');
        } else {
            $select.trigger('focus');
        }
    }

    /**
     * Shows the option's secondary text next to its label, flagged when it is a warning.
     *
     * @param {jQuery} $item
     * @param {string|undefined} detail
     * @param {boolean} isWarning
     * @private
     */
    _applyDetail($item, detail, isWarning) {
        let $detail = $item.find('.ordered_select_detail');
        if (detail === undefined) {
            $detail.prop('hidden', true);
            return;
        }

        let $warningText = $detail.find('.ordered_select_detail_warning_text');
        $detail.prop('hidden', false).toggleClass('ordered_select_detail_warning', isWarning);
        $detail.find('.ordered_select_detail_text').text(detail);
        $detail.find('.ordered_select_detail_icon').prop('hidden', !isWarning);
        $warningText.prop('hidden', !isWarning);
        if (isWarning && $warningText.length > 0) {
            $detail.attr('title', $warningText.text().trim());
        } else {
            $detail.removeAttr('title');
        }
    }

    /**
     * @param {Number} direction -1 moves the item up, 1 moves it down.
     * @param {Event} event
     * @private
     */
    _onMoveClicked(direction, event) {
        let $item = $(event.currentTarget).closest('.ordered_select_item');

        if (direction < 0) {
            $item.insertBefore($item.prev('.ordered_select_item'));
        } else {
            $item.insertAfter($item.next('.ordered_select_item'));
        }

        this._refresh();
        this._announce(this.options.movedStatusText, this._getName($item), this._getItems().index($item) + 1);

        if (this.options.ajax) {
            $(this.options.rootSelector).trigger('orderedselect:moved');
        }

        // At the top or bottom this button just became disabled; hand focus to the one that still works
        let $focus = $(event.currentTarget);
        if ($focus.prop('disabled')) {
            $focus = $item.find(direction < 0 ? '.ordered_select_down' : '.ordered_select_up');
        }
        $focus.trigger('focus');
    }

    /**
     * @param {Event} event
     * @private
     */
    _onRemoveClicked(event) {
        let $item = $(event.currentTarget).closest('.ordered_select_item');
        let $neighbour = $item.next('.ordered_select_item');
        if ($neighbour.length === 0) {
            $neighbour = $item.prev('.ordered_select_item');
        }

        let name = this._getName($item);
        let id = $item.attr('data-id');
        let position = this._getItems().index($item) + 1;
        $(this.options.addSelectSelector).find(`option[value="${id}"]`).prop('disabled', false);
        $item.remove();

        this._refresh();
        this._announce(this.options.removedStatusText, name, 0);

        if (this.options.ajax) {
            $(this.options.rootSelector).trigger('orderedselect:removed', [{id: id, name: name, position: position}]);
        }

        if ($neighbour.length > 0) {
            $neighbour.find('.ordered_select_remove').trigger('focus');
        } else if (this.options.ajax) {
            $(this.options.addButtonSelector).trigger('focus');
        } else {
            $(this.options.addSelectSelector).trigger('focus');
        }
    }

    /**
     * Renumbers the items and brings every dependent control in line with the list.
     *
     * @private
     */
    _refresh() {
        let self = this;
        let $items = this._getItems();
        let count = $items.length;
        let fullCount = this.options.ajax && typeof this.options.fullCount === 'number' ? this.options.fullCount : count;
        let isFull = fullCount >= this.options.max;

        $items.each(function (index, element) {
            let $item = $(element);
            let name = self._getName($item);

            $item.find('.ordered_select_position').text(index + 1);
            $item.find('.ordered_select_up')
                .prop('disabled', index === 0)
                .attr('aria-label', self._format(self.options.moveUpText, name));
            $item.find('.ordered_select_down')
                .prop('disabled', index === count - 1)
                .attr('aria-label', self._format(self.options.moveDownText, name));
            $item.find('.ordered_select_remove')
                .attr('aria-label', self._format(self.options.removeText, name));
        });

        $(this.options.listSelector).prop('hidden', count === 0);
        $(this.options.emptySelector).prop('hidden', count > 0);
        $(this.options.countSelector).text(
            this.options.countText.replace(':count', count).replace(':max', this.options.max)
        );
        $(this.options.fullSelector).prop('hidden', !isFull);
        $(this.options.addSelectSelector).prop('disabled', isFull);
        $(this.options.addButtonSelector).prop('disabled', isFull);

        $(this.options.listSelector).trigger('orderedselect:changed');
    }

    /**
     * Items only - Draggable inserts a mirror and keeps the original hidden while a drag is running.
     *
     * @returns {jQuery}
     * @private
     */
    _getItems() {
        return $(this.options.listSelector)
            .children('.ordered_select_item')
            .not('.draggable-mirror, .draggable--original');
    }

    /**
     * @param {jQuery} $item
     * @returns {string}
     * @private
     */
    _getName($item) {
        return $item.find('.ordered_select_label').text().trim();
    }

    /**
     * @param {string} text
     * @param {string} name
     * @returns {string}
     * @private
     */
    _format(text, name) {
        return text.replace(':name', name);
    }

    /**
     * @param {string} text
     * @param {string} name
     * @param {Number} position
     * @private
     */
    _announce(text, name, position) {
        $(this.options.statusSelector).text(this._format(text, name).replace(':position', position));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonFormsOrderedselect};
}
