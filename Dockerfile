FROM --platform=linux/arm64 php:8.1-fpm

# Set working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath xml
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

# Copy existing permissions from host directories to container directories
COPY --chown=www:www . /var/www/html

# Set working directory permission
RUN chown -R www:www /var/www/html

# Change current user to www
USER www

# Generate .env file if it doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Install composer dependencies
RUN composer install --no-scripts

# Generate application key
RUN php artisan key:generate

# Expose port 9000
EXPOSE 9000

# Start php-fpm server
CMD ["php-fpm"]
