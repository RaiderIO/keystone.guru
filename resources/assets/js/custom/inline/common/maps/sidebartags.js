class CommonMapsSidebartags extends InlineCode {

    constructor(options) {
        super(options);

        // If not overridden, set default
        if (typeof options.dependencies === 'undefined') {
            options.dependencies = ['common/maps/editsidebar'];
        }
    }

    /**
     * Shows or hides the 'no tags' div
     * @private
     */
    _refreshNoTags() {
        // Show the no tags message or not
        $('#no_tags').toggle($('.tag').length === 0);
    }

    /**
     * Renders a tag on the screen
     * @param tag {Object}
     * @private
     */
    _renderTag(tag) {
        let template = Handlebars.templates['tag_render_template'];

        let data = $.extend({}, {
            edit: true
        }, tag);

        $('#tags_container').append(template(data));

        this._refreshNoTags();
    }

    /**
     * Removes a tag from the front end by
     * @param id
     * @private
     */
    _removeRenderedTagById(id) {
        $(`.tag[data-id='${id}']`).fadeOut();

        this._refreshNoTags();
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

        // Restore tags
        for (let index in this.options.tags) {
            if (this.options.tags.hasOwnProperty(index)) {
                this._renderTag(this.options.tags[index]);
            }
        }

        this.refreshTagListeners();


        // New tags text field
        let sourceTags = {};
        for (let i = 0; i < this.options.tags.length; i++) {
            let tagName = this.options.tags[i].name;
            sourceTags[`${tagName}`] = i;
        }

        $('#new_tag_input').on('keyup', function (keyEvent) {
            let $this = $(this);

            // Enter
            if (keyEvent.keyCode === 13) {
                $.ajax({
                    type: 'POST',
                    url: `/ajax/tag`,
                    dataType: 'json',
                    data: {
                        category: 'dungeon_route',
                        model_id: getState().getMapContext().getPublicKey(),
                        name: $this.val(),
                        color: '',
                    },
                    success: function (json) {
                        showSuccessNotification(lang.get('messages.tag_create_success'));

                        $this.val('');
                        self._renderTag(json);
                    }
                });
            }
        }).autocomplete({
            source: sourceTags,
            highlightClass: 'text-danger',
            onSelectItem: function (item, element) {
                console.log('onselectitem');
            },
        });
    }

    /**
     * Unbinds and re-binds the listeners for each tag
     */
    refreshTagListeners() {
        let self = this;

        let $tags = $('.tag');
        $tags.unbind('click');
        $tags.bind('click', function (e) {
            let $this = $(this);

            $.ajax({
                type: 'POST',
                url: `/ajax/tag/${$this.data('id')}`,
                dataType: 'json',
                data: {
                    _method: 'DELETE',
                },
                success: function () {
                    showSuccessNotification(lang.get('messages.tag_delete_success'));

                    self._removeRenderedTagById($this.data('id'));
                }
            });
        });
    }
}