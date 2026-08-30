FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-install pdo_pgsql pgsql mbstring bcmath gd zip exif pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Same limits as Dockerfile.prod — PHP's 2M default upload_max_filesize
# silently rejects anything above it before RegisterCreator's own
# max:10240 (10MB) rule ever runs. Matched here so local testing with a
# real (not `UploadedFile::fake()`) file behaves the same as prod.
RUN echo "upload_max_filesize=12M" > "$PHP_INI_DIR/conf.d/uploads.ini" \
    && echo "post_max_size=13M" >> "$PHP_INI_DIR/conf.d/uploads.ini"

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
