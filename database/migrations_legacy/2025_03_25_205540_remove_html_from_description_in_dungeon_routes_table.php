<?php

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\ConsoleOutput;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $consoleOutput = new ConsoleOutput();
        $consoleOutput->writeln('');

        DungeonRoute::whereNot('description', '')
            ->select(['id', 'description'])
            ->chunk(100, function (Collection $dungeonRoutes) use ($consoleOutput) {
                foreach ($dungeonRoutes as $dungeonRoute) {
                    /** @var DungeonRoute $dungeonRoute */
                    $cleaned = strip_tags(html_entity_decode($dungeonRoute->description));

                    if ($dungeonRoute->description !== $cleaned) {
                        $consoleOutput->writeln(sprintf('%d: %s', $dungeonRoute->id, $dungeonRoute->description));
                        $dungeonRoute->update(['description' => $cleaned]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dungeon_routes', function (Blueprint $table) {
            //
        });
    }
};
