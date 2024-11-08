// https://github.com/tlaverdure/laravel-echo-server/issues/178
require('dotenv').config();

const env = process.env;
const EchoServer = require('laravel-echo-server');

EchoServer.run({
    "authHost": env.APP_URL,
    "authEndpoint": "/broadcasting/auth",
    "clients": [
        {
            "appId": env.LARAVEL_ECHO_SERVER_CLIENT_APP_ID,
            "key": env.LARAVEL_ECHO_SERVER_CLIENT_KEY
        }
    ],
    "database": "redis",
    "databaseConfig": {
        "redis": {
            "host": env.REDIS_HOST,
            "port": env.REDIS_PORT,
            "db": env.REDIS_DB,
            "keyPrefix": env.REDIS_PREFIX
        },
        "publishPresence": true
    },
    "devMode": env.APP_DEBUG === 'true',
    "host": null,
    "port": env.LARAVEL_ECHO_SERVER_PORT,
    "protocol": "http",
    "socketio": {},
    "sslCertPath": "",
    "sslKeyPath": "",
    "sslCertChainPath": "",
    "sslPassphrase": "",
    "subscribers": {
        "http": true,
        "redis": true
    },
    "apiOriginAllow": {
        "allowCors": false,
        "allowOrigin": "",
        "allowMethods": "",
        "allowHeaders": ""
    }
});
