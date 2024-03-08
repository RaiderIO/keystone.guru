FROM php:8.2-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Install system dependencies
RUN apt-get update && apt-get install -y \
    sudo \
    wget \
    openssh-client

# https://medium.com/trabe/use-your-local-ssh-keys-inside-a-docker-container-ea1d117515dc
RUN useradd -m user
RUN mkdir -p /home/user/.ssh && ln -s /run/secrets/deployer_ssh_key /home/user/.ssh/id_rsa
RUN chown -R user:user /home/user/.ssh

USER user

RUN echo "Host *\n\tStrictHostKeyChecking no\n\tIdentityFile ~/.ssh/id_rsa\n\t" >> /home/user/.ssh/config


# Set working directory
WORKDIR /var/www
