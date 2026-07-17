ARG BASE_IMAGE=keystone.guru-app
FROM ${BASE_IMAGE}

# Chrome runtime libraries required by Puppeteer for thumbnail generation.
# These are intentionally absent from the slim base app image (docker-compose/app).
RUN apt-get update && apt-get install -y --no-install-recommends \
    fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libcairo2 libcups2 \
    libdbus-1-3 libdrm2 libgbm1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 \
    libpango-1.0-0 libx11-6 libxcb1 libxcomposite1 libxdamage1 libxext6 libxfixes3 \
    libxi6 libxkbcommon0 libxrandr2 libxrender1 libxss1 libxtst6 \
 && rm -rf /var/lib/apt/lists/*

# Download the Chrome binary into a UID-neutral cache dir (not /root/.cache: local horizon runs
# as the host user (#3414), which cannot read /root). The ENV persists into runtime and into the
# app-worker-aws child image, so both puppeteer.launch() and its npm install resolve this cache.
# Local dev relies on this baked cache: the repo (incl. node_modules) is bind-mounted
# but this dir is not, so the image-baked binary is the only Chrome source locally.
# a+rwX keeps it usable from any UID, root or not (it's a throwaway in-container cache).
ENV PUPPETEER_CACHE_DIR=/opt/puppeteer-cache

# Install the exact `puppeteer` version pinned in package.json, so the Chrome build baked here can
# never drift from what puppeteer-core (bind-mounted from the repo's node_modules) actually
# launches — no separate version to keep in sync by hand. Requires the build context to be the
# repo root (see docker-compose.yml / release-deploy.yml) so package.json is reachable here.
COPY package.json /tmp/package.json
RUN PUPPETEER_VERSION=$(node -p "require('/tmp/package.json').dependencies.puppeteer.match(/[0-9][0-9.]*/)[0]") \
 && npm i -g puppeteer@"$PUPPETEER_VERSION" \
 && rm /tmp/package.json \
 && chmod -R a+rwX /opt/puppeteer-cache
