FROM php:8.3

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    sudo \
    wget \
    gettext \
    openssh-client

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY root/ /root/

COPY .env /tmp/.env

# Import .env file and apply it to the current environment
RUN cd /tmp && \
    export $(echo $(cat .env | sed 's/#.*//g' | sed 's/\r//g' | xargs) | envsubst) && \
    envsubst < /root/.config/composer/auth.json > /root/.config/composer/auth.json.tmp && \
    mv /root/.config/composer/auth.json.tmp /root/.config/composer/auth.json &&  \
    rm /tmp/.env

# https://medium.com/trabe/use-your-local-ssh-keys-inside-a-docker-container-ea1d117515dc
RUN useradd -m user
RUN mkdir -p /home/user/.ssh && ln -s /run/secrets/deployer_ssh_key /home/user/.ssh/id_rsa
RUN chown -R user:user /home/user/.ssh

USER user

RUN echo "Host *\n\tStrictHostKeyChecking no\n\tIdentityFile ~/.ssh/id_rsa\n\t" >> /home/user/.ssh/config

# Set working directory
WORKDIR /var/www

# Run indefinitely
ENTRYPOINT ["tail", "-f", "/dev/null"]
