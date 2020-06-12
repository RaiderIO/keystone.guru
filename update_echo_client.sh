#!/usr/bin/env bash

tput setaf 2;
echo "Refreshing Echo Server Client..."
tput sgr0;

laravel-echo-server client:remove DEADBEEFDEADBEEF
laravel-echo-server client:add DEADBEEFDEADBEEF