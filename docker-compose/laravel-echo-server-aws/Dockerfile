ARG STAGE=staging

FROM laravel-echo-server

# Working directory = /app

# Install required packages
RUN apk add --no-cache python3 py3-pip git

# Install awscli through pip
RUN pip install --no-cache-dir awscli && \
    aws --version

# Checkout the correct version of the repo
RUN git config --global --add safe.directory /tmp/keystone.guru && \
    BRANCH=$([ "$STAGE" = "production" ] && echo "master" || echo "development") && \
    git clone --depth 1 --branch "$BRANCH" https://github.com/RaiderIO/keystone.guru.git /tmp/keystone.guru && \
    cp -r /tmp/keystone.guru/* /app && \
        rm -rf /tmp/keystone.guru

# Install NPM packages and compile code
RUN npm install && \
    npm i express@5.1.0 --save

COPY bin/* /usr/local/bin/
COPY src/* /usr/local/src/

RUN chmod +x /usr/local/bin/start-laravel-echo-server-healthchecks.sh

ENTRYPOINT ["/usr/local/bin/start-laravel-echo-server-healthchecks.sh"]
