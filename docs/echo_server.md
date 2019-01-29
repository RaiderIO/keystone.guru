##Setting up Laravel Echo Server

###Install Laravel Echo Server
`npm install -g laravel-echo-server`

###Install Redis Server
`sudo apt install redis-server`

Make sure it's running:

`service redis start`

###Run Laravel Echo Server

Supervisor handles running echo server.

`laravel-echo-server start --config="<config.json>"`

###Modify .env

`BROADCAST_DRIVER=redis`