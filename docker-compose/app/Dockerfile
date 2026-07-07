### Rust builder (nightly for edition2024)
FROM rust:1.91.1-bookworm AS wa-builder
# or: FROM rustlang/rust:nightly     # (Debian base)
RUN apt-get update && apt-get install -y --no-install-recommends \
  build-essential cmake pkg-config libssl-dev git ca-certificates \
 && rm -rf /var/lib/apt/lists/*

RUN cargo --version && rustc --version
RUN cargo install --git https://codeberg.org/Velithris/cli-weakauras-parser.git --locked

### ---- PHP extension builder ----
# Compiles every PHP extension (and the Lua modules) with the full toolchain and
# -dev headers. Only the resulting .so files are copied into the runtime image, so
# none of the ~350MB build toolchain / -dev headers end up in the final image.
FROM php:8.4-fpm AS php-builder

# Build-only dependencies: compilers (PHPIZE_DEPS) + the -dev headers each extension links against
RUN apt-get update && apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libmagickwand-dev \
    libssl-dev \
    libzip-dev \
    liblua5.3-dev \
 && rm -rf /var/lib/apt/lists/*

# PECL + bundled PHP extensions
RUN pecl install redis imagick swoole && docker-php-ext-enable redis imagick swoole
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Lua headers expected by php-lua / LuaBitOp at the conventional include paths
RUN ln -s /usr/include/lua5.3/ /usr/include/lua \
 && cp /usr/lib/*-linux-gnu/liblua5.3.a /usr/lib/liblua.a || true \
 && cp /usr/lib/*-linux-gnu/liblua5.3.so.0.0.0 /usr/lib/liblua.so || true \
 && ln /usr/include/lua5.3/lua.h /usr/include/lauxlib.h || true \
 && ln /usr/include/lua5.3/lua.h /usr/include/lua.h || true \
 && ln /usr/include/lua5.3/lua.h /usr/include/luaconf.h || true

# Build & enable the PHP Lua extension
RUN mkdir -p /tmp/php-lua \
 && cd /tmp/php-lua \
 && git clone https://github.com/Wotuu/php-lua.git . \
 && phpize \
 && ./configure \
 && make -j"$(nproc)" \
 && make install \
 && docker-php-ext-enable lua

# Build the LuaBitOp Lua module (loaded by the lua PHP extension at runtime)
COPY LuaBitOp-1.0.3.zip /tmp/LuaBitOp-1.0.3.zip
RUN unzip /tmp/LuaBitOp-1.0.3.zip -d /tmp \
 && cd /tmp/LuaBitOp-1.0.3 \
 && make -j"$(nproc)" \
 && mkdir -p /usr/lib/lua/5.3/ \
 && cp bit.so /usr/lib/lua/5.3/

### ---- Runtime image ----
FROM php:8.4-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Runtime dependencies only (no -dev headers, no compilers).
# - First line: CLI tooling used by the app / deploy scripts.
# - Second line: shared libraries the compiled PHP extensions link against.
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl default-mysql-client gettext zip unzip sudo nano ca-certificates \
    libpng16-16 libonig5 libxml2 libzip5 liblua5.3-0 libmagickwand-7.q16-10 \
    libfftw3-double3 liblcms2-2 liblqr-1-0 libfreetype6 libfontconfig1 \
 && rm -rf /var/lib/apt/lists/*

# Install Node.js 24 via NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Skip the chrome-headless-shell download (~164MB). route_thumbnail.js launches with
# `headless: true`, which uses the full Chrome binary, so the shell is never used. Declared
# as ENV so it also applies to app-aws's `npm install`, which re-resolves the same cache.
ENV PUPPETEER_SKIP_CHROME_HEADLESS_SHELL_DOWNLOAD=true

# Install global packages. handlebars is the asset compiler CLI.
# Puppeteer + Chrome live in the worker image (keystone.guru-worker) only.
RUN npm i -g handlebars

# Copy the compiled PHP extensions (.so) and their enable .ini files from the builder.
# Both stages share the php:8.4-fpm base, so sonames and the extension_dir path match.
COPY --from=php-builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=php-builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=php-builder /usr/lib/lua/5.3/bit.so /usr/lib/lua/5.3/bit.so

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy scripts
COPY docker_init.sh /usr/local/bin/docker_init.sh

RUN chmod +x /usr/local/bin/docker_init.sh

USER root

# ----- copy the compiled WA parser from the builder stage -----
COPY --from=wa-builder /usr/local/cargo/bin/cli_weakauras_parser /usr/bin/cli_weakauras_parser

RUN chmod 0755 /usr/bin/cli_weakauras_parser

# Set working directory
WORKDIR /var/www

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
