<?php

namespace Deployer;

require 'recipe/laravel.php';
// This is hacky as hell but trust me - this is kinda how it should be since this file should be called using
// ./vendor/bin/dep
foreach (glob(__DIR__.'/vendor/wotuu/keystone.guru.deployer/src/*.php') as $file) {
    require $file;
}
