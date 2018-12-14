<?php

use Illuminate\Database\Migrations\Migration;

class UpgradeLaravelFrom56To57 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $process = new \Symfony\Component\Process\Process(['/bin/bash', base_path('sh') . DIRECTORY_SEPARATOR . '56_to_57.sh']);

        $process->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
