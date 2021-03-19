class DungeonrouteDiscoverSearch extends InlineCode {

    constructor(options) {
        super(options);

        this.searchHandler = new SearchHandler();
        // Previous search params are used to prevent searching for the same thing multiple times for no reason
        this._previousSearchParams = null;
        // The current offset
        this.offset = 0;
    }

    /**
     */
    activate() {
        super.activate();

        let self = this;

        $('.grid_dungeon.selectable').bind('click', function () {
            $(this).toggleClass('selected');
            self._search();
        })

        $('#title,#user').on('keypress', function (keyEvent) {
            // Enter pressed
            if (keyEvent.keyCode === 13) {
                self._search();
            }
        }).on('focusout', function () {
            self._search();
        })

        $('#level').ionRangeSlider({
            grid: true,
            type: 'double',
            min: this.options.min,
            max: this.options.max,
            from: 2,
            to: 30,
            onFinish: function (data) {
                self._search();
            }
        });

        // Grouped affixes
        $('#filter_affixes').change(function () {
            console.log('change');
            self._search();
        })

        // Individual affixes
        $('.select_icon.class_icon.selectable').bind('click', function () {
            $(this).toggleClass('selected');
            self._search();
        })

        $('#enemy_forces').change(function () {
            self._search();
        });

        $('#rating').ionRangeSlider({
            grid: true,
            min: 1,
            max: 10,
            extra_classes: 'inverse',
            onFinish: function (data) {
                self._search();
            }
        });
    }

    /**
     *
     * @returns {SearchParams}
     * @private
     */
    _getSearchParams() {
        let dungeonIds = [];
        $('.grid_dungeon.selected').each(function (index, element) {
            dungeonIds.push($(element).data('id'));
        });

        // Individual affixes
        let affixes = [];
        $('.select_icon.class_icon.selected').each(function (index, element) {
            affixes.push($(element).data('id'));
        });

        return new SearchParams({
            dungeons: dungeonIds,
            offset: this.offset,
            title: $('#title').val(),
            affixgroups: $('#filter_affixes').val(),
            affixes: affixes,
            enemy_forces: $('#enemy_forces').is(':checked') ? 1 : 0,
            rating: $('#rating').val(),
            user: $('#user').val(),
        });
    }

    _search() {
        let searchParams = this._getSearchParams();

        // Only search if the search parameters have changed
        if (this._previousSearchParams === null || !this._previousSearchParams.equals(searchParams)) {
            this.searchHandler.search(searchParams, $('#route_list'));

            this._previousSearchParams = searchParams;
        }
    }

    cleanup() {
    }
}