<?php

class HallsOfValorNpcSeeder extends NpcSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dungeon = \App\Models\Dungeon::all()->where('name', '=', 'Halls of Valor')->first();
        $this->_setDungeon($dungeon);

        parent::run();

        //
        $this->_insertData([
            [97087, 2, 'Valarjar Champion', 24335, 'aggressive'],
            [99804, 2, 'Valarjar Falconer', 31288, 'aggressive'],
            [101637, 2, 'Valarjar Aspirant', 34764, 'aggressive'],
            [96640, 2, 'Valarjar Marksman', 34069, 'aggressive'],
            [95834, 2, 'Valarjar Mystic', 34764, 'aggressive'],
            [97197, 2, 'Valarjar Purifier', 34764, 'aggressive'],
            [96664, 2, 'Valarjar Runecarver', 34764, 'aggressive'],
            [95832, 2, 'Valarjar Shieldmaiden', 34764, 'aggressive'],
            [95842, 2, 'Valarjar Thundercaller', 36369, 'aggressive'],
            [96934, 2, 'Valarjar Trapper', 35807, 'aggressive'],
            [96611, 2, 'Angerhoof Bull', 38241, 'neutral'],
            [96608, 2, 'Ebonclaw Worg', 31288, 'aggressive'],
            [96609, 2, 'Gildedfur Stag', 10429, 'neutral'],
            [97081, 2, 'King Bjorn', 82990, 'aggressive'],
            [95843, 2, 'King Haldor', 82990, 'aggressive'],
            [97083, 2, 'King Ranulf', 82990, 'aggressive'],
            [97084, 2, 'King Tor', 82990, 'aggressive'],
            [97202, 2, 'Olmyr the Enlightened', 115079, 'aggressive'],
            [97219, 2, 'Solsten', 115079, 'aggressive'],
            [96677, 2, 'Steeljaw Grizzly', 41717, 'aggressive'],
            [99891, 2, 'Storm Drake', 61136, 'aggressive'],
            [96574, 2, 'Stormforged Sentinel', 57539, 'aggressive'],
            [99828, 2, 'Trained Hawk', 15001, 'aggressive'],
            [94960, 3, 'Hymdall', 253595, 'aggressive'],
            [95833, 3, 'Hyrja', 260419, 'aggressive'],
            [99868, 3, 'Fenryr', 284114, 'aggressive'],
            [95675, 3, 'God-King Skovald', 247255, 'aggressive'],
            [95676, 3, 'Odyn', 1302095, 'aggressive'],
        ]);
    }
}
