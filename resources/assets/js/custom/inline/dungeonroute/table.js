class DungeonrouteTable extends InlineCode {

    constructor(options) {
        super(options);
        this._profileMode = false;
        this._viewMode = '';
        this._dt = {};
    }

    /**
     *
     */
    activate() {
        let self = this;

        $('#dungeonroute_filter').bind('click', function () {
            // Build the search parameters
            let dungeonId = $('#dungeonroute_search_dungeon_id').val();
            if (parseInt(dungeonId) < 1) {
                dungeonId = '';
            }
            let affixes = $('#affixes').val();
            let attributes = $('#attributes').val();

            let offset = self._viewMode === 'biglist' ? 1 : 0;
            self._dt[self._viewMode].column(offset).search(dungeonId);
            self._dt[self._viewMode].column(1 + offset).search(affixes);
            self._dt[self._viewMode].column(2 + offset).search(attributes);
            self._dt[self._viewMode].draw();
        });

        $('.table_list_view_toggle').bind('click', function () {
            // Display the correct table
            self.setViewMode($(this).data('viewmode'));
            self.refreshTable();
        });
    }

    /**
     * Sets the table to be in profile view mode or not (only show your own routes).
     * @param value
     */
    setProfileMode(value) {
        this._profileMode = value;
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

        // Send cookie only on the current page
        Cookies.set('routes_viewmode', self._viewMode, {path: ''});

        let $element = $('#routes_table_' + self._viewMode);

        // Hide all wrappers
        $('.routes_table_wrapper').hide();

        // Show the appropriate wrapper
        $('#routes_table_' + self._viewMode + '_wrapper').show();

        // Set buttons to the correct state
        $('.table_list_view_toggle').removeClass('btn-default').removeClass('btn-primary').addClass('btn-default');

        // This is now the selected button
        $('#table_' + self._viewMode + '_btn').removeClass('btn-default').addClass('btn-primary');

        // If not initialized
        if (!self._dt.hasOwnProperty(self._viewMode)) {
            self._dt[self._viewMode] = $element.DataTable({
                'processing': true,
                'serverSide': true,
                'responsive': true,
                'ajax': {
                    'url': '/ajax/routes',
                    'data': function (d) {
                        d.favorites = $('#favorites').is(':checked') ? 1 : 0;
                        d.mine = self._profileMode;
                    },
                    'cache': false
                },
                'drawCallback': function (settings) {
                    // Don't do anything when the message 'no data available' is showing
                    if (settings.json.data.length > 0) {
                        // For each row in the body
                        $.each(self._dt[self._viewMode].$('tbody tr'), function (trIndex, trValue) {
                            // For each td in the row
                            $.each($(trValue).find('td'), function (tdIndex, tdValue) {
                                $(tdValue).data('publickey', settings.json.data[trIndex].public_key);
                            });
                        });
                    }
                },
                'lengthMenu': [25],
                'bLengthChange': false,
                // Order by affixes by default
                'order': [[1 + (self._viewMode === 'biglist' ? 1 : 0), 'asc']],
                'columns': self._getColumns()
            });

            self._dt[self._viewMode].on('draw.dt', function (e, settings, json, xhr) {
                refreshTooltips();
                let $deleteBtns = $('.dungeonroute-delete');
                $deleteBtns.unbind('click');
                $deleteBtns.bind('click', self._promptDeleteDungeonRoute);

                let $cloneBtns = $('.dungeonroute-clone');
                $cloneBtns.unbind('click');
                $cloneBtns.bind('click', self._cloneDungeonRoute);

                $('.owl-carousel').owlCarousel({
                    // True to enable overlayed buttons (custom styled, wasted time :( )
                    nav: false,
                    loop: true,
                    dots: false,
                    lazyLoad: true,
                    lazyLoadEager: 1,
                    items: 1
                });
            });

            // When in biglist, the first entry does not trigger the click events
            let notFirst = self._viewMode === 'biglist' ? ':not(:first-child)' : '';
            let notLast = self._profileMode ? ':not(:last-child)' : '';

            self._dt[self._viewMode].on('click', 'tbody td' + notFirst + notLast, function (clickEvent) {
                let key = $(clickEvent.currentTarget).data('publickey');

                window.open((self._profileMode ? '/replace_me/edit' : '/replace_me').replace('replace_me', key));
            });

            self._dt[self._viewMode].on('mouseenter', 'tbody tr', function () {
                self._dt[self._viewMode].$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            });

            self._dt[self._viewMode].on('mouseleave', 'tbody tr', function () {
                if ($(this).hasClass('selected')) {
                    $(this).removeClass('selected');
                }
            });
        } else {
            // Force a click on the filter button to refresh the table
            $('#dungeonroute_filter').click();
        }
    }

    /**
     * Get the columns based on the current view for the table.
     **/
    _getColumns() {

        let columns = [];

        if (this._viewMode === 'biglist') {
            columns.push({
                'data': 'public_key',
                'name': 'public_key',
                'render': function (data, type, row, meta) {
                    return handlebarsThumbnailCarouselParse(row);
                },
                'orderable': false
            });
        }

        columns.push({
            'data': 'dungeon.name',
            'name': 'dungeon_id',
            'render': function (data, type, row, meta) {
                return data;
            },
            'className': this._viewMode === 'biglist' ? 'd-none d-md-table-cell' : '',
        });

        if (this._viewMode === 'biglist') {
            columns.push({
                'data': 'affixes',
                'name': 'affixes.id',
                'render': function (data, type, row, meta) {
                    return handlebarsBiglistFeaturesParse(row);
                },
            });
        } else {
            columns.push({
                'data': 'affixes',
                'name': 'affixes.id',
                'render': function (data, type, row, meta) {
                    return handlebarsAffixGroupsParse(data);
                },
                'className': 'd-none d-md-table-cell'
            });
        }

        columns.push({
            'data': 'routeattributes',
            'name': 'routeattributes.name',
            'render': function (data, type, row, meta) {
                return handlebarsRouteAttributesParse(data);
            },
            // Hide this column when in big list mode; we can't remove it since we need it in order for the filtering
            // to work on the server-side
            'className': this._viewMode === 'biglist' ? 'd-none' : ''
        });

        if (this._viewMode === 'list') {
            columns.push({
                'data': 'setup',
                'render': function (data, type, row, meta) {
                    return handlebarsGroupSetupParse(data);
                },
                'className': 'd-none d-lg-table-cell',
                'orderable': false
            });
        }
        columns.push({
            'data': 'author.name',
            'name': 'author.name',
            'className': 'd-none ' + (this._profileMode ? '' : 'd-lg-table-cell')
        });
        columns.push({
            'data': 'views',
            'name': 'views',
            // 'className': 'd-none {{ $profile ? '' : 'd-lg-table-cell'}}'
        });
        columns.push({
            'name': 'rating',
            'render': function (data, type, row, meta) {
                let result = '-';

                if (row.rating_count !== 0) {
                    result = row.avg_rating;
                    if (row.rating_count === 1) {
                        result += ' (' + row.rating_count + ' ' + lang.get('messages.vote') + ')';
                    } else {
                        result += ' (' + row.rating_count + ' ' + lang.get('messages.votes') + ' )';
                    }
                }

                return result;
            }
        });

        // Only display these columns when we're displaying the table in profile
        if (this._profileMode) {
            columns.push({
                'render': function (data, type, row, meta) {
                    return row.published === 1 ? 'Yes' : 'No';
                },
                'className': 'd-none d-lg-table-cell',
            });

            columns.push({
                'render': function (data, type, row, meta) {
                    let template = Handlebars.templates['dungeonroute_table_profile_actions_template'];
                    return template($.extend({public_key: row.public_key}, getHandlebarsDefaultVariables()));
                }
            });
        }

        return columns;
    }

    /**
     * Prompts the user to delete a route (called by button press)
     * @param clickEvent
     * @private
     */
    _promptDeleteDungeonRoute(clickEvent) {
        if (confirm(lang.get('messages.route_delete_confirm'))) {
            let publicKey = $(clickEvent.target).data('publickey');

            $.ajax({
                type: 'DELETE',
                url: '/ajax/dungeonroute/' + publicKey,
                dataType: 'json',
                success: function (json) {
                    showSuccessNotification(lang.get('messages.route_delete_successful'));
                    // Refresh the table
                    $('#dungeonroute_filter').trigger('click');
                }
            });
        }

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
    _cloneDungeonRoute(clickEvent) {
        let key = $(clickEvent.target).data('publickey');
        $('<a>').attr('href', '/replace_me/clone'.replace('replace_me', key))
            .attr('target', '_blank')[0].click();

        // Prevent clicking clone from opening the route after it returns
        clickEvent.preventDefault();
        return false;
    }
}