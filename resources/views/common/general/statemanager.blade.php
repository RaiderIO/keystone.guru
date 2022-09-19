<script>
    /** Instance that handles the internal state for the dungeon map */
    let _stateManager;
    // Init it right away
    _stateManager = new StateManager();
    _stateManager.setMapContext({!! new \Illuminate\Support\Collection($mapContext) !!});
    _stateManager.setPatreonBenefits({!! $patreonBenefits !!});
    @isset($userData)
    _stateManager.setUserData({!! $userData !!});
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
