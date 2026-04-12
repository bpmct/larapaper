FROM mcr.microsoft.com/devcontainers/php:8.4-bookworm

# Install system dependencies for imagick and zip
RUN apt-get update && apt-get install -y --no-install-recommends \
    libmagickwand-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-install zip
