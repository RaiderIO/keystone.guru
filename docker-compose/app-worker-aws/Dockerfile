ARG STAGE=staging

FROM keystone.guru-worker

ENV DEBIAN_FRONTEND=noninteractive

# Set working directory
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y awscli

ARG STAGE
# GIT_REF optionally pins the checkout to a specific tag/commit (e.g. a release tag),
# falling back to the branch matching STAGE when not provided. It is also written to the
# `version` file so the worker shares the web images' cache namespace and compiled/<tag>
# asset keying (laravel-model-caching and ksgCompiledAsset both read this file).
ARG GIT_REF
# Checkout the correct version of the repo
RUN git config --global --add safe.directory /var/www/html && \
    BRANCH=$([ "${STAGE}" = "production" ] && echo "master" || echo "development") && \
    REF="${GIT_REF:-$BRANCH}" && \
    echo "Using ref: $REF" && \
    git clone --depth 1 --branch "$REF" https://github.com/RaiderIO/keystone.guru.git . && \
    printf '%s' "${GIT_REF:-$(git rev-list HEAD -1)}" > version && \
    cat version; \
    rm -rf .git

# Install composer packages (token is already configured in main image)
RUN composer install --no-dev --optimize-autoloader && \
      php artisan vendor:publish --tag=laravel-assets --ansi --force

# Install NPM packages. The worker base image bakes Chrome into PUPPETEER_CACHE_DIR
# (/opt/puppeteer-cache) via a global puppeteer@24 install; as long as the repo's puppeteer
# resolves the same Chrome build, its postinstall detects the cached binary and skips the
# download. When the versions drift it downloads that version's Chrome as well — still correct,
# just image bloat.
RUN npm install

# Generate Swagger related files
RUN php artisan l5-swagger:generate --all && \
      php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"

# Ensure that storage and bootstrap/cache are writable for the web server
RUN chmod o+w ./storage/ -R && \
      chown -R www-data:www-data storage bootstrap/cache
