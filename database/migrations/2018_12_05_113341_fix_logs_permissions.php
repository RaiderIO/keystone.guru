<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixLogsPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // See https://stackoverflow.com/a/29882246/771270
        $process = new \Symfony\Component\Process\Process(['setfacl',
            '-d',
            '-m',
            'g:www-data:rwx',
            'storage/logs']);
        $process->run();

        \Illuminate\Support\Facades\Artisan::call('cache:clear');

        $process = new \Symfony\Component\Process\Process(['composer',
            'dump-autoload']);
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
