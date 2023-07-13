FROM php:7.4-fpm

# Arguments defined in docker-compose.yml
ARG user
ARG uid

# Add nodejs
RUN curl -sL https://deb.nodesource.com/setup_15.x | bash -

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    nodejs \
    sudo \
    zip \
    unzip \
    default-mysql-client

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy scripts
COPY docker-compose/docker_init.sh /usr/local/bin/docker_init.sh

# Make them executable
RUN chmod +x /usr/local/bin/docker_init.sh

# Set working directory
WORKDIR /var/www

USER root

#ENTRYPOINT ["/usr/local/bin/docker_init.sh"]
