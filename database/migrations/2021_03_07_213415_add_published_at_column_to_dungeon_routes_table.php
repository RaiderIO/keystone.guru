<?php

use App\Models\PublishedState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublishedAtColumnToDungeonRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dateTime('published_at')->default('1970-01-01 00:00:00')->after('updated_at');
        });

        DB::update(
            sprintf(
                'UPDATE `dungeon_routes` SET `published_at` = `updated_at` WHERE published_state_id = %s',
                PublishedState::where('name', PublishedState::WORLD)->firstOrFail()->id
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeon_routes', function (Blueprint $table)
        {
            $table->dropColumn('published_at');
        });
    }
}
