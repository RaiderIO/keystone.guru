<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config
set('repository', 'git@github.com:Wotuu/keystone.guru');

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts
host('<server ip address>')
    ->set('remote_user', 'deployer')
    ->set('deploy_path', '/var/www/html/keystone.guru.deployer');

// Hooks
after('deploy:failed', 'deploy:unlock');
