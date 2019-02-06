<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserRoleToAdminUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // May have already been done
        try {
            DB::table('role_user')->insert(['role_id' => 2, 'user_id' => 1, 'user_type' => 'App\User']);
        } catch (Exception $ex) {
            // Doesn't matter!
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eh
    }
}
