FROM node:lts-alpine

WORKDIR /app

### Install Laravel Echo Server and dependencies
RUN set -eux; \
    apk add --update --no-cache \
        sqlite \
        openssl \
    ; \
    apk add --update --no-cache --virtual .build-deps \
        build-base \
    ; \
    yarn global add --prod --no-lockfile laravel-echo-server ; \
    apk del .build-deps ; \
    yarn cache clean ; \
    mkdir -p /app/database ; \
    rm /usr/local/bin/docker-entrypoint.sh

COPY bin/* /usr/local/bin/
COPY src/* /usr/local/src/


# Make them executable
RUN chmod +x /usr/local/bin/docker-entrypoint && \
    chmod +x /usr/local/bin/health-check

ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]
CMD ["start"]
