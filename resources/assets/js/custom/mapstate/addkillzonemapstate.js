class AddKillZoneMapState extends MapObjectMapState {
    constructor(map, killZone) {
        super(map, killZone);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', killZone);
    }

    getName() {
        return 'AddKillZoneMapState';
    }

    start() {
        console.assert(this instanceof AddKillZoneMapState, 'this is not a AddKillZoneMapState', this);
        super.start();

        // Start drawing a killzone
        $('.leaflet-draw-draw-killzone')[0].click();
    }

    stop(){
        console.assert(this instanceof AddKillZoneMapState, 'this is not a AddKillZoneMapState', this);
        this.sourceMapObject.floor_id = getState().getCurrentFloor().id;

        super.stop();
    }
}