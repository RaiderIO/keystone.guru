<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dungeon_route_scheduled_publishes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dungeon_route_id')->index();
            $table->string('published_state');
            $table->dateTime('publish_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dungeon_route_scheduled_publishes');
    }
};
