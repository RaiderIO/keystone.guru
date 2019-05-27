#!/usr/bin/env bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl stop laravel-echo-server:*
sudo supervisorctl start laravel-echo-server:*

sudo supervisorctl stop laravel-horizon:*
sudo supervisorctl start laravel-horizon:*