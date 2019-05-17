# LUA IS A NIGHTMARE
So, hopefully I won't have to read this file ever again but here's some pointers about what needs to be done to install LUA on PHP.

## Installing LUA on Ubuntu 14.04
Install these packages:

```bash
sudo apt-get install lua5.3
sudo apt-get install liblua5.3
```

Create soft link of /usr/include/lua TO /usr/include/lua*.*:
```bash
sudo ln -s /usr/include/lua5.3/ /usr/include/lua
```
This way, when typing `lua` in the command line, it'll actually work.

Find liblua*.*.so.0.0.0 and liblua*.*.a files in /usr/lib.
If they don't exist, they could be in /usr/lib/x86_64-linux-gnu or /usr/lib/i386-linux-gnu depending on OS.

Copy them from that directory into /usr/lib as liblua.so and liblua.a (WITHOUT VERSION NUMBER).
```bash
sudo cp /usr/lib/x86_64-linux-gnu/liblua5.3.a /usr/lib/liblua.a
sudo cp /usr/lib/x86_64-linux-gnu/liblua5.3.so.0.0.0 /usr/lib/liblua.so
```

(source: http://php.net/manual/en/lua.installation.php)

Link `/usr/local/lib/lua` to `/usr/local/lib/lua/5.3`, it expected a version number first but I didn't have any. Had to fake the version number folder which in fact pointed to the directory above it.
This fixed an issue or two.

```bash
sudo ln -s /usr/local/lib/lua /usr/local/lib/lua/5.3
```

Link `/bin/lua5.3` to `/bin/lua` and/or `/usr/bin/lua5.3` to `/usr/bin/lua`, I couldn't use the `lua` command but only the `lua5.3` command to open LUA command line. This would break some programs that expected `lua` as-is to work. Understandably.

```bash
sudo ln /bin/lua5.3 /bin/lua
sudo ln /usr/bin/lua5.3 /usr/bin/lua
```

Then I had to link `/usr/include/lua*.*/`'s `lauxlib.h`, `lua.h`, `luaconf.h` to `/usr/include/` so that they'd appear in that folder.

```bash
sudo ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h
sudo ln /usr/include/lua5.3/lua.h /usr/include/lua.h
sudo ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h
```

# Coupling LUA to PHP using the plugin

Now that LUA is properly installed, download latest version of the php plugin here: https://pecl.php.net/package/lua

Copy the file you just downloaded to /tmp, from there, run the following command (Adjust version accordingly of course).

```bash
sudo pecl install lua-2.0.6.tgz
```
Add extension=lua.so to php.ini file (could be /etc/php/7.1/fpm/php.ini). Check with `phpinfo()` to see the exact version you got.

## Installing a module in LUA
Modules are installed in `/usr/lib/lua/*.*/yourmodule.so`. I had to create this folder.

From this project's `storage/lua` folder, copy `bit.so` to the `/usr/lib/lua/5.3/` folder. This enables some bit operators required by various World of Warcraft addons that are loaded when parsing MDT Import strings.
Lastly, from the same folder,  upload the `lua.so` file to your PHP installation's `extension_dir`. See `phpinfo()` for where it is, for mine it was `/usr/lib/php/20160303`.

# Restarting service
Lastly, run `sudo service nginx restart` and `sudo service php7.1-fpm restart`. This will restart your web services, it should load the LUA library now.

# Troubleshooting
From the command line, run `php -v`, check if there's a problem loading the LUA library.  If so, you may need to recompile the LUA library (lua.so).
Try running `php -m | grep lua` and see if a single line with `lua` pops up. If so, the module is correctly loaded.
If the LUA library still does not pop up, verify you placed the `extension=lua.so` in the correct `php.ini` file. Keep in mind the different PHP versions.
And again, restart the proper version of `php-fpm` as noted above.