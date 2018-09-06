class AdminEnemyPatrol extends EnemyPatrol {

    constructor(map, layer) {
        super(map, layer);

        this.saving = false;
        this.deleting = false;
        this.setSynced(false);

        this.enemy_id = -1;
    }

    delete() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypatrol',
            dataType: 'json',
            data: {
                _method: 'DELETE',
                id: self.id
            },
            beforeSend: function () {
                self.deleting = true;
            },
            success: function (json) {
                self.signal('object:deleted', {response: json});
            },
            complete: function () {
                self.deleting = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                self.setSynced(false);
            }
        });
    }

    edit(){
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        this.save();
    }

    save() {
        let self = this;
        console.assert(this instanceof AdminEnemyPatrol, this, 'this was not an AdminEnemyPatrol');
        $.ajax({
            type: 'POST',
            url: '/ajax/enemypatrol',
            dataType: 'json',
            data: {
                id: self.id,
                floor_id: self.map.getCurrentFloor().id,
                enemy_id: self.enemy_id,
                vertices: self.getVertices()
            },
            beforeSend: function () {
                self.saving = true;
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.edited,
                    color: c.map.admin.mapobject.colors.editedBorder
                });
            },
            success: function (json) {
                console.log(json);
                self.id = json.id;
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.saved,
                    color: c.map.admin.mapobject.colors.savedBorder
                });
                self.setSynced(true);
            },
            complete: function () {
                self.saving = false;
            },
            error: function () {
                self.layer.setStyle({
                    fillColor: c.map.admin.mapobject.colors.unsaved,
                    color: c.map.admin.mapobject.colors.unsavedBorder
                });
                // Even if we were synced, make sure user knows it's no longer / an error occurred
                self.setSynced(false);
            }
        });
    }
}