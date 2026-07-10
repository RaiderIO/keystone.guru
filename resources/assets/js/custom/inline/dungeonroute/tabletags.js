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
        $addTagBtns.unbind('click').bind('click', this._promptAddTagClicked.bind(this));
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

        this._refreshTagListeners(publicKey);

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
     * @param publicKey string
     * @param callback null|function
     * @private
     */
    _createTag(name, publicKey, callback = null) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);
        let self = this;

        $.ajax({
            type: 'POST',
            url: `/ajax/tag`,
            dataType: 'json',
            data: {
                context: this._dungeonrouteTable.options.teamPublicKey ?? this._dungeonrouteTable.options.currentUserPublicKey,
                context_class: this._dungeonrouteTable.options.teamPublicKey === null ? 'user' : 'team',
                category: this._dungeonrouteTable.options.teamPublicKey === null ? 'dungeon_route_personal' : 'dungeon_route_team',
                model_id: this._addTagPublicKey,
                name: name,
            },
            success: function (json) {
                showSuccessNotification(lang.get('js.tag_create_success'));

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
                showSuccessNotification(lang.get('js.tag_delete_success'));

                self._removeRenderedTagById(id);
            }
        });
    }

    /**
     * Unbinds and re-binds the listeners for each tag
     *
     * @private
     */
    _refreshTagListeners(publicKey) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);
        let self = this;

        let $tags = $('.tag');
        $tags.unbind('click').bind('click', function (e) {
            self._deleteTag($(this).data('id'))
        });

        // New tags text field
        $('#new_tag_input').unbind('keyup').bind('keyup', function (keyEvent) {
            let $this = $(this);

            // Enter
            if (keyEvent.keyCode === 13 && $this.val() !== null && $this.val().length > 0) {
                let $activeSuggestion = $('#new_tag_autocomplete .dropdown-item.active');
                if ($activeSuggestion.length > 0) {
                    // Adopt the highlighted suggestion; the next enter press creates it
                    // (mirrors bootstrap-4-autocomplete, which refocused the input on select)
                    $this.val($activeSuggestion.text());
                    self._hideTagAutocomplete();
                } else {
                    self._createTag($this.val(), publicKey, function () {
                        $this.val('');
                    });
                    self._hideTagAutocomplete();
                }
            } else if (keyEvent.keyCode === 27) {
                // Escape
                self._hideTagAutocomplete();
            } else if (keyEvent.keyCode === 38 || keyEvent.keyCode === 40) {
                // Arrow up/down move the suggestion highlight, like bootstrap-4-autocomplete did
                self._moveTagAutocompleteHighlight(keyEvent.keyCode === 40 ? 1 : -1);
            } else {
                self._refreshTagAutocomplete();
            }
        }).unbind('blur').bind('blur', function () {
            // Delay hiding so a mousedown on a suggestion still registers
            setTimeout(self._hideTagAutocomplete.bind(self), 200);
        });

        // Save button should work
        $('#new_tag_submit').unbind('click').bind('click', function () {
            let $newTagInput = $('#new_tag_input');
            let value = $newTagInput.val();

            if (value !== null && value.length > 0) {
                self._createTag(value, publicKey, function () {
                    $newTagInput.val('');
                });
            }
        });
    }

    /**
     * Renders tag suggestions below the new tag input (replaces bootstrap-4-autocomplete).
     *
     * @private
     */
    _refreshTagAutocomplete() {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);
        let self = this;

        let $menu = $('#new_tag_autocomplete');
        let value = `${$('#new_tag_input').val() ?? ''}`.trim();

        $menu.empty();

        if (value.length === 0) {
            this._hideTagAutocomplete();
            return;
        }

        let matchedTagNames = [];
        for (let i = 0; i < this._dungeonrouteTable.options.autoCompleteTags.length; i++) {
            let tagName = `${this._dungeonrouteTable.options.autoCompleteTags[i].name}`;
            if (tagName.toLowerCase().includes(value.toLowerCase())) {
                matchedTagNames.push(tagName);
            }
        }

        if (matchedTagNames.length === 0) {
            this._hideTagAutocomplete();
            return;
        }

        for (let i = 0; i < matchedTagNames.length; i++) {
            let tagName = matchedTagNames[i];
            let matchIndex = tagName.toLowerCase().indexOf(value.toLowerCase());

            // Build the item using text nodes so tag names cannot inject HTML
            let $item = $('<button/>', {type: 'button', 'class': 'dropdown-item'});
            $item.append(document.createTextNode(tagName.substring(0, matchIndex)));
            $item.append($('<span/>', {'class': 'text-danger', text: tagName.substring(matchIndex, matchIndex + value.length)}));
            $item.append(document.createTextNode(tagName.substring(matchIndex + value.length)));

            $item.bind('mousedown', function (mouseEvent) {
                // Mousedown fires before the input's blur, so the suggestion is applied before the menu hides
                mouseEvent.preventDefault();
                $('#new_tag_input').val(tagName).focus();
                self._hideTagAutocomplete();
            });

            $menu.append($('<li/>').append($item));
        }

        $menu.addClass('show');
    }

    /**
     * Moves the keyboard highlight across the rendered suggestions (replaces the arrow-key
     * support that bootstrap-4-autocomplete offered).
     *
     * @param direction {Number} 1 to move down, -1 to move up
     * @private
     */
    _moveTagAutocompleteHighlight(direction) {
        console.assert(this instanceof DungeonRouteTableTagsHandler, 'this is not a DungeonRouteTableTagsHandler', this);

        let $items = $('#new_tag_autocomplete .dropdown-item');
        if ($items.length === 0) {
            return;
        }

        let currentIndex = $items.index($items.filter('.active'));
        $items.removeClass('active');

        let nextIndex;
        if (currentIndex < 0) {
            nextIndex = direction > 0 ? 0 : $items.length - 1;
        } else {
            // Wraps around on both ends
            nextIndex = (currentIndex + direction + $items.length) % $items.length;
        }

        $items.eq(nextIndex).addClass('active');
    }

    /**
     * @private
     */
    _hideTagAutocomplete() {
        $('#new_tag_autocomplete').removeClass('show').empty();
    }
}
