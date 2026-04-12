#!/usr/bin/env bash
set -euo pipefail

# Install PHP dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Install Node dependencies and build assets
npm ci --no-audit
npm run build

# Set up the .env if not already present
if [ ! -f .env ]; then
  cp .env.example .env
  # Override for local dev
  sed -i 's/APP_ENV=production/APP_ENV=local/' .env
  sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env
fi

# Generate app key if not set
php artisan key:generate --ansi --no-interaction || true

# Run migrations
php artisan migrate --force --no-interaction
