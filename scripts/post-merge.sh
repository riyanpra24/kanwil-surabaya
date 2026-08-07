#!/bin/bash
set -e

# Install PHP dependencies (production only, skip framework dev scripts)
composer install --no-dev --optimize-autoloader --no-scripts

# Ensure writable directory has correct permissions
chmod -R 775 writable/
