FROM phpswoole/swoole:6.0.1-php8.2

# Arguments defined in docker-compose.yml
ARG user
ARG uid


# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    sudo \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client  \
    wget \
    gettext

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

USER root

# Install actual LUA language
RUN apt-get update && apt-get install -y lua5.3 liblua5.3 && \
    ln -s /usr/include/lua5.3/ /usr/include/lua && \
    cp /usr/lib/*-linux-gnu/liblua5.3.a /usr/lib/liblua.a && \
    cp /usr/lib/*-linux-gnu/liblua5.3.so.0.0.0 /usr/lib/liblua.so && \
    ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h && \
    ln /usr/include/lua5.3/lua.h /usr/include/lua.h && \
    ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h

# Install PHP Lua https://serverfault.com/a/219577/1076801
RUN mkdir /tmp/php-lua && \
    cd /tmp/php-lua && \
    git clone https://github.com/Wotuu/php-lua.git . && \
    phpize && \
    ./configure && \
    make && \
    make install

# Install LUA BitOp
COPY LuaBitOp-1.0.3.zip /tmp/LuaBitOp-1.0.3.zip

RUN unzip /tmp/LuaBitOp-1.0.3.zip -d /tmp && \
    cd /tmp/LuaBitOp-1.0.3 && \
    make && \
    sudo mkdir -p /usr/lib/lua/5.3/ && \
    cp bit.so /usr/lib/lua/5.3/

# Set working directory
WORKDIR /var/www

#ENTRYPOINT ["/usr/local/bin/docker_init.sh"]
