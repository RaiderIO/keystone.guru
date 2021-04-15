<?php

use App\Models\Dungeon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddSlugColumnToDungeonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dungeons', function (Blueprint $table)
        {
            $table->string('slug')->after('name')->default('');
        });

        // Fill the slug field
        Dungeon::all()->each(function (Dungeon $dungeon)
        {
            $dungeon->slug = Str::slug($dungeon->name);
            $dungeon->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dungeons', function (Blueprint $table)
        {
            $table->dropColumn('slug');
        });
    }
}
