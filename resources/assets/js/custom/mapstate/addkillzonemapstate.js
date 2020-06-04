class AddKillZoneMapState extends MapObjectMapState {
    constructor(map, killZone) {
        super(map, killZone);
        console.assert(killZone instanceof KillZone, 'killZone is not a KillZone', killZone);
    }
}