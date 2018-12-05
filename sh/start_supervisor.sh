#!/usr/bin/env bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl stop laravel-worker-dev:*
sudo supervisorctl start laravel-worker-dev:*