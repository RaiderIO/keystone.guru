class DungeonRouteTableTagsHandler {
    constructor(dungeonrouteTable) {
        /** @type DungeonrouteTable */
        this._dungeonrouteTable = dungeonrouteTable;

        // The route's public key we're trying to add a tag for - bit of a hack but it works
        this._addTagPublicKey = '';
    }

    activate() {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);

        let $addTagBtns = $('.dungeonroute-add-tag');
        $addTagBtns.unbind('click');
        $addTagBtns.bind('click', this._promptAddTagClicked.bind(this));
    }

    /**
     * Adds a new tag to a route
     * @param clickEvent
     * @private
     */
    _promptAddTagClicked(clickEvent) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);

        let publicKey = $(clickEvent.target).data('publickey');
        let template = Handlebars.templates['dungeonroute_table_add_tag_template'];

        this._addTagPublicKey = publicKey;

        showConfirmFinished(template($.extend({}, getHandlebarsDefaultVariables(), {
            publicKey: publicKey,
            teams: this._dungeonrouteTable.options.teams
        })), function () {
            // Refresh the table
            $('#dungeonroute_filter').trigger('click');
        }, {closeWith: ['button']});


        // Restore tags
        let routeData = this._dungeonrouteTable.getRouteDataByPublicKey(publicKey);
        if (routeData !== null) {

            let tags = routeData.hasOwnProperty('tagspersonal') ? routeData.tagspersonal : routeData.tagsteam;
            for (let index in tags) {
                if (tags.hasOwnProperty(index)) {
                    this._renderTag(tags[index]);
                }
            }

            // Hidden by default
            if (tags.length === 0) {
                $('#no_tags').show();
            }
        }

        this._refreshTagListeners();

        refreshSelectPickers();
    }

    /**
     * Shows or hides the 'no tags' div
     * @private
     */
    _refreshNoTags() {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);

        // Show the no tags message or not
        let hasTags = $('.tag').length !== 0;
        $('#no_tags').toggle(!hasTags);
        $('#tags_container_display').toggle(hasTags);
    }

    /**
     * Renders a tag on the screen
     * @param tag {Object}
     * @private
     */
    _renderTag(tag) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);

        let template = Handlebars.templates['tag_render_template'];

        let data = $.extend({}, {
            edit: true,
            dark: tag.color === null ? false : isColorDark(tag.color)
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
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);

        $(`.tag[data-id='${id}']`).fadeOut();

        this._refreshNoTags();
    }

    /**
     *
     * @param name string
     * @param callback null|function
     * @private
     */
    _createTag(name, callback = null) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/tag`,
            dataType: 'json',
            data: {
                category: this._dungeonrouteTable.options.teamPublicKey === '' ? 'dungeon_route_personal' : 'dungeon_route_team',
                model_id: this._addTagPublicKey,
                name: name,
            },
            success: function (json) {
                showSuccessNotification(lang.get('messages.tag_create_success'));

                self._renderTag(json);
                self._refreshTagListeners();

                if (typeof callback === 'function') {
                    callback();
                }
            }
        });
    }

    /**
     *
     * @param id Number
     * @private
     */
    _deleteTag(id) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/tag/${id}`,
            dataType: 'json',
            data: {
                _method: 'DELETE',
            },
            success: function () {
                showSuccessNotification(lang.get('messages.tag_delete_success'));

                self._removeRenderedTagById(id);
            }
        });
    }

    /**
     * Unbinds and re-binds the listeners for each tag
     *
     * @private
     */
    _refreshTagListeners() {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);
        let self = this;

        let $tags = $('.tag');
        $tags.unbind('click').bind('click', function (e) {
            self._deleteTag($(this).data('id'))
        });

        // New tags text field
        let sourceTags = {};
        for (let i = 0; i < this._dungeonrouteTable.options.autocompletetags.length; i++) {
            let tagName = this._dungeonrouteTable.options.autocompletetags[i].name;
            sourceTags[`${tagName}`] = i;
        }

        $('#new_tag_input').unbind('keyup').bind('keyup', function (keyEvent) {
            let $this = $(this);

            // Enter
            if (keyEvent.keyCode === 13 && $this.val() !== null && $this.val().length > 0) {
                self._createTag($this.val(), function () {
                    $this.val('');
                });
            }
        }).autocomplete({
            source: sourceTags,
            highlightClass: 'text-danger',
            // Typo intentional, it is part of the library
            treshold: 2,
            // In case they fix it
            threshold: 2,
            onSelectItem: function (item, element) {
                // Refocus the input so people can quickly press enter
                $('#new_tag_input').focus();
            },
        });

        // Save button should work
        $('#new_tag_submit').unbind('click').bind('click', function () {
            let $newTagInput = $('#new_tag_input');
            let value = $newTagInput.val();

            if (value !== null && value.length > 0) {
                self._createTag(value, function () {
                    $newTagInput.val('');
                });
            }
        });
    }
}