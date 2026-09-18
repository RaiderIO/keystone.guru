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
 * buttons, remove. Each item carries a hidden input, so the form posts the ids in list order.
 *
 * @property {CommonFormsOrderedselectOptions} options
 */
class CommonFormsOrderedselect extends InlineCode {

    activate() {
        super.activate();

        let $list = $(this.options.listSelector);

        $(this.options.addButtonSelector).unbind('click').bind('click', this._addSelected.bind(this));
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
            this._sortable.on('drag:stopped', this._refresh.bind(this));
        }

        this._refresh();
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
        let name = $option.text().trim();

        let $item = $($(this.options.templateSelector).prop('content').firstElementChild.cloneNode(true));
        $item.attr('data-id', id);
        $item.find('.ordered_select_label').text(name);
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
        $(this.options.addSelectSelector).find(`option[value="${$item.attr('data-id')}"]`).prop('disabled', false);
        $item.remove();

        this._refresh();
        this._announce(this.options.removedStatusText, name, 0);

        if ($neighbour.length > 0) {
            $neighbour.find('.ordered_select_remove').trigger('focus');
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
        let isFull = count >= this.options.max;

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
