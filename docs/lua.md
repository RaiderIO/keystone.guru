# LUA IS A NIGHTMARE
So, hopefully I won't have to read this file ever again but here's some pointers about what needs to be done to install LUA on PHP.

## Installing LUA in PHP & Ubuntu 14.04
Download latest version here: https://pecl.php.net/package/lua


Copy the file you just downloaded to /tmp, from there, run the following command (Adjust version accordingly of course).

```bash
sudo pecl install lua-2.0.6.tgz
```
Add extension=lua.so to php.ini file (could be /etc/php5/(cli/apache)/php.ini)

Install these packages:

```bash
sudo apt-get install lua*.*
sudo apt-get install liblua*.*
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

Then of course `service apache2 restart`, or ngnix if that's how you roll.

(source: http://php.net/manual/en/lua.installation.php)

## Additional links (if possible)
Link `/usr/local/lib/lua` to `/usr/local/lib/lua/5.3`, it expected a version number first but I didn't have any. Had to fake the version number folder which in fact pointed to the directory above it.
This fixed an issue or two.

```bash
sudo ln -s /usr/local/lib/lua /usr/local/lib/lua/5.3
```

Link `/bin/lua*.*` to `/bin/lua`, I couldn't use the `lua` command but only the `lua*.*` command to open LUA command line. This would break some programs that expected `lua` as-is to work. Understandably.
Then I had to link `/usr/include/lua*.*/`'s `lauxlib.h`, `lua.h`, `luaconf.h` to `/usr/include/` so that they'd appear in that folder.

```bash
sudo ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h
sudo ln /usr/include/lua5.3/lua.h /usr/include/lua.h
sudo ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h
```

## Installing a module in LUA
Modules are installed in `/usr/lib/lua/*.*/yourmodule.so`. I had to create this folder.
