#!/usr/bin/env bash
# Install lua
sudo apt-get install lua5.3
sudo apt-get install liblua5.3

sudo ln -s /usr/include/lua5.3/ /usr/include/lua
sudo cp /usr/lib/x86_64-linux-gnu/liblua5.3.a /usr/lib/liblua.a
sudo cp /usr/lib/x86_64-linux-gnu/liblua5.3.so.0.0.0 /usr/lib/liblua.so

sudo ln -s /usr/local/lib/lua /usr/local/lib/lua/5.3
sudo ln /bin/lua5.3 /bin/lua
sudo ln /usr/bin/lua5.3 /usr/bin/lua

sudo ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h
sudo ln /usr/include/lua5.3/lua.h /usr/include/lua.h
sudo ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h

sudo pecl install lua-2.0.6

sudo mkdir -p /usr/lib/lua/5.3/
sudo cp storage/lua/* /usr/lib/lua/5.3/

echo "Now you just need to add 'extension=lua.so' to your PHP.ini"