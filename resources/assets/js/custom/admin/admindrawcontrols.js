class AdminDrawControls extends DrawControls {
    /**
     * @returns {DrawTool[]}
     * @protected
     */
    _getTools() {
        let drawError = {
            color: '#e1e100', // Color the shape will turn when intersects
            message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
        };

        return [{
            id: 'enemy',
            group: 'enemies',
            icon: 'fa-user',
            label: 'js.enemy',
            title: 'js.enemy_title',
            keys: ['3'],
            handler: L.Draw.Enemy,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'enemypack',
            group: 'enemies',
            icon: 'fa-draw-polygon',
            label: 'js.enemypack',
            title: 'js.enemypack_title',
            keys: ['2'],
            handler: L.Draw.EnemyPack,
            options: {
                allowIntersection: false, // Restricts shapes to simple polygons
                drawError: drawError,
            },
        }, {
            id: 'enemypatrol',
            group: 'enemies',
            icon: 'fa-exchange-alt',
            label: 'js.enemypatrol',
            title: 'js.enemypatrol_title',
            keys: ['4'],
            handler: L.Draw.EnemyPatrol,
            options: {
                shapeOptions: {
                    color: 'red',
                    weight: 3
                },
                zIndexOffset: 1000,
            },
        }, {
            id: 'enemyforcescheckpoint',
            group: 'enemies',
            icon: 'fa-percent',
            label: 'js.enemyforcescheckpoint',
            title: 'js.enemyforcescheckpoint_title',
            keys: ['c'],
            handler: L.Draw.EnemyForcesCheckpoint,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'dungeonfloorswitchmarker',
            group: 'floors',
            icon: 'fa-door-open',
            label: 'js.dungeonfloorswitchmarker',
            title: 'js.dungeonfloorswitchmarker_title',
            keys: ['5'],
            handler: L.Draw.DungeonFloorSwitchMarker,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'dungeonstart',
            group: 'floors',
            icon: 'fa-flag-checkered',
            label: 'js.dungeonstart',
            title: 'js.dungeonstart_title',
            keys: ['s'],
            handler: L.Draw.DungeonStart,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'dungeontransport',
            group: 'floors',
            icon: 'fa-ship',
            label: 'js.dungeontransport',
            title: 'js.dungeontransport_title',
            keys: ['t'],
            handler: L.Draw.DungeonTransport,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'floorunion',
            group: 'floors',
            icon: 'fa-object-group',
            label: 'js.floorunion',
            title: 'js.floorunion_title',
            keys: ['7'],
            handler: L.Draw.FloorUnion,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'floorunionarea',
            group: 'floors',
            icon: 'fa-chart-pie',
            label: 'js.floorunionarea',
            title: 'js.floorunionarea_title',
            keys: ['8'],
            handler: L.Draw.FloorUnionArea,
            options: {
                shapeOptions: {
                    color: c.map.floorunionarea.color
                },
                allowIntersection: false, // Restricts shapes to simple polygons
                drawError: drawError,
            },
        }, {
            id: 'mapicon',
            group: 'markers',
            icon: 'fa-icons',
            label: 'js.admin_mapicon',
            title: 'js.mapicon_title',
            keys: ['1'],
            handler: L.Draw.MapIcon,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'mountablearea',
            group: 'markers',
            icon: 'fa-horse-head',
            label: 'js.mountablearea',
            title: 'js.mountablearea_title',
            keys: ['6'],
            handler: L.Draw.MountableArea,
            options: {
                shapeOptions: {
                    color: c.map.mountablearea.color
                },
                allowIntersection: false, // Restricts shapes to simple polygons
                drawError: drawError,
            },
        }, {
            id: 'awakenedobeliskgatewaymapicon',
            hidden: true,
            handler: L.Draw.AwakenedObeliskGatewayMapIcon,
            options: {
                repeatMode: false,
                zIndexOffset: 1000,
            },
        }, {
            id: 'edit',
            kind: 'edit',
            icon: 'fa-edit',
            label: 'js.edit',
            title: 'js.edit_title',
            keys: ['9'],
        }, {
            id: 'delete',
            kind: 'remove',
            icon: 'fa-trash',
            label: 'js.delete',
            title: 'js.delete_title',
            keys: ['0'],
            btnType: 'btn-danger',
        }];
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {AdminDrawControls};
}
