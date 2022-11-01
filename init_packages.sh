#!/usr/bin/env bash

apt-get install -y redis-server \
                supervisor \
                pngquant \
                composer \
                nodejs-dev \
                node-gyp \
                libssl1.0-dev \
                npm

npm install -g laravel-echo-server handlebars cross-env dotenv


cd /tmp || exit

# Required for cargo, which is required for cli-weakauras-parser
curl https://sh.rustup.rs -sSf | sh -s -- -y
# Temp fix so that cargo works https://github.com/rust-lang/rustup/issues/686#issuecomment-272023791
export PATH="$HOME/.cargo/bin:$PATH"

# cli-weakauras-parser requires cmake..
# https://vitux.com/how-to-install-cmake-on-ubuntu-18-04/
wget https://github.com/Kitware/CMake/releases/download/v3.19.2/cmake-3.19.2.tar.gz
tar -zxvf cmake-3.19.2.tar.gz
cd cmake-3.19.2 || exit
./bootstrap
make
sudo make install
cmake --version

cargo install --git https://github.com/Zireael-N/cli-weakauras-parser.git
ln -s ~/.cargo/bin/cli_weakauras_parser /usr/bin/cli_weakauras_parser
