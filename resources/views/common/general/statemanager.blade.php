<?php

use App\Logic\MapContext\Map\MapContextBase;
use Illuminate\Support\Collection;

/**
 * @var MapContextBase $mapContext
 */
?>
<script>
    /** Instance that handles the internal state for the dungeon map */
    let _stateManager;
    // Init it right away
    _stateManager = new StateManager();
    /**
     * mapContextStaticData is defined in an external file, loaded per locale
     * mapContextDungeonData is defined in an external file, loaded per mapping version
     * */
    _stateManager.setMapContext($.extend({}, mapContextStaticData, mapContextDungeonData, {!! new Collection($mapContext->toArray()) !!}));
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
