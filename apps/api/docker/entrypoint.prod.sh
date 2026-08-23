#!/bin/sh
set -e

# Real env vars are only available at container start (Render injects them
# at runtime, not at Docker build time), so caching must happen here rather
# than in the Dockerfile — a build-time cache would freeze an empty/wrong
# config into the image. Migrations are deliberately NOT run here: they run
# once via Render's Pre-Deploy Command, before this container takes traffic.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
