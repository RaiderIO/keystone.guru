#!/usr/bin/env bash

tput setaf 2;
echo "Refreshing Echo Server Clients..."
tput sgr0;

# Restore echo server clients
cd config/echo
for folder in jastola live local staging
do
    echo $folder
    cd $folder
    # Use the same arbitrary app id to remove and add the client; refreshing the tokens
    laravel-echo-server client:remove DEADBEEFDEADBEEF
    laravel-echo-server client:add DEADBEEFDEADBEEF
    cd ..
done
# Back out twice to the root folder again
cd ../../

pwd