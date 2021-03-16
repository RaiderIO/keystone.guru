class DungeonrouteDiscoverSearch extends InlineCode {

    /**
     */
    activate() {
        super.activate();

        $('#level').ionRangeSlider({
            type: 'double',
            grid: true,
            min: 2,
            max: 30,
            from: 2,
            to: 30
        });
    }

    cleanup() {
    }
}