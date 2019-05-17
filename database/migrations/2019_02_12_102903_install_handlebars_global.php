<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InstallHandlebarsGlobal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $process = new \Symfony\Component\Process\Process(['npm', '-g', 'install', 'handlebars']);
        $process->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $process = new \Symfony\Component\Process\Process(['npm', '-g', 'uninstall', 'handlebars']);
        $process->run();
    }
}
