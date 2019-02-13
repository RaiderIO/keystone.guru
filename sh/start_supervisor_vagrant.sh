#!/usr/bin/env bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl stop laravel-worker:*
sudo supervisorctl start laravel-worker:*