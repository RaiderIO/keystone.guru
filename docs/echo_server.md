##Setting up Laravel Echo Server

###Install Laravel Echo Server
`npm install -g laravel-echo-server`

###Install Redis Server
`sudo apt install redis-server`

Make sure it's running:

`service redis start`

Should be sorted already, but make sure you have predis (PHP redis).

`composer require predis/predis`

###Run Laravel Echo Server

Supervisor handles running echo server.

###Modify .env

`BROADCAST_DRIVER=redis`