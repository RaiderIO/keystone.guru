class DungeonrouteTable extends InlineCode {

    constructor(options) {
        super(options);
        this._viewMode = 'biglist';
        this._dt = null;

        this._tableView = null;
        this._routeData = [];
        // Handles the displaying of tags inside the table
        this._tagsHandler = new DungeonRouteTableTagsHandler(this);
        // Handles the
        this._teamsHandler = new DungeonRouteTableTeam(this);

        this.carouselHandler = new CarouselHandler();

        // Init the code
        this.setViewMode(this.options.viewMode);
        let tableView = this.setTableView(this.options.tableView);
        // Make sure the TeamID is set if we need it
        if (typeof tableView.setTeamPublicKey === 'function') {
            tableView.setTeamPublicKey(this.options.teamPublicKey);
        }
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

        $('#dungeonroute_filter').unbind('click').bind('click', function () {
            // Build the search parameters
            let dungeonId = $('#dungeonroute_search_dungeon_id').val();
            let affixes = $('#affixes').val();
            let attributes = $('#attributes').val();

            // Find wherever the columns are we're looking for, then filter using them
            // https://stackoverflow.com/questions/32598279/how-to-get-name-of-datatable-column
            $.each(self._dt.settings().init().columns, function (index, value) {
                if (value.name === 'dungeon_id') {
                    self._dt.column(index).search(dungeonId);
                } else if (value.name === 'affixes.id') {
                    self._dt.column(index).search(affixes);
                } else if (value.name === 'routeattributes.name') {
                    self._dt.column(index).search(attributes);
                }
            });
            self._dt.draw();
        });

        $('.table_list_view_toggle').unbind('click').bind('click', function () {
            // Display the correct table
            self.setViewMode($(this).data('viewmode'));
            self.refreshTable();
        });
    }

    /**
     * Gets the view that is handling the display of the table.
     */
    getTableView() {
        return this._tableView;
    }

    /**
     * Sets the table to be in profile view mode or not (only show your own routes).
     * @param value
     */
    setTableView(value) {
        switch (value) {
            case 'favorites': {
                this._tableView = new FavoritesTableView();
                break;
            }
            case 'profile': {
                this._tableView = new ProfileTableView();
                break;
            }
            case 'userprofile': {
                this._tableView = new UserProfileTableView();
                break;
            }
            case 'team': {
                this._tableView = new TeamTableView();
                break;
            }
            case 'routes': {
                this._tableView = new RoutesTableView();
                break;
            }
        }

        console.assert(this._tableView !== null, 'Unable to find tableview for ' + value, this);
        return this._tableView;
    }

    /**
     * Sets the view mode of this table.
     * @param viewMode
     */
    setViewMode(viewMode) {
        this._viewMode = viewMode;
    }

    /**
     * Binds a datatables instance to a jquery element.
     **/
    refreshTable() {
        let self = this;

        // Send cookie
        Cookies.set('routes_viewmode', self._viewMode, cookieDefaultAttributes);

        let $element = $('#routes_table');

        // Set buttons to the correct state
        $('.table_list_view_toggle').removeClass('btn-default').removeClass('btn-primary').addClass('btn-default');

        // This is now the selected button
        $('#table_' + self._viewMode + '_btn').removeClass('btn-default').addClass('btn-primary');

        if (self._dt !== null) {
            self._dt.destroy();
            $element.empty();
        }

        self._dt = $element.DataTable({
            'processing': true,
            'serverSide': true,
            'responsive': true,
            'ajax': {
                'url': '/ajax/routes',
                'data': function (d) {
                    d.requirements = $('#dungeonroute_requirements_select').val();
                    d.tags = $('#dungeonroute_tags_select').val();
                    d = $.extend(d, self._tableView.getAjaxParameters());
                },
                'cache': false
            },
            'drawCallback': function (settings) {
                // Don't do anything when the message 'no data available' is showing
                if (settings.json.data.length > 0) {
                    // For each row in the body
                    $.each(self._dt.$('tbody tr'), function (trIndex, trValue) {
                        // For each td in the row
                        $.each($(trValue).find('td'), function (tdIndex, tdValue) {
                            $(tdValue).data('publickey', settings.json.data[trIndex].public_key);
                        });
                    });
                }

                self._routeData = settings.json.data;
            },
            'lengthMenu': [25],
            'bLengthChange': false,
            // Order by affixes by default
            'order': [[1 + (self._viewMode === 'biglist' ? 1 : 0), 'asc']],
            'columns': self._getColumns(),
            'searchCols': self._getDefaultSearchColumns(),
            'language': {
                'emptyTable': lang.get('messages.datatable_no_routes_in_table')
            }
        });

        self._dt.on('draw.dt', function (e, settings, json, xhr) {
            refreshTooltips();

            self._tagsHandler.activate();
            self._teamsHandler.activate();

            let $publishBtns = $('.dungeonroute-publish');
            $publishBtns.unbind('click').bind('click', self._publishDungeonRouteClicked);

            let $publishedStateBtns = $('.dungeonroute-publishedstate');
            $publishedStateBtns.unbind('click').bind('click', function (clickEvent) {
                self._changePublishState($(clickEvent.target).data('publickey'), $(clickEvent.target).data('publishedstate'));
            });

            let $cloneBtns = $('.dungeonroute-clone');
            $cloneBtns.unbind('click').bind('click', self._cloneDungeonRouteClicked);

            let $cloneToTeamBtns = $('.dungeonroute-clone-to-team');
            $cloneToTeamBtns.unbind('click').bind('click', self._promptCloneToTeamClicked.bind(self));

            let $migrateToEncryptedBtns = $('.dungeonroute-migrate-to-encrypted');
            $migrateToEncryptedBtns.unbind('click').bind('click', self._migrateToEncryptedClicked.bind(self));

            let $migrateToShroudedBtns = $('.dungeonroute-migrate-to-shrouded');
            $migrateToShroudedBtns.unbind('click').bind('click', self._migrateToShroudedClicked.bind(self));

            let $deleteBtns = $('.dungeonroute-delete');
            $deleteBtns.unbind('click').bind('click', self._promptDeleteDungeonRouteClicked);

            self.carouselHandler.refreshCarousel('', {autoWidth: false});
        });

        self._dt.on('click', 'tbody td.clickable', function (clickEvent) {
            let key = $(clickEvent.currentTarget).data('publickey');

            window.open((self._tableView.getName() === 'profile' ? '/replace_me/edit' : '/replace_me').replace('replace_me', key));
        });

        self._dt.on('mouseenter', 'tbody tr', function () {
            $(this).addClass('row_selected');
        });

        self._dt.on('mouseleave', 'tbody tr', function () {
            $(this).removeClass('row_selected');
        });
    }

    /**
     *
     * @returns {*[]}
     * @private
     */
    _getDefaultSearchColumns() {
        // Get a list of strings of what columns we want
        let viewColumns = this._tableView.getColumns(this._viewMode);

        // Map the string columns to actual DT columns and return the result
        let result = [];
        for (let index in viewColumns) {
            // Satisfy PhpStorm..
            if (viewColumns.hasOwnProperty(index)) {
                // Object containing name and width of the column
                let viewColumn = viewColumns[index];

                result.push(
                    viewColumn.hasOwnProperty('defaultSearch') ? {'search': viewColumn.defaultSearch} : null
                );
            }
        }

        return result;
    }

    /**
     * Get the columns based on the current view for the table.
     **/
    _getColumns() {
        let self = this;

        let columns = {
            preview: {
                'title': lang.get('messages.preview_label'),
                'data': 'public_key',
                'name': 'public_key',
                'render': function (data, type, row, meta) {
                    return handlebarsThumbnailCarouselParse(row);
                },
                'orderable': false
            },
            title: {
                'title': lang.get('messages.title_label'),
                'data': 'title',
                'name': 'title',
                'className': 'test',
                'render': function (data, type, row, meta) {
                    let result = '';


                    let published = '';
                    switch (row.published) {
                        case 'unpublished':
                            published = `<i class="fas fa-plane-arrival text-warning" data-toggle="tooltip" title="${lang.get('messages.route_table_published_state_unpublished')}"></i>`
                            break;
                        case 'team':
                            published = `<i class="fas fa-users text-success" data-toggle="tooltip" title="${lang.get('messages.route_table_published_state_team')}"></i>`
                            break;
                        case 'world':
                            published = `<i class="fas fa-globe text-success" data-toggle="tooltip" title="${lang.get('messages.route_table_published_state_world')}"></i>`
                            break;
                        case 'world_with_link':
                            published = `<i class="fas fa-link text-success" data-toggle="tooltip" title="${lang.get('messages.route_table_published_state_world_with_link')}"></i>`
                            break;
                    }

                    result = `${published} ${row.title}`;

                    if ((row.hasOwnProperty('tagspersonal') && row.tagspersonal.length > 0) ||
                        (row.hasOwnProperty('tagsteam') && row.tagsteam.length > 0)) {
                        let template = Handlebars.templates['dungeonroute_table_title_template'];

                        let rowTags = row.hasOwnProperty('tagspersonal') ? row.tagspersonal : row.tagsteam;

                        let tags = [];
                        for (let index in rowTags) {
                            if (rowTags.hasOwnProperty(index)) {
                                let tag = rowTags[index];

                                let template = Handlebars.templates['tag_render_template'];

                                let data = $.extend({}, {
                                    edit: false,
                                    dark: tag.color === null ? false : isColorDark(tag.color)
                                }, tag);

                                tags.push(template(data));
                            }
                        }

                        // Build the status bar from the template
                        result = template({
                            title: result,
                            tags: tags.join('')
                        });
                    }

                    return result;
                }
            },
            dungeon: {
                'title': lang.get('messages.dungeon_label'),
                'data': 'dungeon.name',
                'name': 'dungeon_id',
                'render': function (data, type, row, meta) {
                    return lang.get(data);
                },
            },
            features: {
                'title': lang.get('messages.features_label'),
                'data': 'affixes',
                'name': 'affixes.id',
                'render': function (data, type, row, meta) {
                    return handlebarsBiglistFeaturesParse(row);
                },
            },
            affixes: {
                'title': lang.get('messages.affixes_label'),
                'data': 'affixes',
                'name': 'affixes.id',
                'render': function (data, type, row, meta) {
                    return handlebarsAffixGroupsParse(data);
                },
                'className': 'd-none d-md-table-cell'
            },
            attributes: {
                'title': lang.get('messages.attributes_label'),
                'data': 'routeattributes',
                'name': 'routeattributes.name',
                'render': function (data, type, row, meta) {
                    return handlebarsRouteAttributesParse(data);
                },
                // Hide this column when in big list mode; we can't remove it since we need it in order for the filtering
                // to work on the server-side
                'className': this._viewMode === 'biglist' ? 'd-none' : ''
            },
            setup: {
                'title': lang.get('messages.setup_label'),
                'data': 'setup',
                'render': function (data, type, row, meta) {
                    return handlebarsGroupSetupParse(data);
                },
                'className': 'd-none d-lg-table-cell',
                'orderable': false
            },
            author: {
                'title': lang.get('messages.author_label'),
                'data': 'author.name',
                'name': 'author.name',
                'className': 'd-none ' + (self._tableView.getName() === 'profile' ? '' : 'd-lg-table-cell')
            },
            enemy_forces: {
                'title': lang.get('messages.enemy_forces_label'),
                'data': 'enemy_forces',
                'name': 'enemy_forces',
                'orderable': false,
                'render': function (data, type, row, meta) {
                    let enemyForcesRequired = row.teeming === 1 ? row.enemy_forces_required_teeming : row.enemy_forces_required;
                    let template = Handlebars.templates['dungeonroute_table_profile_enemy_forces_template'];

                    return template($.extend({}, getHandlebarsDefaultVariables(), {
                        enemy_forces: row.enemy_forces,
                        enemy_forces_required: enemyForcesRequired,
                        enough: row.enemy_forces >= enemyForcesRequired
                    }));
                }
            },
            views: {
                'title': lang.get('messages.metrics_label'),
                'data': 'views',
                'name': 'views',
                'render': function (data, type, row, meta) {
                    let findMetric = function (category, tag) {
                        for (let i in row.metric_aggregations) {
                            let metric = row.metric_aggregations[i];
                            if (metric.category === category && metric.tag === tag) {
                                return metric.value;
                            }
                        }

                        return 0;
                    }

                    let template = Handlebars.templates['dungeonroute_table_views_metrics'];
                    return template($.extend({}, getHandlebarsDefaultVariables(), {
                        views: row.views,
                        views_embed: row.views_embed,
                        copy_view: findMetric(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_VIEW),
                        copy_embed: findMetric(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_EMBED),
                    }));
                }
                // 'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}'
            },
            rating: {
                'title': lang.get('messages.rating_label'),
                'name': 'rating',
                'render': function (data, type, row, meta) {
                    let result = '-';

                    if (row.rating_count !== 0) {
                        result = row.rating;
                        if (row.rating_count === 1) {
                            result += ' (' + row.rating_count + ' ' + lang.get('messages.vote') + ')';
                        } else {
                            result += ' (' + row.rating_count + ' ' + lang.get('messages.votes') + ' )';
                        }
                    }

                    return result;
                }
            },
            actions: {
                'title': lang.get('messages.actions_label'),
                'render': function (data, type, row, meta) {
                    let template = Handlebars.templates['dungeonroute_table_profile_actions_template'];

                    let rowHasAffix = function (row, targetAffix) {
                        for (let index in row.affixes) {
                            let affixGroup = row.affixes[index];
                            for (let affixGroupIndex in affixGroup.affixes) {
                                let affix = affixGroup.affixes[affixGroupIndex];

                                // If the affix group contains an affix with encrypted, it's not possible to migrate
                                if (affix.key === targetAffix) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    }

                    // 9 = Shadowlands, 10 = Dragonflight
                    let seasonId = row.affixes.length === 0 ? false : row.affixes[0].expansion_id;
                    let isShadowlandsRoute = seasonId === 9;
                    let isDragonflightRoute = seasonId === 10;

                    let rowHasEncryptedAffix = rowHasAffix(row, AFFIX_ENCRYPTED);
                    let rowHasShroudedAffix = rowHasAffix(row, AFFIX_SHROUDED);

                    // @TODO add an additional check to see if the current route's dungeon is part of previous season?
                    return template($.extend({}, getHandlebarsDefaultVariables(), {
                        public_key: row.public_key,
                        published: row.published,
                        show_migrate_to_encrypted: isShadowlandsRoute && !rowHasEncryptedAffix && !rowHasShroudedAffix,
                        show_migrate_to_shrouded: isShadowlandsRoute && !rowHasShroudedAffix,
                        has_new_mapping_version: row.dungeon_latest_mapping_version_id !== row.mapping_version_id
                    }));
                }
            },
            addremoveroute: {
                'title': lang.get('messages.actions_label'),
                'render': function (data, type, row, meta) {
                    let result = null;
                    if (row.has_team) {
                        let template = Handlebars.templates['team_dungeonroute_table_route_actions_template'];
                        result = template($.extend({}, getHandlebarsDefaultVariables(), {public_key: row.public_key}));
                    } else {
                        let template = Handlebars.templates['team_dungeonroute_table_add_route_actions_template'];
                        result = template($.extend({}, getHandlebarsDefaultVariables(), {public_key: row.public_key}));
                    }
                    return result;
                }
            }
        };

        // Get a list of strings of what columns we want
        let viewColumns = this._tableView.getColumns(this._viewMode);

        // Map the string columns to actual DT columns and return the result
        let result = [];
        for (let index in viewColumns) {
            // Satisfy PhpStorm..
            if (viewColumns.hasOwnProperty(index)) {
                // Object containing name and width of the column
                let column = viewColumns[index];
                if (columns.hasOwnProperty(column.name)) {
                    let dtColumn = columns[column.name];
                    dtColumn.width = column.width;
                    dtColumn.className = column.hasOwnProperty('className') ? column.className : '';
                    // Default is clickable
                    dtColumn.className += !column.hasOwnProperty('clickable') || column.clickable === true ? ' clickable' : ' not_clickable';
                    result.push(dtColumn);
                } else {
                    console.error('Unable to find DT column for view column ', column);
                }
            }
        }

        return result;
    }

    /**
     * Changes the publish state of a dungeon route.
     * @param publicKey string
     * @param value string
     * @private
     */
    _changePublishState(publicKey, value) {
        $.ajax({
            type: 'POST',
            url: `/ajax/${publicKey}/publishedState`,
            data: {
                published_state: value
            },
            dataType: 'json',
            success: function (json) {
                showSuccessNotification(lang.get('messages.route_published_state_changed'));
                // Refresh the table
                $('#dungeonroute_filter').trigger('click');
            }
        });
    }

    /**
     * Prompts the user to delete a route (called by button press)
     * @param clickEvent
     * @private
     */
    _promptDeleteDungeonRouteClicked(clickEvent) {
        showConfirmYesCancel(lang.get('messages.route_delete_confirm'), function () {
            let publicKey = $(clickEvent.target).data('publickey');

            $.ajax({
                type: 'DELETE',
                url: `/ajax/${publicKey}`,
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('messages.route_delete_successful'));
                    // Refresh the table
                    $('#dungeonroute_filter').trigger('click');
                }
            });
        });

        // Prevent clicking delete from opening the route after it returns
        clickEvent.preventDefault();
        return false;
    }

    /**
     * Clones a dungeon route.
     * @param clickEvent
     * @returns {boolean}
     * @private
     */
    _cloneDungeonRouteClicked(clickEvent) {
        let key = $(clickEvent.target).attr('data-publickey');
        $('<a>').attr('href', '/replace_me/clone'.replace('replace_me', key))
            .attr('target', '_blank')[0].click();

        // Prevent clicking clone from opening the route after it returns
        clickEvent.preventDefault();
        return false;
    }

    /**
     * Clones a dungeon route to a specific team.
     * @param clickEvent
     * @returns {boolean}
     * @private
     */
    _promptCloneToTeamClicked(clickEvent) {
        let publicKey = $(clickEvent.target).data('publickey');
        let template = Handlebars.templates['dungeonroute_table_profile_clone_to_team_template'];

        showConfirmYesCancel(template($.extend({}, getHandlebarsDefaultVariables(), {
            publicKey: publicKey,
            teams: this.options.teams
        })), function () {
            let targetTeam = $(`input[type='radio'][name='clone-to-team-${publicKey}']:checked`).val();

            if (typeof targetTeam === 'undefined') {
                showErrorNotification(lang.get('messages.route_clone_select_team'));
                return;
            }

            $.ajax({
                type: 'POST',
                url: `/ajax/${publicKey}/clone/team/${targetTeam}`,
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('messages.route_clone_successful'));
                    // Refresh the table
                    $('#dungeonroute_filter').trigger('click');
                }
            });
        }, null, {closeWith: ['button']});

        refreshSelectPickers();
    }

    /**
     *
     * @param clickEvent
     * @returns {boolean}
     * @private
     */
    _migrateToEncryptedClicked(clickEvent) {
        this._migrateTo(clickEvent, 'encrypted');
    }

    /**
     *
     * @param clickEvent
     * @returns {boolean}
     * @private
     */
    _migrateToShroudedClicked(clickEvent) {
        this._migrateTo(clickEvent, 'shrouded');
    }

    /**
     *
     * @param clickEvent
     * @param affixName
     * @returns {boolean}
     * @private
     */
    _migrateTo(clickEvent, affixName) {
        let publicKey = $(clickEvent.target).data('publickey');

        showConfirmYesCancel(lang.get(`messages.route_migration_to_${affixName}_confirm_warning`), function () {
            $.ajax({
                type: 'POST',
                url: `/ajax/${publicKey}/migrate/${affixName}`,
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('messages.route_migration_successful'));
                    // Refresh the table
                    $('#dungeonroute_filter').trigger('click');
                }
            });
        }, null, {closeWith: ['button']});

        // Prevent clicking clone from opening the route after it returns
        clickEvent.preventDefault();
        return false;
    }

    /**
     *
     * @private
     */
    _reset() {
        $('#dungeonroute_search_dungeon_id').val(-1);
        $('#affixes').val([]);
        $('#attributes').val([]);
        $('#dungeonroute_requirements_select').val([]);
        $('#dungeonroute_tags_select').val([]);

        refreshSelectPickers();
    }

    /**
     *
     * @param dungeonId {Number}
     * @param affixGroupIds {Array}
     * @param attributes {Array}
     * @param requirements {Array}
     * @param tags {Array}
     */
    overrideSelection(dungeonId, affixGroupIds = [], attributes = [], requirements = [], tags = []) {
        this._reset();

        console.log(dungeonId, affixGroupIds, attributes, requirements, tags);

        $('#dungeonroute_search_dungeon_id').val(dungeonId);
        $('#affixes').val(affixGroupIds);
        $('#attributes').val(attributes);
        $('#dungeonroute_requirements_select').val(requirements);
        $('#dungeonroute_tags_select').val(tags);

        // Refresh the list of routes
        $('#dungeonroute_filter').trigger('click');
    }

    /**
     *
     * @param publicKey
     * @returns {null}
     */
    getRouteDataByPublicKey(publicKey) {
        let result = null;

        for (let index in this._routeData) {
            if (this._routeData.hasOwnProperty(index)) {
                let routeData = this._routeData[index];
                if (routeData.public_key === publicKey) {
                    result = routeData;
                    break;
                }
            }
        }

        return result;
    }
}
