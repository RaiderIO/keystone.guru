#!/bin/sh

# Read all environment variables from .env file
set -e
set -o allexport
source .env
set +o allexport

# laravel-echo-server init
if [[ "$1" == 'init' ]]; then
    set -- laravel-echo-server "$@"
fi

if [ "$1" == 'start' ] && [ -f '/app/docker-compose/laravel-echo-server/laravel-echo-server.lock' ]; then
    rm /app/docker-compose/laravel-echo-server/laravel-echo-server.lock
fi

echo "${APP_URL}"

# laravel-echo-server <sub-command>
if [[ "$1" == 'start' ]] || [[ "$1" == 'client:add' ]] || [[ "$1" == 'client:remove' ]]; then
    if [[ "${LARAVEL_ECHO_SERVER_GENERATE_CONFIG:-true}" == "false" ]]; then
        # wait for another process to inject the config
        echo -n "Waiting for /app/docker-compose/laravel-echo-server/laravel-echo-server.json"
        while [[ ! -f /app/docker-compose/laravel-echo-server/laravel-echo-server.json ]]; do
            sleep 2
            echo -n "."
        done
    elif [[ ! -f /app/docker-compose/laravel-echo-server/laravel-echo-server.json ]]; then
        echo "Creating /app/docker-compose/laravel-echo-server/laravel-echo-server.json"
        cp /usr/local/src/laravel-echo-server-template.json /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        # Replace with environment variables
        sed -i "s|LARAVEL_ECHO_SERVER_AUTH_URL|${LARAVEL_ECHO_SERVER_AUTH_URL:-${LARAVEL_ECHO_SERVER_HOST:-localhost}}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|LARAVEL_ECHO_ALLOW_ORIGIN|${LARAVEL_ECHO_ALLOW_ORIGIN:-${LARAVEL_ECHO_AUTH_HOST:-${LARAVEL_ECHO_SERVER_HOST:-localhost}}}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        LARAVEL_ECHO_CLIENTS="[]"
        if [ ! -z "${LARAVEL_ECHO_CLIENT_APP_ID}" ]; then
            LARAVEL_ECHO_CLIENTS="[{\"appId\": \"${LARAVEL_ECHO_CLIENT_APP_ID}\",\"key\": \"${LARAVEL_ECHO_CLIENT_APP_KEY}\"}]"
        fi
        sed -i "s|LARAVEL_ECHO_SERVER_CLIENT_APP_ID|${LARAVEL_ECHO_SERVER_CLIENT_APP_ID}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|LARAVEL_ECHO_SERVER_CLIENT_KEY|${LARAVEL_ECHO_SERVER_CLIENT_KEY}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|LARAVEL_ECHO_SERVER_DB|${LARAVEL_ECHO_SERVER_DB:-redis}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|REDIS_HOST|${REDIS_HOST:-redis}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|REDIS_PORT|${REDIS_PORT:-6380}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|REDIS_PASSWORD|${REDIS_PASSWORD}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|REDIS_PREFIX|${REDIS_PREFIX}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        sed -i "s|REDIS_DB|${REDIS_DB:-0}|i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
        # Remove password config if it is empty
        sed -i "s|\"password\": \"\",||i" /app/docker-compose/laravel-echo-server/laravel-echo-server.json
    fi
    set -- laravel-echo-server "$@"
fi

# first arg is `-f` or `--some-option`
if [[ "${1#-}" != "$1" ]]; then
    set -- laravel-echo-server "$@"
fi
