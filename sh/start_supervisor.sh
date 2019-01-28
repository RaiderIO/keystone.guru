#!/usr/bin/env bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl stop laravel-echo-server-dev:*
sudo supervisorctl start laravel-echo-server-dev:*

# @TODO This should only restart the workers relative to the current environment, but this works for now
sudo supervisorctl stop laravel-worker-dev:*
sudo supervisorctl start laravel-worker-dev:*

sudo supervisorctl stop laravel-worker-staging:*
sudo supervisorctl start laravel-worker-staging:*

sudo supervisorctl stop laravel-worker-live:*
sudo supervisorctl start laravel-worker-live:*