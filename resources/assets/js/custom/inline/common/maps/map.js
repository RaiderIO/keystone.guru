class CommonMapsMap extends InlineCode {

    constructor(options) {
        super(options);
        this._dungeonMap = null;
    }

    /**
     *
     * @returns {null}
     */
    getDungeonMap() {
        return this._dungeonMap;
    }

    /**
     *
     */
    activate() {
        super.activate();

        this.initDungeonMap();

        $('#share_modal').on('show.bs.modal', this._fetchMdtExportString.bind(this))
    }

    /**
     * Initializes the dungeon map. Cannot put this in activate() because that leads to the draw controls being messed up.
     */
    initDungeonMap() {
        let self = this;

        if (getState().isMapAdmin()) {
            this._dungeonMap = new AdminDungeonMap('map', this.options);
        } else {
            this._dungeonMap = new DungeonMap('map', this.options);
        }

        if (this.options.sandbox) {
            $('#start_tutorial').bind('click', function () {
                introjs().start();
            });

            // Bind leaflet virtual tour classes
            let selectors = [
                ['intro_sidebar', '#editsidebar', 'right'],
                ['intro_sidebar_toggle', '#editsidebarToggle', 'right'],

                ['intro_visibility_tools', '.visibility_tools', 'right'],
                ['intro_map_enemy_visuals', '#map_enemy_visuals', 'right'],
                ['intro_map_map_object_group_visibility_container', '#map_map_object_group_visibility_container', 'right'],
                ['intro_floor_selection', '#map_floor_selection_container', 'right'],

                ['intro_route_actions', '.route_actions', 'right'],
                ['intro_route_actions_draw_settings', '#map_draw_settings_btn_container', 'right'],
                ['intro_route_actions_map_login_and_continue', '#map_login_and_continue', 'right'],
                ['intro_route_actions_map_register_and_continue', '#map_register_and_continue', 'right'],
                ['intro_route_actions_save_and_continue', '#map_save_and_continue', 'right'],

                ['intro_route_manipulation_tools', '.route_manipulation_tools', 'top'],
                ['intro_draw_path', '.leaflet-draw-draw-path', 'left'],
                ['intro_draw_mapicon', '.leaflet-draw-draw-mapicon', 'left'],
                ['intro_draw_brushline', '.leaflet-draw-draw-brushline', 'left'],
                ['intro_draw_pridefulenemy', '.leaflet-draw-draw-pridefulenemy', 'left'],

                ['intro_draw_edit', '.leaflet-draw-edit-edit', 'left'],
                ['intro_draw_remove', '.leaflet-draw-edit-remove', 'left'],

                ['intro_map_enemy_forces_numbers', '#map_enemy_forces_numbers', 'left'],
                ['intro_new_pull', '#killzones_new_pull', 'left'],
            ];

            this._dungeonMap.register('map:refresh', null, function () {
                // Upon map refresh, re-init the tutorial selectors
                let step = 1;
                for (let i = 0; i < selectors.length; i++) {
                    let $selector = $(selectors[i][1]);
                    // Floor selection may not exist
                    if ($selector.length > 0) {
                        let messageKey = selectors[i][0];
                        $selector.attr('data-intro', lang.get(`messages.${messageKey}`));
                        $selector.attr('data-position', selectors[i][2]);
                        $selector.attr('data-step', step);

                        step++;
                    }
                }
            });
        }

        // Refresh the map; draw the layers on it
        this._dungeonMap.refreshLeafletMap();
    }

    /**
     *
     * @private
     */
    _fetchMdtExportString() {
        $.ajax({
            type: 'GET',
            url: `/ajax/${getState().getMapContext().getPublicKey()}/mdtExport`,
            dataType: 'json',
            beforeSend: function () {
                $('.mdt_export_loader_container').show();
                $('.mdt_export_result_container').hide();
            },
            success: function (json) {
                $('#mdt_export_result').val(json.mdt_string);

                // Inject the warnings, if there are any
                if (json.warnings.length > 0) {
                    (new MdtStringWarnings(json.warnings))
                        .render($('#mdt_export_result_warnings'));
                }

            },
            complete: function () {
                $('.mdt_export_loader_container').hide();
                $('.mdt_export_result_container').show();
            }
        });
    }
}