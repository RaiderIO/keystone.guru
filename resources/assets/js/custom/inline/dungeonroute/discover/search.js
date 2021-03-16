class DungeonrouteDiscoverSearch extends InlineCode {

    constructor(options) {
        super(options);

        this.searchHandler = new SearchHandler();
    }

    /**
     */
    activate() {
        super.activate();

        let self = this;

        $('#title').on('focusout', function(){
            self._search();
        })

        $('#level').ionRangeSlider({
            grid: true,
            type: 'double',
            min: this.options.min,
            max: this.options.max,
            from: 2,
            to: 30
        });

        $('#rating').ionRangeSlider({
            grid: true,
            min: 1,
            max: 10,
            extra_classes: 'inverse'
        });
    }

    _getSearchParams(){
        return new SearchParams(
            0,
            $('#title').val()
        );
    }

    _search(){
        this.searchHandler.search(this._getSearchParams(), $('#route_list'));
    }

    cleanup() {
    }
}