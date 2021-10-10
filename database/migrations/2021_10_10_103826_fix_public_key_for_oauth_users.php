<?php

use App\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

class FixPublicKeyForOauthUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // May take some time but it'll work
        User::where('public_key', '')->chunk(100, function (Collection $users) {
            foreach ($users as $user) {
                $user->public_key = User::generateRandomPublicKey();
                $user->save();
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
        // No reversing this unfortunately
    }
}
