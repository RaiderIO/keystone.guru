<script>
    /** Instance that handles the internal state for the dungeon map */
    let _stateManager;

    // Init it right away
    _stateManager = new StateManager();
    _stateManager.setDungeonRoute({!! new \Illuminate\Support\Collection($dungeonroute) !!});
    _stateManager.setDungeonData({!! $dungeonData !!});
    _stateManager.setMapIconTypes({!! $mapIconTypes !!});
    _stateManager.setClassColors({!! $classColors !!});
    _stateManager.setRawEnemies({!! $enemies !!});
    _stateManager.setRaidMarkers({!! $raidMarkers !!});
    _stateManager.setFactions({!! $factions !!});
    @isset($mdt_enemies)
        _stateManager.setMdtEnemies({!! $mdt_enemies !!});
     @endisset

    /**
     * Get the current state manager of the app.
     * @return StateManager
     **/
    function getState() {
        return _stateManager;
    }
</script>