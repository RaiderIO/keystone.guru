<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 64);
            $table->string('audience', 16);
            $table->text('body')->nullable();
            $table->json('params')->nullable();
            $table->string('url')->nullable();
            $table->integer('game_version_id')->nullable();
            $table->boolean('bypasses_preferences')->default(false);
            $table->integer('created_by')->nullable();
            $table->string('dedupe_key', 191)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('dedupe_key', 'nm_dedupe_unq');
            $table->index(['audience', 'created_at'], 'nm_audience_created_idx');
            $table->index(['type', 'created_at'], 'nm_type_created_idx');
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('notification_message_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['notification_message_id', 'user_id'], 'un_message_user_unq');
            $table->index(['user_id', 'read_at'], 'un_user_read_idx');
            $table->index(['user_id', 'id'], 'un_user_id_idx');
        });

        Schema::create('global_notification_reads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('notification_message_id');
            $table->timestamp('read_at')->nullable();

            $table->unique(['user_id', 'notification_message_id'], 'gnr_user_message_unq');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('global_notifications_read_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notifications_enabled', 'global_notifications_read_at']);
        });

        Schema::dropIfExists('global_notification_reads');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('notification_messages');
    }
};
