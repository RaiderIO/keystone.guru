<script>
    /** Instance that handles the internal state for the dungeon map */
    let _stateManager;
    // Init it right away
    _stateManager = new StateManager();
    _stateManager.setAppType('{!! $appType !!}');
    _stateManager.setMapContext({!! new \Illuminate\Support\Collection($mapContext) !!});
    _stateManager.setDungeonData({!! $dungeonData !!});
    _stateManager.setMapIconTypes({!! $mapIconTypes !!});
    _stateManager.setClassColors({!! $classColors !!});
    _stateManager.setRawEnemies({!! $enemies !!});
    _stateManager.setRaidMarkers({!! $raidMarkers !!});
    _stateManager.setFactions({!! $factions !!});
    _stateManager.setPaidTiers({!! $paidTiers !!});
    @isset($userData)
    _stateManager.setUserData({!! $userData !!});
    @endisset
    @isset($mdt_enemies)
    _stateManager.setMdtEnemies({!! $mdt_enemies !!});

    @endisset
    @if($echo)
    _stateManager.enableEcho();

    @endif

    /**
     * Get the current state manager of the app.
     * @return StateManager
     **/
    function getState() {
        return _stateManager;
    }
</script>