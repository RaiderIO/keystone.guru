## LUA IS A NIGHTMARE
So, hopefully I won't have to read this file ever again but here's some pointers about what needs to be done to install LUA on PHP.

##Installing LUA in PHP
Download latest version here: https://pecl.php.net/package/lua

Follow these instructions:
```bash
Installation on Ubuntu 14.04

"*.*" means version number

Packages to install (sudo apt-get install):
Install lua*.*
Install liblua*.*

Create soft link of /usr/include/lua TO /usr/include/lua*.*:
sudo ln -s /usr/include/lua*.*/ /usr/include/lua

Find liblua*.*.so and liblua*.*.a files in /usr/lib.
If they don't exist, they could be in /usr/lib/x86_64-linux-gnu or /usr/lib/i386-linux-gnu depending on OS.

Copy them from that directory into /usr/lib as liblua.so and liblua.a (WITHOUT VERSION NUMBER).

Example:
sudo cp /usr/lib/x86_64-linux-gnu/liblua5.2.a /usr/lib/liblua.a

Now execute the following command (1.1.0 is the version at the time of writing this):
sudo pecl install lua-1.1.0

Add extension=lua.so to php.ini file (could be /etc/php5/(cli/apache)/php.ini)
```

Then of course `service apache2 restart`.

(source: http://php.net/manual/en/lua.installation.php)

##Additional links
Link `/usr/local/lib/lua` to `/usr/local/lib/lua/5.3`, it expected a version number first but I didn't have any. Had to fake the version number folder which in fact pointed to the directory above it.
This fixed an issue or two.
Link `/bin/lua5.3` to `/bin/lua`, I couldn't use the `lua` command but only the `lua5.3` command to open LUA command line. This would break some programs that expected `lua` as-is to work. Understandably.
I also had to link `/usr/include/lua` to `/usr/include/lua5.3` (`lua` folder is the link to `lua5.3`)
Then I had to link `/usr/include/lua5.3/`'s `lauxlib.h`, `lua.h`, `luaconf.h` to `/usr/include/` so that they'd appear in that folder.

##Installing a module in LUA
Modules are installed in `/usr/lib/lua/*.*/yourmodule.so`. I had to create this folder.

##Tip
If possible just use LUA 7.1 or 7.2, currently 7.3 caused me too much headaches to bother with it again. It worked in the end but fuck.