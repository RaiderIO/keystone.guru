// See https://blog.sixeyed.com/docker-healthchecks-why-not-to-use-curl-or-iwr/

var http = require("http");
var config = {};

try {
    config = require("/app/docker-compose/laravel-echo-server/laravel-echo-server.json");
} catch (error) {
    console.error('Unable to find config file!');
}

var options = {
    host : "localhost",
    port : config.port | 6001,
    path : `${config.socketio && config.socketio.path ? config.socketio.path : '/socket.io'}/socket.io.js`,
    timeout : 2000
};

var request = http.request(options, (res) => {
    console.log(`STATUS: ${res.statusCode}`);
    if (res.statusCode === 200) {
        process.exit(0);
    } else {
        process.exit(1);
    }
});

request.on('error', function(err) {
    console.error('Request error!');
    process.exit(1);
});

request.end();