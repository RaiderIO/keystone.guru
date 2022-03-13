class CommonTagTagmanager extends InlineCode {

    constructor(options) {
        super(options);
    }

    activate() {
        super.activate();

        let $saveTag = $('.tag_save');
        $saveTag.unbind('click').bind('click', this._onTagSaveClicked);

        let $deleteTag = $('.tag_delete');
        $deleteTag.unbind('click').bind('click', this._onTagDeleteClicked);
    }

    /**
     *
     * @private
     */
    _onTagSaveClicked() {
        let id = $(this).data('id');

        $.ajax({
            type: 'PUT',
            url: `/ajax/tag/${id}/all`,
            data: {
                name: $(`#tag_name_${id}`).val(),
                color: $(`#tag_color_${id}`).val()
            },
            dataType: 'json',
            success: function (json) {
                showSuccessNotification(lang.get('messages.save_tag_success'));
            }
        });
    }

    /**
     *
     * @private
     */
    _onTagDeleteClicked() {
        let id = $(this).data('id');

        $.ajax({
            type: 'POST',
            url: `/ajax/tag/${id}/all`,
            data: {
                _method: 'DELETE'
            },
            dataType: 'json',
            success: function (json) {
                $(`#tag_row_${id}`).fadeOut();

                showSuccessNotification(lang.get('messages.delete_tag_success'));
            }
        });
    }
}
