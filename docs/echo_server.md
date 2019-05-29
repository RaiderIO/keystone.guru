##Setting up Laravel Echo Server

###Install Laravel Echo Server
`npm install -g laravel-echo-server`

###Install Redis Server
`sudo apt install redis-server`

Make sure it's running:

`service redis start`

To verify:

`redis-cli ping`

should be replied with `PONG`.

###Run Laravel Echo Server
Supervisor handles running echo server.

`laravel-echo-server start --config="<config.json>"`

###Modify .env
`BROADCAST_DRIVER=redis`

###Apache2 proxy
(Note I run the server on port 6002)

Enable modules:

`a2enmod proxy`

`a2enmod proxy_balancer`

`a2enmod proxy_http`

`a2enmod proxy_wstunnel`

`a2enmod lbmethod_byrequests`

```apacheconfig
RewriteEngine On
RewriteCond %{REQUEST_URI}  ^/socket.io            [NC]
RewriteCond %{QUERY_STRING} transport=websocket    [NC]
RewriteRule /(.*)           ws://localhost:6002/$1 [P,L]
ProxyPass        /socket.io http://localhost:6002/socket.io
ProxyPassReverse /socket.io http://localhost:6002/socket.io
```

###nginx proxy
The config should go between the `charset` and the `location /` parts.
```nginxconfig
charset utf-8;

location /socket.io {
    proxy_pass http://127.0.0.1:6002;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection 'upgrade';
    proxy_set_header Host $host;
    proxy_cache_bypass $http_upgrade;
}

location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```