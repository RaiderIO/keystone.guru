<?php

use App\Models\DungeonRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

class FixIncorrectTitlesAgainInDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $output = new ConsoleOutput();
        DungeonRoute::chunk(100, function (Collection $dungeonRoutes) use ($output) {
            /** @var Collection|DungeonRoute[] $dungeonRoutes */
            foreach ($dungeonRoutes as $dungeonRoute) {
                if (empty($dungeonRoute->title) || empty($dungeonRoute->getTitleSlug())) {
                    $oldTitle = $dungeonRoute->title;
                    if ($dungeonRoute->update(['title' => __($dungeonRoute->dungeon->name)])) {
                        $output->writeln(sprintf('<info>%d: Updated %s to %s</info>', $dungeonRoute->id, $oldTitle, __($dungeonRoute->dungeon->name)));
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            //
        });
    }
}
