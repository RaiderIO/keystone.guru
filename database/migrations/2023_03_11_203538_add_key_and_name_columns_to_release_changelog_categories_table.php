<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeyAndNameColumnsToReleaseChangelogCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('release_changelog_categories', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->string('key')->after('id');
            $table->string('name')->after('key');

            $table->index(['key']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('release_changelog_categories', function (Blueprint $table) {
            $table->string('category')->after('id');
            $table->dropColumn('key');
            $table->dropColumn('name');

            $table->dropIndex(['key']);
        });
    }
}
