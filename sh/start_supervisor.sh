#!/usr/bin/env bash
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl stop laravel-echo-server-staging:*
sudo supervisorctl start laravel-echo-server-staging:*

sudo supervisorctl stop laravel-echo-server-live:*
sudo supervisorctl start laravel-echo-server-live:*

sudo supervisorctl stop laravel-echo-server-jastola:*
sudo supervisorctl start laravel-echo-server-jastola:*

# @TODO This should only restart the workers relative to the current environment, but this works for now
sudo supervisorctl stop laravel-horizon-staging:*
sudo supervisorctl start laravel-horizon-staging:*

sudo supervisorctl stop laravel-horizon-live:*
sudo supervisorctl start laravel-horizon-live:*

sudo supervisorctl stop laravel-horizon-jastola:*
sudo supervisorctl start laravel-horizon-jastola:*