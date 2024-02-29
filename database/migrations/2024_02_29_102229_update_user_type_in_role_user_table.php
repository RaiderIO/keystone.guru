<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::update('
            UPDATE `role_user` SET `user_type` = "App\Models\User" WHERE `user_type` = "App\User"
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::update('
            UPDATE `role_user` SET `user_type` = "App\User" WHERE `user_type` = "App\Models\User"
        ');
    }
};
