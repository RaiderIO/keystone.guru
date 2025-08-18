FROM node:lts-alpine
WORKDIR /app

# runtime deps
RUN set -eux; \
    apk add --no-cache sqlite openssl

# build deps only for installing from git (removed afterwards)
RUN set -eux; \
    apk add --no-cache --virtual .build-deps git python3 make g++

# ⬇️ install your fork (pin to a branch or commit SHA)
#     remove --prod so "prepare" can build if needed
RUN yarn global add --no-lockfile "github:Wotuu/laravel-echo-server#991c5a264f00659a9e4f6dbd6ea0a84486339ccc" \
 && yarn cache clean \
 && apk del .build-deps

# (rest unchanged)
COPY bin/* /usr/local/bin/
COPY src/* /usr/local/src/
RUN chmod +x /usr/local/bin/setup-env.sh \
 && chmod +x /usr/local/bin/start-laravel-echo-server.sh

ENTRYPOINT ["/usr/local/bin/start-laravel-echo-server.sh"]
