class AdminReleaseEdit extends InlineCode {

    /**
     *
     */
    activate() {
        let self = this;

        if (this.options.changelog !== null) {
            let changes = this.options.changelog.changes;
            for (let i = 0; i < changes.length; i++) {
                let change = changes[i];
                this._addChangeRow(change.ticket_id, change.release_category_id, change.change);
            }
        }

        // Add an empty row for adding new stuff
        this._addChangeRow();

        // Add a new row when the button is pressed
        $('#add_change_button').bind('click', function () {
            self._addChangeRow();
        });
    }

    _addChangeRow(ticket = '', category = '', change = '') {
        let template = Handlebars.templates['release_change_row_template'];

        let data = $.extend({
            ticket: ticket,
            change: change,
            category: category,
            categories: this.options.categories
        }, getHandlebarsDefaultVariables());

        let html = template(data);

        let $container = $('#changes_container');
        $container.append(html);

        $('.change_delete_btn').bind('click', function () {
            // Remove the row
            $($(this).closest('.row')).remove();
        });
    }
}