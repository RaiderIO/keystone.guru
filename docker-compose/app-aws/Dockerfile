ARG STAGE=staging

FROM keystone.guru

ENV DEBIAN_FRONTEND=noninteractive

# Set working directory
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y awscli

ARG STAGE
# Checkout the correct version of the repo
RUN git config --global --add safe.directory /var/www/html && \
    BRANCH=$([ "${STAGE}" = "production" ] && echo "master" || echo "development") && \
    echo "Using branch: $BRANCH" && \
    git clone --depth 1 --branch "$BRANCH" https://github.com/RaiderIO/keystone.guru.git . && \
    git rev-list HEAD -1 > version && \
    cat version

# Install composer packages (token is already configured in main image)
RUN composer install --no-dev --optimize-autoloader && \
      php artisan vendor:publish --tag=laravel-assets --ansi --force

# Install NPM packages
RUN npm install && \
    node node_modules/puppeteer/install.js

# Generate Swagger related files
RUN php artisan l5-swagger:generate --all && \
      php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Ensure that storage and bootstrap/cache are writable for the web server
RUN chmod o+w ./storage/ -R && \
      chown -R www-data:www-data storage bootstrap/cache

COPY /usr/local/etc/php-fpm.d/zz-overrides.conf /usr/local/etc/php-fpm.d/zz-overrides.conf

