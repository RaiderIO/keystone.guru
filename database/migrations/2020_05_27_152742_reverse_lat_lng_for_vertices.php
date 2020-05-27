<?php

use Illuminate\Database\Migrations\Migration;

class ReverseLatLngForVertices extends Migration
{
    private function _reverseLatLngs($table){
        $objects = DB::select(sprintf('SELECT * FROM `%s`', $table));

        foreach ($objects as $obj) {
            $oldVertices = json_decode($obj->vertices_json);
            $newVertices = [];
            foreach($oldVertices as $latlng){
                $newVertices[] = ['lat' => $latlng->lng, 'lng' => $latlng->lat];
            }

            DB::update(sprintf('UPDATE `%s` SET `%s`.vertices_json = \'%s\' WHERE `id` = \'%s\'', $table, $table, json_encode($newVertices), $obj->id));
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->_reverseLatLngs('polylines');
        $this->_reverseLatLngs('enemy_packs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
