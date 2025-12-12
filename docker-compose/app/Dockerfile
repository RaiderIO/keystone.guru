### Rust builder (nightly for edition2024)
FROM rust:1.91.1-bookworm AS wa-builder
# or: FROM rustlang/rust:nightly     # (Debian base)
RUN apt-get update && apt-get install -y --no-install-recommends \
  build-essential cmake pkg-config libssl-dev git ca-certificates \
 && rm -rf /var/lib/apt/lists/*

RUN ls -lh /usr/local/cargo/bin


RUN cargo --version && rustc --version
RUN cargo install --git https://github.com/Zireael-N/cli-weakauras-parser.git --locked

### ---- Runtime image ----
FROM php:8.3-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Add nodejs
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash -

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libmagickwand-dev \
    nodejs \
    sudo \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client  \
    wget \
    gettext \
    nano \
    libxdamage-dev \
    libnss3-dev \
    libgdk-pixbuf-xlib-2.0-dev \
    libgtk-3-dev \
    libxss-dev \
    libasound2 \
 && rm -rf /var/lib/apt/lists/*

# Install global packages
RUN npm i -g handlebars puppeteer@19.9.0

# Have puppeteer download chromium (this really should be done when puppeteer is installed from npm..)
RUN node /usr/lib/node_modules/puppeteer/install.js

# Install PHP extensions
RUN pecl install redis imagick swoole && docker-php-ext-enable redis imagick swoole
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy scripts
COPY docker_init.sh /usr/local/bin/docker_init.sh

RUN chmod +x /usr/local/bin/docker_init.sh

USER root

# ----- ADD: copy the compiled WA parser from the builder stage -----
COPY --from=wa-builder /usr/local/cargo/bin/cli_weakauras_parser /usr/bin/cli_weakauras_parser

RUN chmod 0755 /usr/bin/cli_weakauras_parser


# Set working directory
WORKDIR /var/www

# Install actual LUA language
RUN apt-get update && apt-get install -y --no-install-recommends lua5.3 liblua5.3-dev \
 && rm -rf /var/lib/apt/lists/* \
 && ln -s /usr/include/lua5.3/ /usr/include/lua \
 && cp /usr/lib/*-linux-gnu/liblua5.3.a /usr/lib/liblua.a || true \
 && cp /usr/lib/*-linux-gnu/liblua5.3.so.0.0.0 /usr/lib/liblua.so || true \
 && ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h || true \
 && ln /usr/include/lua5.3/lua.h /usr/include/lua.h || true \
 && ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h || true

# Install PHP Lua extension
RUN mkdir -p /tmp/php-lua \
 && cd /tmp/php-lua \
 && git clone https://github.com/Wotuu/php-lua.git . \
 && phpize \
 && ./configure \
 && make -j"$(nproc)" \
 && make install

# Install LUA BitOp
COPY LuaBitOp-1.0.3.zip /tmp/LuaBitOp-1.0.3.zip
RUN unzip /tmp/LuaBitOp-1.0.3.zip -d /tmp \
 && cd /tmp/LuaBitOp-1.0.3 \
 && make -j"$(nproc)" \
 && sudo mkdir -p /usr/lib/lua/5.3/ \
 && cp bit.so /usr/lib/lua/5.3/

# Enable LUA
RUN docker-php-ext-enable lua

# Setup files/folders
COPY etc/ /etc/

RUN chmod -R 440 /etc/sudoers.d/

COPY .env /tmp/.env

COPY root/ /root/

# Only copy this file over to prevent overriding the entire folder which will be changed by docker-php-ext-enable
COPY usr/local/etc/php/conf.d/uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# Only copy this file over to prevent overriding the entire folder which will be changed by docker-php-ext-enable
COPY usr/local/etc/php-fpm.d/ /usr/local/etc/php-fpm.d/

COPY var/ /var/

# Import .env file and apply it to the current environment
RUN cd /tmp \
 && export $(echo $(cat .env | sed 's/#.*//g' | sed 's/\r//g' | xargs) | envsubst) \
 && envsubst < /root/.config/composer/auth.json > /root/.config/composer/auth.json.tmp \
 && mv /root/.config/composer/auth.json.tmp /root/.config/composer/auth.json \
 && rm /tmp/.env

#ENTRYPOINT ["/usr/local/bin/docker_init.sh"]
