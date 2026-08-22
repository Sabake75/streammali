#!/bin/sh
set -e

if [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    npm ci
fi

exec "$@"
