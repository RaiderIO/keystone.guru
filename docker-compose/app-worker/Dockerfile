FROM keystone.guru

# Chrome runtime libraries required by Puppeteer for thumbnail generation.
# These are intentionally absent from the slim keystone.guru base image.
RUN apt-get update && apt-get install -y --no-install-recommends \
    fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 libcairo2 libcups2 \
    libdbus-1-3 libdrm2 libgbm1 libglib2.0-0 libgtk-3-0 libnspr4 libnss3 \
    libpango-1.0-0 libx11-6 libxcb1 libxcomposite1 libxdamage1 libxext6 libxfixes3 \
    libxi6 libxkbcommon0 libxrandr2 libxrender1 libxss1 libxtst6 \
 && rm -rf /var/lib/apt/lists/*

# Download the Chrome binary into /root/.cache/puppeteer.
# Local dev relies on this baked cache: the repo (incl. node_modules) is bind-mounted
# but /root/.cache is not, so the image-baked binary is the only Chrome source locally.
RUN npm i -g puppeteer@24
