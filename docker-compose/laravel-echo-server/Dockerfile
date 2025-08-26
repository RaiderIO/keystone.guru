FROM node:lts-alpine
WORKDIR /app

# Runtime deps
RUN set -eux; apk add --no-cache sqlite openssl

# Build deps needed to compile TS once
RUN set -eux; apk add --no-cache --virtual .build-deps git python3 make g++

# ⬇️ install your fork (pin branch/commit)
# IMPORTANT: no --prod; we’ll build it afterwards.
RUN yarn global add --no-lockfile "github:Wotuu/laravel-echo-server#7d5afc288cd550bf0c9149f08f15682f20f4c06b"

# Build inside the globally installed package so /dist exists
RUN set -eux; \
  PKGDIR="$(yarn global dir)/node_modules/laravel-echo-server"; \
  yarn --cwd "$PKGDIR" install --production=false; \
  yarn --cwd "$PKGDIR" build; \
  { [ -f "$PKGDIR/dist/cli.js" ] || [ -f "$PKGDIR/dist/cli/index.js" ] || [ -d "$PKGDIR/dist/cli" ]; }; \
  yarn cache clean; \
  apk del .build-deps


# (your existing bits)
COPY bin/* /usr/local/bin/
COPY src/* /usr/local/src/
RUN chmod +x /usr/local/bin/setup-env.sh \
 && chmod +x /usr/local/bin/start-laravel-echo-server.sh

ENTRYPOINT ["/usr/local/bin/start-laravel-echo-server.sh"]
