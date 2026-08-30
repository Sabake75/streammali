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

# Render assigns its own $PORT at runtime (defaults to 10000, not the 80
# hardcoded in the Dockerfile's CMD) and routes traffic to it — binding to
# :80 instead produced "frankenphp: Operation not permitted" in Render's
# sandboxed runtime (privileged port, whether that's a plain capability
# issue or Render's proxy simply never reaching a port it didn't ask for).
# $PORT is a shell variable, so it can only be expanded here (a sh script),
# not in the Dockerfile's exec-form CMD, which is why the port isn't baked
# in there — falls back to :80 for `docker run` outside Render.
exec "$@" --listen ":${PORT:-80}"
