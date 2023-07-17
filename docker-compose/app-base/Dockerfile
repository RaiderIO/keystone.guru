FROM php:7.4-cli

# Arguments defined in docker-compose.yml
ARG user
ARG uid

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
    default-mysql-client  \
    wget

# Required for puppeteer to work
RUN apt-get install -y libxdamage-dev \
                libnss3-dev \
                libgdk-pixbuf2.0-dev \
                libgtk-3-dev \
                libxss-dev \
                libasound2

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

USER root

# Set working directory
WORKDIR /var/www