# LUA IS A NIGHTMARE
So, hopefully I won't have to read this file ever again but here's some pointers about what needs to be done to install LUA on PHP.

## To install LUA and configure it
Run `storage/sh/setup_lua.sh`.

## Lua
```
sudo apt-get install lua5.3
sudo apt-get install liblua5.3
```
This will install & compile the actual LUA language. Generally this goes OK. Some copies are required to make it work; but all in all not too bad.


## Lua PECL
```
sudo pecl install lua-2.0.6
```
This is the PHP coupling with lua. This uses `phpize` to determine the current module version (ex. `/usr/lib/php/20170718`), and compiles against it. Make sure you have a correct version of `php-dev` or `php7.2-dev` or whatever installed.
If you run this command and see that it doesn't grab the correct version (ex. `20160303`), PHP LUA will NOT WORK.

## Bit.so
This is extracted and compiled from the `storage/lua/LuaBitOp-1.0.3.zip`. This must be done because otherwise versions aren't matching and it won't work.

## Testing
If you run `php -v` and you get any module errors related to lua, something went wrong and you need to try again. Try re-installing things and deleting stuff.

# Restarting service
Lastly, run `sudo service nginx restart` and `sudo service phpX.X-fpm restart` (or `sudo service apache2 restart`). This will restart your web services, it should load the LUA library now.

# Troubleshooting
From the command line, run `php -v`, check if there's a problem loading the LUA library.  If so, you may need to recompile the LUA library (lua.so).
Try running `php -m | grep lua` and see if a single line with `lua` pops up. If so, the module is correctly loaded.
If the LUA library still does not pop up, verify you placed the `extension=lua.so` in the correct `php.ini` file. Keep in mind the different PHP versions.
And again, restart the proper version of `php-fpm` as noted above.