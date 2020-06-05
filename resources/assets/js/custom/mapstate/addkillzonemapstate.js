class AddKillZoneMapState extends MapObjectMapState {
    constructor(map, killZone) {
        super(map, killZone);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', killZone);
    }

    stop(){
        console.assert(this instanceof AddKillZoneMapState, 'this is not a AddKillZoneMapState', this);
        this.sourceMapObject.floor_id = getState().getCurrentFloor().id;

        super.stop();
    }
}