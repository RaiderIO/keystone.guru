<?php

use Illuminate\Database\Seeder;

class NpcSeeder extends Seeder
{
    /**
     * @var \App\Models\Dungeon The current dungeon.
     */
    private $_dungeon;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Just a base class
        $this->_rollback();
        $this->command->info('Adding known npcs for Halls of Valor');
    }

    /**
     * Insert data in format: <game_id>, <classification_id>, <name>, <health_mythic>, <aggressiveness>
     * [97087, 2, 'Valarjar Champion', 24335, 'aggressive'],
     * @param $data
     */
    protected function _insertData($data)
    {
        foreach($data as $npcData){
            $npc = new \App\Models\Npc([
                'id' => $npcData[0],
                'dungeon_id' => $this->_dungeon->id,
                'classification_id' => $npcData[1],
                'name' => $npcData[2],
                'base_health' => $npcData[3],
                'aggressiveness' => $npcData[4],
            ]);
            $npc->save();
        }
    }

    protected function _setDungeon($dungeon)
    {
        $this->_dungeon = $dungeon;
    }

    protected function _rollback()
    {
        DB::table('npcs')->where('dungeon_id', '=', $this->_dungeon->id)->delete();
    }
}
