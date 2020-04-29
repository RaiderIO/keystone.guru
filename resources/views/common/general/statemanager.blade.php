<script>
    /** Instance that handles the internal state for the dungeon map */
    let _stateManager;

    // Init it right away
    _stateManager = new StateManager();
    _stateManager.setDungeonRoute({!! new \Illuminate\Support\Collection($dungeonroute) !!});
    _stateManager.setMapIconTypes({!! $mapIconTypes !!});

    /**
     * Get the current state manager of the app.
     * @return StateManager
     **/
    function getState() {
        return _stateManager;
    }
</script>