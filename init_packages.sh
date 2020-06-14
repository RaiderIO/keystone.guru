#!/usr/bin/env bash

apt-get install -y redis-server \
                supervisor \
                pngquant \
                composer \
                nodejs-dev \
                node-gyp \
                libssl1.0-dev \
                npm

npm install -g laravel-echo-server handlebars