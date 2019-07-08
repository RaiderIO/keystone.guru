<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReleaseChangelogChangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('release_changelog_changes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('release_changelog_id');
            $table->integer('release_category_id');
            // May be null for old issues, but must be required for later on
            $table->integer('ticket_id')->nullable();
            $table->text('change');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('release_changelog_changes');
    }
}
