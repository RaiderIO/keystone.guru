class RowElementFloorSwitch extends RowElement {

    constructor(killZonesSidebar, killZone, targetFloor, start = false) {
        super(killZonesSidebar, 'map_killzonessidebar_floor_switch_row_template');

        this.killZone = killZone;
        this.targetFloor = targetFloor;
        this.start = start;
    }

    /**
     *
     * @return {Object}
     * @protected
     */
    _getTemplateData() {
        console.assert(this instanceof RowElementFloorSwitch, 'this is not a RowElementFloorSwitch', this);

        let state = getState();
        return {
            'id': this.killZone.id,
            'target_floor_id': this.targetFloor.id,
            'name': lang.get(this.targetFloor.name),
            'edit': state.getMapContext() instanceof MapContextDungeonRoute && state.getDungeonMap().options.edit,
            'start': this.start
        };
    }

    /**
     * Triggered whenever someone clicks the row - initiating a floor switch
     * @private
     */
    _floorSwitchRowClicked() {
        let targetFloorId = parseInt($(this).data('id'));
        let state = getState();
        if (!state.isCurrentDungeonFacadeEnabled() && state.getCurrentFloor().id !== targetFloorId) {
            state.setFloorId(targetFloorId);
        }
    }

    /**
     * @inheritDoc
     */
    render($targetContainer, $after = null) {
        super.render($targetContainer);

        $(`#map_killzonessidebar_floor_switch_${this.killZone.id}`).unbind('click').bind('click', this._floorSwitchRowClicked);
    }

    /**
     * @inheritDoc
     */
    renderBefore($beforeElement) {
        super.renderBefore($beforeElement);
        console.assert(this instanceof RowElementFloorSwitch, 'this is not a RowElementFloorSwitch', this);

        $(`#map_killzonessidebar_floor_switch_${this.killZone.id}`).unbind('click').bind('click', this._floorSwitchRowClicked);
    }
}
