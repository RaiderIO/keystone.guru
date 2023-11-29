FROM php:7.4-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Add nodejs
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    nodejs \
    sudo \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client  \
    wget

# Install handlebars
RUN npm i -g handlebars

# Required for puppeteer to work
RUN apt-get install -y libxdamage-dev \
                libnss3-dev \
                libgdk-pixbuf2.0-dev \
                libgtk-3-dev \
                libxss-dev \
                libasound2

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy scripts
COPY docker_init.sh /usr/local/bin/docker_init.sh

# Make them executable
RUN chmod +x /usr/local/bin/docker_init.sh

USER root

# Set working directory
WORKDIR /tmp

# Download cmake, required for cargo, which is required for cli-weakauras-parser
RUN curl https://sh.rustup.rs -sSf | sh -s -- -y && \
    # Temp fix so that cargo works https://github.com/rust-lang/rustup/issues/686#issuecomment-272023791
    export PATH="$HOME/.cargo/bin:$PATH" && \
    # cli-weakauras-parser requires cmake..
    # https://vitux.com/how-to-install-cmake-on-ubuntu-18-04/
    wget https://github.com/Kitware/CMake/releases/download/v3.19.2/cmake-3.19.2.tar.gz && \
    tar -zxvf cmake-3.19.2.tar.gz && \
    rm cmake-3.19.2.tar.gz

# Install cmake
RUN apt-get update && apt-get install -y libssl-dev && \
    cd cmake-3.19.2 && \
    ./bootstrap && \
    make && \
    sudo make install && \
    cmake --version

# Install cli weakauras parser
RUN /root/.cargo/bin/cargo install --git https://github.com/Zireael-N/cli-weakauras-parser.git && \
    ln -s ~/.cargo/bin/cli_weakauras_parser /usr/bin/cli_weakauras_parser

# Give the www-data user rights to execute cli weakauras parser as sudo
RUN echo "www-data ALL=NOPASSWD: /usr/bin/cli_weakauras_parser" >> /etc/sudoers

# Install actual LUA language
RUN apt-get update && apt-get install -y lua5.3 liblua5.3 && \
    ln -s /usr/include/lua5.3/ /usr/include/lua && \
    cp /usr/lib/x86_64-linux-gnu/liblua5.3.a /usr/lib/liblua.a && \
    cp /usr/lib/x86_64-linux-gnu/liblua5.3.so.0.0.0 /usr/lib/liblua.so && \
    ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h && \
    ln /usr/include/lua5.3/lua.h /usr/include/lua.h && \
    ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h && \
    pecl install lua-2.0.6

#sudo mkdir -p /usr/lib/lua/5.3/
#sudo cp storage/lua/* /usr/lib/lua/5.3/
# Install LUA BitOp
COPY LuaBitOp-1.0.3.zip /tmp/LuaBitOp-1.0.3.zip

RUN unzip /tmp/LuaBitOp-1.0.3.zip -d /tmp && \
    cd /tmp/LuaBitOp-1.0.3 && \
    make && \
    sudo mkdir -p /usr/lib/lua/5.3/ && \
    cp bit.so /usr/lib/lua/5.3/

# Setup files/folders
COPY etc/crontab /etc/crontab

COPY var/ /var/

# Set working directory
WORKDIR /var/www

#ENTRYPOINT ["/usr/local/bin/docker_init.sh"]
